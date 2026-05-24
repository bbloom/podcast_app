<?php

namespace MediaPlatform\Podcasts\Publishing\PostProduction\UploadRecording\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

class DoneController extends Controller
{
    /**
     * Display the "Recording Uploaded — what next?" page.
     *
     * Shown after UploadRecordingController::complete() succeeds. All upload
     * work is done at this point — status has advanced to ready_for_auphonic.
     * The user chooses to continue to Auphonic Processing or return to the
     * Post-Production Dashboard.
     */
    public function __invoke(PodcastEpisode $episode): View|RedirectResponse
    {
        if ($episode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.upload_recording.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        return view(
            'media_platform.podcasts.publishing.post_production.upload_recording.done',
            ['episode' => $episode->load('show')]
        );
    }
}