<?php

namespace MediaPlatform\Digest\ContentSources\Youtube\Services;

use Illuminate\Support\Facades\Http;

class YoutubeService
{
    private string $apiKey;
    private string $baseUrl = 'https://www.googleapis.com/youtube/v3';

    public function __construct()
    {
        $this->apiKey = config('youtube.api_key');
    }

    /**
     * Parse raw user input into a normalized type and value.
     *
     * Returns:
     *   ['type' => 'handle',     'value' => 'ChannelName']
     *   ['type' => 'channel_id', 'value' => 'UCxxxxxxxxxxxxxx']
     *   ['type' => 'keywords',   'value' => 'the midnight special']
     */
    public function parseInput(string $input): array
    {
        $input = trim($input);

        // Strip protocol and www
        $cleaned = preg_replace('#^https?://#', '', $input);
        $cleaned = preg_replace('#^www\.#', '', $cleaned);

        // Strip youtube.com/
        $cleaned = preg_replace('#^youtube\.com/#', '', $cleaned);

        // Handle: @ChannelName
        if (str_starts_with($cleaned, '@')) {
            return [
                'type'  => 'handle',
                'value' => ltrim($cleaned, '@'),
            ];
        }

        // Channel ID URL: channel/UCxxxxxxxxxxxxxx
        if (str_starts_with($cleaned, 'channel/')) {
            $channelId = substr($cleaned, strlen('channel/'));

            if (preg_match('/^UC[A-Za-z0-9_-]{22}$/', $channelId)) {
                return [
                    'type'  => 'channel_id',
                    'value' => $channelId,
                ];
            }
        }

        // Plain keywords — anything that isn't a URL or handle
        // If the original input contained no URL-like patterns, treat as keywords
        if (! str_contains($input, 'youtube.com') && ! str_starts_with($input, 'http')) {
            return [
                'type'  => 'keywords',
                'value' => $input,
            ];
        }

        return ['type' => 'unknown', 'value' => null];
    }

    /**
     * Look up a channel by handle (e.g. "ABBA" from @ABBA).
     * Returns one channel or an empty array.
     */
    public function searchByHandle(string $handle): array
    {
        $response = Http::get("{$this->baseUrl}/channels", [
            'key'       => $this->apiKey,
            'forHandle' => $handle,
            'part'      => 'snippet',
        ]);

        if ($response->failed()) {
            return [];
        }

        return $this->formatChannelItems($response->json('items', []));
    }

    /**
     * Look up a channel directly by its channel ID.
     * Returns one channel or an empty array.
     */
    public function searchByChannelId(string $channelId): array
    {
        $response = Http::get("{$this->baseUrl}/channels", [
            'key'  => $this->apiKey,
            'id'   => $channelId,
            'part' => 'snippet',
        ]);

        if ($response->failed()) {
            return [];
        }

        return $this->formatChannelItems($response->json('items', []));
    }

    /**
     * Search for channels by keyword.
     * Returns up to 10 channel candidates.
     */
    public function searchByKeywords(string $keywords): array
    {
        // First, search for channel IDs matching the keywords
        $searchResponse = Http::get("{$this->baseUrl}/search", [
            'key'        => $this->apiKey,
            'q'          => $keywords,
            'type'       => 'channel',
            'part'       => 'snippet',
            'maxResults' => 10,
        ]);

        if ($searchResponse->failed()) {
            return [];
        }

        $items = $searchResponse->json('items', []);

        if (empty($items)) {
            return [];
        }

        // Extract channel IDs from search results
        $channelIds = collect($items)
            ->pluck('snippet.channelId')
            ->filter()
            ->implode(',');

        // Fetch full channel details using the channels endpoint
        $channelResponse = Http::get("{$this->baseUrl}/channels", [
            'key'  => $this->apiKey,
            'id'   => $channelIds,
            'part' => 'snippet',
        ]);

        if ($channelResponse->failed()) {
            return [];
        }

        return $this->formatChannelItems($channelResponse->json('items', []));
    }

    /**
     * Normalize raw API items into a consistent shape for the views.
     */
    private function formatChannelItems(array $items): array
    {
        return collect($items)->map(function ($item) {
            $customUrl  = $item['snippet']['customUrl'] ?? null;
            $channelUrl = $customUrl
                ? 'https://www.youtube.com/' . $customUrl
                : 'https://www.youtube.com/channel/' . $item['id'];

            return [
                'channel_id'  => $item['id'],
                'title'       => $item['snippet']['title'],
                'description' => $item['snippet']['description'] ?? '',
                'thumbnail'   => $item['snippet']['thumbnails']['default']['url'] ?? '',
                'handle'      => $customUrl ?? '—',
                'channel_url' => $channelUrl,
                'rss_url'     => 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $item['id'],
            ];
        })->toArray();
    }
}