<?php

namespace MediaPlatform\Digest\Processing\Services;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DigestBuilderService — assembles pending summaries into a structured digest.
 *
 * WHAT THIS SERVICE DOES
 * ──────────────────────
 * 1. Queries the summaries table for all items that are:
 *    - Relevant (is_relevant = true)
 *    - Not yet included in a digest (included_in_digest = false)
 *    - Belonging to a list_source that is part of the given list
 *
 * 2. Groups those summaries by their list_source (which represents one
 *    content source — a YouTube channel, podcast, or RSS feed). Each group
 *    carries the source's name and type alongside its summary items.
 *
 * 3. Returns a structured array ready to be passed into a Blade view:
 *    [
 *      'list'         => ListModel,
 *      'date'         => Carbon,
 *      'groups'       => Collection of source groups (see buildGroups()),
 *      'total_items'  => int,
 *      'source_count' => int,
 *    ]
 *
 * 4. After successful publish, markAsIncluded() updates the summaries table
 *    so those items are never re-included in a future digest.
 *
 * IDEMPOTENCY
 * ───────────
 * markAsIncluded() uses the IDs collected during build() — only the exact
 * summaries that were rendered will be marked. If PublishDigest is retried
 * after a partial failure (before markAsIncluded() ran), the same summaries
 * will be picked up again and re-rendered. This is safe because:
 *   - Webpage: overwrites the same file (dated filename means no orphan files)
 *   - Email: could send a duplicate, but this is a rare edge case
 */
class DigestBuilderService
{
    /**
     * Summary IDs collected during the last build() call.
     * Stored so markAsIncluded() can update exactly the right rows.
     */
    private array $collectedIds = [];

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Build the digest data structure for a list.
     *
     * Returns null if there are no pending summaries (caller should treat
     * this as "nothing to publish" and skip delivery).
     *
     * @param  ListModel  $list  The list to build a digest for.
     * @return array|null        Structured digest data, or null if nothing to publish.
     */
    public function build(ListModel $list): ?array
    {
        // Reset the collected IDs from any previous call on this instance.
        $this->collectedIds = [];

        // ── Load pending summaries for all sources in this list ───────────────
        // We join list_sources to get the source name and type alongside each
        // summary, so we can group them meaningfully in the view.
        $rows = DB::table('summaries')
            ->join('list_sources', 'list_sources.id', '=', 'summaries.list_source_id')
            ->where('list_sources.list_id', $list->id)
            ->where('summaries.is_relevant', true)
            ->where('summaries.included_in_digest', false)
            ->whereNotNull('summaries.summary_html')    // exclude items that failed to process
            ->orderBy('summaries.source_published_at', 'desc')
            ->select([
                'summaries.id',
                'summaries.list_source_id',
                'summaries.source_url',
                'summaries.source_title',
                'summaries.source_description',
                'summaries.source_published_at',
                'summaries.processing_mode',
                'summaries.summary_html',
                'list_sources.sourceable_type',
                'list_sources.sourceable_id',
            ])
            ->get();

        if ($rows->isEmpty()) {
            Log::info("DigestBuilderService: No pending summaries for list '{$list->name}' (ID {$list->id}).");
            return null;
        }

        // ── Collect the IDs so markAsIncluded() can act on them ───────────────
        $this->collectedIds = $rows->pluck('id')->all();

        // ── Group by source ───────────────────────────────────────────────────
        $groups = $this->buildGroups($rows, $list);

        $totalItems  = $rows->count();
        $sourceCount = $groups->count();

        Log::info("DigestBuilderService: Built digest for list '{$list->name}': {$totalItems} items from {$sourceCount} sources.");

        return [
            'list'         => $list,
            'date'         => Carbon::now(),
            'groups'       => $groups,
            'total_items'  => $totalItems,
            'source_count' => $sourceCount,
        ];
    }

    /**
     * Mark all summaries from the last build() call as included in a digest.
     *
     * Call this AFTER successful delivery (SFTP upload, email send, or WP post).
     * Never call it before delivery — if delivery fails, the summaries must
     * remain un-included so they are retried on the next run.
     */
    public function markAsIncluded(): void
    {
        if (empty($this->collectedIds)) {
            return;
        }

        $count = DB::table('summaries')
            ->whereIn('id', $this->collectedIds)
            ->update([
                'included_in_digest'    => true,
                'included_in_digest_at' => now(),
                'updated_at'            => now(),
            ]);

        Log::info("DigestBuilderService: Marked {$count} summaries as included in digest.");

        // Clear the IDs so this instance cannot accidentally mark them twice.
        $this->collectedIds = [];
    }

    /**
     * Generate the plain-text excerpt line used in notification 
     * emails. e.g. "12 items from 3 sources"
     */
    public function buildExcerpt(array $digestData): string
    {
        $items   = $digestData['total_items'];
        $sources = $digestData['source_count'];

        $itemWord   = $items   === 1 ? 'item'   : 'items';
        $sourceWord = $sources === 1 ? 'source' : 'sources';

        return "{$items} {$itemWord} from {$sources} {$sourceWord}";
    }

    /**
     * Build the filename slug for this digest run.
     *
     * Format: {list-slug}-digest-{YYYY-MM-DD}
     *
     * The slug is derived from the list name using the same simple
     * lowercasing + hyphenation pattern used elsewhere in the codebase.
     * Dots are preserved (consistent with the makeSlug convention).
     *
     * Examples:
     *   "Morning Tech Digest"  → morning-tech-digest-2026-03-13
     *   "AI & Robotics Weekly" → ai-robotics-weekly-digest-2026-03-13
     */
    public function buildSlug(ListModel $list, Carbon $date): string
    {
        $nameSlug = strtolower(preg_replace('/[^a-z0-9.]+/i', '-', trim($list->name)));
        $nameSlug = trim($nameSlug, '-');

        return $nameSlug . '-digest-' . $date->format('Y-m-d');
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Group summary rows by their list_source, resolving the source display name
     * and type for each group.
     *
     * Each group in the returned collection is an array:
     * [
     *   'source_id'   => int     — list_sources.id
     *   'source_name' => string  — human-readable source name (fetched from the source model)
     *   'source_type' => string  — morph alias: 'youtube_channel', 'podcast', 'text_based_rss_feed'
     *   'items'       => Collection of summary rows for this source
     * ]
     *
     * The source name lookup hits the individual source model tables
     * (youtube_channels, podcasts, text_based_rss_feeds). We do one query per
     * source type rather than N+1 queries per row.
     */
    private function buildGroups(Collection $rows, ListModel $list): Collection
    {
        // ── Group rows by list_source_id ──────────────────────────────────────
        $grouped = $rows->groupBy('list_source_id');

        // ── Pre-fetch source names by type to avoid N+1 queries ───────────────
        // Collect all unique (type → [ids]) pairs from the rows.
        $sourceIdsByType = $rows->groupBy('sourceable_type')
            ->map(fn ($group) => $group->pluck('sourceable_id')->unique()->values());

        // For each source type, fetch all matching source names in one query.
        $sourceNames = [];

        foreach ($sourceIdsByType as $type => $ids) {
            $table = $this->tableForType($type);

            if ($table === null) {
                continue;
            }

            $names = DB::table($table)
                ->whereIn('id', $ids->all())
                ->pluck('title', 'id');

            foreach ($names as $id => $name) {
                $sourceNames[$type][$id] = $name;
            }
        }

        // ── Build the final groups collection ─────────────────────────────────
        return $grouped->map(function (Collection $items, int $listSourceId) use ($sourceNames) {
            $first      = $items->first();
            $sourceType = $first->sourceable_type;
            $sourceId   = $first->sourceable_id;
            $sourceName = $sourceNames[$sourceType][$sourceId] ?? "Source #{$listSourceId}";

            return [
                'source_id'   => $listSourceId,
                'source_name' => $sourceName,
                'source_type' => $sourceType,
                'items'       => $items,
            ];
        })->values();
    }

    /**
     * Map a sourceable_type morph alias to the underlying database table name.
     * Returns null for unknown types (they will be skipped in name lookups).
     */
    private function tableForType(string $type): ?string
    {
        return match ($type) {
            'youtube_channel'     => 'youtube_channels',
            'podcast'             => 'podcasts',
            'text_based_rss_feed' => 'text_based_rss_feeds',
            default               => null,
        };
    }
}