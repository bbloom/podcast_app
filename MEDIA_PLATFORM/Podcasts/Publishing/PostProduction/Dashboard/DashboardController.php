<?php

namespace MediaPlatform\Podcasts\Publishing\PostProduction\Dashboard;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

class DashboardController extends Controller
{
    /**
     * Display the post-production dashboard.
     *
     * Passes any episodes currently stuck in intermediate pipeline statuses
     * to the view so the user can resume them without hunting through each
     * step's index page.
     *
     * Intermediate statuses surfaced here:
     *   `website_published`        — published but build not yet triggered
     *   `build_triggered`          — build triggered, awaiting completion
     *   `ready_to_upload_rss_feed` — RSS on live S3, awaiting validation
     *   `rss_validation_failed`    — validation failed, needs attention
     */
    public function show()
    {
        $inProgressEpisodes = PodcastEpisode::forUser(auth()->id())
            ->whereIn('status', [
                PodcastEpisodeStatus::website_published->value,
                PodcastEpisodeStatus::build_triggered->value,
                PodcastEpisodeStatus::ready_to_upload_rss_feed->value,
                PodcastEpisodeStatus::rss_validation_failed->value,
            ])
            ->orderByScheduledDate()
            ->with('show')
            ->get();

        return view('media_platform.podcasts.publishing.post_production.dashboard', [
            'inProgressEpisodes' => $inProgressEpisodes,
        ]);
    }
}