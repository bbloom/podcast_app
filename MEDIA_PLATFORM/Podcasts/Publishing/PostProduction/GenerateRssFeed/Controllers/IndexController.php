<?php

// =============================================================================
// IndexController — GenerateRssFeed
//
// RSS PIPELINE REORDER CHANGE:
//   Now also shows episodes in `rss_validation_failed` status so the user
//   can re-enter the wizard after fixing the underlying issue. These episodes
//   are reset to `ready_to_generate_rss_feed` by RestartController when
//   the user re-enters Step 1.
//
//   `withStatus()` accepts a single value, so we use `whereIn` directly.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/GenerateRssFeed/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;

class IndexController extends Controller
{
    /**
     * Display episodes ready for RSS feed generation, plus any that have
     * failed validation and need attention.
     *
     * Statuses shown:
     *   `ready_to_generate_rss_feed` — normal entry point.
     *   `rss_validation_failed`      — previously attempted and marked as
     *                                  failed; user can re-enter the wizard
     *                                  after fixing the issue.
     *
     * Both are ordered by scheduled date ascending.
     */
    public function __invoke(): \Illuminate\View\View
    {
        $episodes = PodcastEpisode::forUser(auth()->id())
            ->whereIn('status', [
                PodcastEpisodeStatus::ready_to_generate_rss_feed->value,
                PodcastEpisodeStatus::rss_validation_failed->value,
            ])
            ->orderByScheduledDate()
            ->with('show')
            ->get();

        return view('media_platform.podcasts.publishing.post_production.generate_rss_feed.index', [
            'episodes' => $episodes,
        ]);
    }
}