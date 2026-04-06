<?php

// =============================================================================
// IndexController
//
// Lists all episodes with status `ready_for_auphonic`, allowing the user to
// select one to submit to Auphonic for audio processing.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/AuphonicProcessing/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\AuphonicProcessing\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;

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
        $episodes = PodcastEpisode::with('show')
            ->where('user_id', auth()->id())
            ->where('status', PodcastEpisodeStatus::ready_for_auphonic)
            ->orderBy('scheduled_date')
            ->get();

        return view('media_platform.podcast_studio.post_production.auphonic_processing.index', [
            'episodes' => $episodes,
        ]);
    }
}