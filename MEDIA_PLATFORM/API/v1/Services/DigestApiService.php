<?php

namespace MediaPlatform\API\v1\Services;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Publishing\Models\PublishedDigest;
use Illuminate\Support\Facades\DB;

/**
 * DigestApiService — queries published digests for the API endpoint.
 *
 * Fetches all retained published digest records for a given list, shapes the
 * response array, and marks the records as fetched (api_fetched_at).
 */
class DigestApiService
{
    /**
     * Get all published digests for a list, shaped for the API response.
     *
     * Returns the structured array ready to be returned as JSON.
     * Updates api_fetched_at on all returned records.
     *
     * @param  ListModel  $list  The list to fetch digests for.
     * @return array             The shaped API response.
     */
    public function getDigestsForList(ListModel $list): array
    {
        $records = PublishedDigest::where('list_id', $list->id)
            ->orderByDesc('digest_date')
            ->orderByDesc('id')
            ->limit($list->retention_count)
            ->get();

        // ── Mark as fetched ───────────────────────────────────────────────────
        if ($records->isNotEmpty()) {
            DB::table('published_digests')
                ->whereIn('id', $records->pluck('id'))
                ->update(['api_fetched_at' => now()]);
        }

        // ── Shape the response ────────────────────────────────────────────────
        $digests = $records->map(fn (PublishedDigest $record) => [
            'slug'         => $record->slug,
            'date'         => $record->digest_date->toDateString(),
            'total_items'  => $record->total_items,
            'source_count' => $record->source_count,
            'groups'       => $record->payload,
        ])->all();

        return [
            'list' => [
                'name'        => $list->name,
                'description' => $list->description,
            ],
            'digests' => $digests,
        ];
    }
}