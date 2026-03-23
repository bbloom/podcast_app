<?php

namespace MediaPlatform\Digest\Processing\Services;

use MediaPlatform\Digest\ContentSources\Podcasts\Models\Podcast;
use MediaPlatform\Digest\Processing\Podcasts\Jobs\ProcessPodcastSource;
use MediaPlatform\Digest\Processing\TextBasedRss\Jobs\ProcessTextBasedRssSource;
use MediaPlatform\Digest\Processing\Youtube\Jobs\ProcessYoutubeSource;
use MediaPlatform\Digest\ContentSources\TextBasedRssFeeds\Models\TextBasedRssFeed;
use MediaPlatform\Digest\ContentSources\Youtube\Models\YoutubeChannel;
use Illuminate\Contracts\Queue\ShouldQueue;

class SourceJobResolver
{
    /**
     * Resolve the appropriate processing job for a list_source row.
     *
     * @param  object  $listSource  A row from the list_sources table.
     * @return ShouldQueue The job to be batched.
     *
     * @throws \InvalidArgumentException If the sourceable_type is unknown.
     */
    public static function resolve(object $listSource): ShouldQueue
    {
        return match ($listSource->sourceable_type) {
            'youtube_channel'      => new ProcessYoutubeSource($listSource->id),
            'podcast'              => new ProcessPodcastSource($listSource->id),
            'text_based_rss_feed'  => new ProcessTextBasedRssSource($listSource->id),
            default => throw new \InvalidArgumentException(
                "Unknown sourceable_type: {$listSource->sourceable_type}"
            ),
        };
    }
}
