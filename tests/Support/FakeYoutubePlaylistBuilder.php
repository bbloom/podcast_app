<?php

namespace Tests\Support;

use Illuminate\Support\Carbon;

/**
 * FakeYoutubePlaylistBuilder
 *
 * Builds a fake YouTube playlistItems API JSON response for use in tests.
 *
 * Items are always ordered newest-first, matching the real YouTube API.
 * Each item gets a unique video ID and a published date spaced evenly apart.
 *
 * USAGE
 * ─────
 * // 50 items, newest published today, each 1 day apart:
 * $response = FakeYoutubePlaylistBuilder::build(50, now(), 1);
 *
 * // Prepend 5 new items to an existing set:
 * $response = FakeYoutubePlaylistBuilder::build(55, now(), 1);
 * // The first 5 items will be newer than the original 50.
 *
 * // Build with a specific start date:
 * $response = FakeYoutubePlaylistBuilder::build(50, now()->subDays(3), 1);
 */
class FakeYoutubePlaylistBuilder
{
    /**
     * Build a fake YouTube playlistItems API response.
     *
     * @param  int     $count      Number of items to generate.
     * @param  Carbon  $newestDate The published date of the first (newest) item.
     * @param  int     $daySpacing Days between each item's published date.
     * @param  string  $prefix     Optional prefix for video IDs (useful for distinguishing new vs old items).
     * @return array               Full API response array, ready for Http::response().
     */
    public static function build(
        int    $count,
        Carbon $newestDate,
        int    $daySpacing = 1,
        string $prefix     = 'vid',
    ): array {
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            // Each item is $daySpacing days older than the previous one.
            $publishedAt = $newestDate->copy()->subDays($i * $daySpacing)->toIso8601String();
            $videoId     = $prefix . '_' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);

            $items[] = [
                'kind' => 'youtube#playlistItem',
                'etag' => 'fake-etag-' . $videoId,
                'snippet' => [
                    'publishedAt' => $publishedAt,
                    'title'       => "Video {$videoId} Title",
                    'description' => "Description for video {$videoId}.",
                    'resourceId'  => [
                        'kind'    => 'youtube#video',
                        'videoId' => $videoId,
                    ],
                ],
                'contentDetails' => [
                    'videoId'          => $videoId,
                    'videoPublishedAt' => $publishedAt,
                ],
            ];
        }

        return [
            'kind'     => 'youtube#playlistItemListResponse',
            'etag'     => 'fake-etag-response',
            'pageInfo' => [
                'totalResults'   => $count,
                'resultsPerPage' => 50,
            ],
            'items' => $items,
        ];
    }

    /**
     * Build a source URL for a given video ID, matching what the processor generates.
     */
    public static function sourceUrl(string $videoId): string
    {
        return "https://www.youtube.com/watch?v={$videoId}";
    }

    /**
     * Build a video ID for the Nth item (1-based) with a given prefix.
     * Useful for asserting which items were processed.
     */
    public static function videoId(int $position, string $prefix = 'vid'): string
    {
        return $prefix . '_' . str_pad((string) $position, 3, '0', STR_PAD_LEFT);
    }
}