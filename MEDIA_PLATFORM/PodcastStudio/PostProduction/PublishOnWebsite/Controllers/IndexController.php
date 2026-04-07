<?php

// =============================================================================
// IndexController
//
// Lists all episodes with status `ready_to_publish`, allowing the user to
// select one to publish on the website.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/PublishOnWebsite/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\PublishOnWebsite\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;

class IndexController extends Controller
{
    /**
     * Display a list of episodes that are ready to be published on the website.
     *
     * Only episodes belonging to the authenticated user with status
     * `ready_to_publish` are shown, ordered by scheduled date ascending
     * so the most imminent episode appears first.
     */
    public function __invoke(): \Illuminate\View\View
    {
        $episodes = PodcastEpisode::with('show')
            ->where('user_id', auth()->id())
            ->where('status', PodcastEpisodeStatus::ready_to_publish)
            ->orderBy('scheduled_date')
            ->get();

        return view('media_platform.podcast_studio.post_production.publish_on_website.index', [
            'episodes' => $episodes,
        ]);
    }
}