<?php

// =============================================================================
// IndexController
//
// Lists all episodes with status `ready_for_auphonic`, allowing the user to
// select one to submit to Auphonic for audio processing.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/AuphonicProcessing/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;

class IndexController extends Controller
{
    /**
     * Display a list of episodes that are ready to be submitted to Auphonic.
     *
     * Only episodes with status `ready_for_auphonic` are shown.
     * Episodes are ordered by scheduled date ascending so the most
     * imminent episode appears first.
     */
    public function __invoke(): \Illuminate\View\View
    {
        $episodes = PodcastEpisode::forUser(auth()->id())
        ->withStatus(PodcastEpisodeStatus::ready_for_auphonic)
        ->orderByScheduledDate()
        ->with('show')
        ->get();

        return view('media_platform.podcasts.publishing.post_production.auphonic_processing.index', [
            'episodes' => $episodes,
        ]);
    }
}