<?php

// =============================================================================
// IndexController
//
// Lists all episodes with status `ready_to_upload_production_file`, allowing
// the user to select one to proceed with the production audio upload pipeline.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/UploadProductionAudio/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\UploadProductionAudio\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;

class IndexController extends Controller
{
    /**
     * Display a list of episodes that are ready to have their production
     * audio file uploaded to S3 and R2.
     *
     * Only episodes belonging to the authenticated user with status
     * `ready_to_upload_production_file` are shown, ordered by scheduled date.
     */
    public function __invoke(): \Illuminate\View\View
    {
        $episodes = PodcastEpisode::with('show')
            ->where('user_id', auth()->id())
            ->where('status', PodcastEpisodeStatus::ready_to_upload_production_file)
            ->orderBy('scheduled_date')
            ->get();

        return view('media_platform.podcast_studio.post_production.upload_production_audio.index', [
            'episodes' => $episodes,
        ]);
    }
}