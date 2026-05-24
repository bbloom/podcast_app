<?php

namespace MediaPlatform\Podcasts\Publishing\PostProduction\UploadProductionAudio\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

class DoneController extends Controller
{
    /**
     * Display the "Production Audio Uploaded — what next?" page.
     *
     * Shown after UploadProductionAudio\CleanUpController::destroy() succeeds.
     * All work is done — MP3 uploaded to S3 and R2, local file deleted, status
     * advanced to ready_to_generate_rss_feed.
     * The user chooses to continue to Generate RSS Feed or return to the
     * Post-Production Dashboard.
     */
    public function __invoke(PodcastEpisode $podcastEpisode): View|RedirectResponse
    {
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        return view(
            'media_platform.podcasts.publishing.post_production.upload_production_audio.done',
            ['episode' => $podcastEpisode->load('show')]
        );
    }
}