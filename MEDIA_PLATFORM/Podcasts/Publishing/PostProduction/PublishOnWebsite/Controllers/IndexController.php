<?php

// =============================================================================
// IndexController — PublishOnWebsite
//
// RSS PIPELINE REORDER CHANGE:
//   Previously only showed episodes in `ready_to_publish`. Now also shows
//   `ready_to_publish_website` (new pipeline entry status).
//   `ready_to_publish` is retained for legacy episodes.
//
//   `withStatus()` accepts a single value, so we use `whereIn` directly
//   when querying for multiple acceptable statuses.
//
// Path: MEDIA_PLATFORM/Podcasts/PostProduction/PublishOnWebsite/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

class IndexController extends Controller
{
    /**
     * Display episodes ready to be published on the website.
     *
     * Shows both `ready_to_publish_website` (new pipeline) and
     * `ready_to_publish` (legacy pipeline), ordered by scheduled date
     * ascending so the most imminent episode appears first.
     */
    public function __invoke(): \Illuminate\View\View
    {
        $episodes = PodcastEpisode::forUser(auth()->id())
            ->whereIn('status', [
                PodcastEpisodeStatus::ready_to_publish_website->value,
                PodcastEpisodeStatus::ready_to_publish->value,
            ])
            ->orderByScheduledDate()
            ->with('show')
            ->get();

        return view('media_platform.podcasts.publishing.post_production.publish_on_website.index', [
            'episodes' => $episodes,
        ]);
    }
}