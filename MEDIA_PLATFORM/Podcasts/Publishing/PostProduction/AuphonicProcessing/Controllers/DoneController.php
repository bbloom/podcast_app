<?php

namespace MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

class DoneController extends Controller
{
    /**
     * Display the "Auphonic Clean-Up Done — what next?" page.
     *
     * Shown after AuphonicProcessing\CleanUpController::destroy() succeeds.
     * All clean-up work is done — MP3 downloaded, WAV deleted, Auphonic
     * production deleted, status advanced to ready_to_upload_production_file.
     * The user chooses to continue to Upload Production Audio or return to
     * the Post-Production Dashboard.
     */
    public function __invoke(PodcastEpisode $podcastEpisode): View|RedirectResponse
    {
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        return view(
            'media_platform.podcasts.publishing.post_production.auphonic_processing.done',
            ['episode' => $podcastEpisode->load('show')]
        );
    }
}