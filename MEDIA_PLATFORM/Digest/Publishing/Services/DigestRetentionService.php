<?php

namespace MediaPlatform\Digest\Publishing\Services;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\Digest\Publishing\Models\PublishedDigest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DigestRetentionService — prunes old digest data based on each list's retention_count.
 *
 * Called by PublishDigest after successful delivery and after markAsIncluded().
 * Handles all three output types:
 *
 *   - Static Site → prunes `published_digests` (oldest records beyond retention_count)
 *   - Email / SFTP → prunes `summaries` where included_in_digest = true (oldest digest runs beyond retention_count)
 *
 * Never prunes:
 *   - `content_already_processed` (permanent bookmark)
 *   - `summaries` where included_in_digest = false (pending items)
 *   - `summaries` where is_relevant = false (search-mode non-matches, kept for auditing)
 */
class DigestRetentionService
{
    /**
     * Prune old digest data for a list based on its retention_count.
     *
     * Safe to call for any output type — routes to the correct pruning logic.
     */
    public function pruneForList(ListModel $list): void
    {
        if ($list->retention_count < 1) {
            return;
        }

        if ($list->output_type === OutputType::StaticSite) {
            $this->prunePublishedDigests($list);
        } else {
            $this->pruneSummaries($list);
        }
    }

    // =========================================================================
    // Private — Static Site (published_digests)
    // =========================================================================

    /**
     * Prune old published_digests records beyond retention_count.
     * Keeps the newest N records ordered by digest_date descending, id descending.
     */
    private function prunePublishedDigests(ListModel $list): void
    {
        $idsToKeep = PublishedDigest::where('list_id', $list->id)
            ->orderByDesc('digest_date')
            ->orderByDesc('id')
            ->limit($list->retention_count)
            ->pluck('id');

        $deleted = PublishedDigest::where('list_id', $list->id)
            ->whereNotIn('id', $idsToKeep)
            ->delete();

        if ($deleted > 0) {
            Log::info("DigestRetentionService: Pruned {$deleted} published digest(s) for list '{$list->name}'.");
        }
    }

    // =========================================================================
    // Private — Email / SFTP (summaries)
    // =========================================================================

    /**
     * Prune old summaries that have been included in a digest, beyond retention_count.
     *
     * Groups included summaries by the date portion of included_in_digest_at
     * to identify distinct digest runs. Keeps the newest N dates. Deletes all
     * included summaries older than the Nth date.
     *
     * Only deletes rows where:
     *   - included_in_digest = true
     *   - is_relevant = true (preserves search-mode non-matches for auditing)
     *   - the list_source belongs to this list
     */
    private function pruneSummaries(ListModel $list): void
    {
        // ── Get the list_source IDs for this list ─────────────────────────────
        $listSourceIds = DB::table('list_sources')
            ->where('list_id', $list->id)
            ->pluck('id');

        if ($listSourceIds->isEmpty()) {
            return;
        }

        // ── Find distinct digest run dates (by included_in_digest_at date) ────
        $distinctDates = DB::table('summaries')
            ->whereIn('list_source_id', $listSourceIds)
            ->where('included_in_digest', true)
            ->where('is_relevant', true)
            ->whereNotNull('included_in_digest_at')
            ->selectRaw('DATE(included_in_digest_at) as run_date')
            ->distinct()
            ->orderByDesc('run_date')
            ->pluck('run_date');

        // ── If within retention limit, nothing to prune ───────────────────────
        if ($distinctDates->count() <= $list->retention_count) {
            return;
        }

        // ── Find the cutoff date — the Nth newest date ────────────────────────
        $cutoffDate = $distinctDates->get($list->retention_count - 1);

        // ── Delete included summaries older than the cutoff ───────────────────
        $deleted = DB::table('summaries')
            ->whereIn('list_source_id', $listSourceIds)
            ->where('included_in_digest', true)
            ->where('is_relevant', true)
            ->whereNotNull('included_in_digest_at')
            ->whereRaw('DATE(included_in_digest_at) < ?', [$cutoffDate])
            ->delete();

        if ($deleted > 0) {
            Log::info("DigestRetentionService: Pruned {$deleted} old summary(ies) for list '{$list->name}' (kept {$list->retention_count} run(s)).");
        }
    }
}