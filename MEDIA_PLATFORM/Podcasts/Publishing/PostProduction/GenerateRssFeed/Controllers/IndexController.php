<?php

// =============================================================================
// IndexController
//
// Lists all episodes with status `ready_to_generate_rss_feed`, allowing the
// user to select one to proceed with RSS feed generation.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/GenerateRssFeed/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;

class IndexController extends Controller
{
    /**
     * Display a list of episodes that are ready for RSS feed generation.
     *
     * Only episodes belonging to the authenticated user with status
     * `ready_to_generate_rss_feed` are shown, ordered by scheduled date
     * ascending so the most imminent episode appears first.
     */
    public function __invoke(): \Illuminate\View\View
    {
        $episodes = PodcastEpisode::forUser(auth()->id())
            ->withStatus(PodcastEpisodeStatus::ready_to_generate_rss_feed)
            ->orderByScheduledDate()
            ->with('show')
            ->get();

        return view('media_platform.podcasts.publishing.post_production.generate_rss_feed.index', [
            'episodes' => $episodes,
        ]);
    }
}