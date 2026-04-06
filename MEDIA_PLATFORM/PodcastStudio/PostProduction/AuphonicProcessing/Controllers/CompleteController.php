<?php

// =============================================================================
// CompleteController
//
// Displays the "Auphonic Complete" screen after the webhook has fired and
// the episode status has advanced to `auphonic_complete`.
//
// The user is presented with three choices:
//   - Review in Auphonic console (external link — opens Auphonic UI)
//   - Proceed to Clean Up (link to CleanUpController::confirm(), which
//     downloads the MP3, deletes the S3 recording, deletes the Auphonic
//     production, and advances status to `ready_to_upload_production_file`)
//   - Re-submit to Auphonic (link to ResubmitController::confirm(), which
//     deletes the existing production and starts a fresh one)
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/AuphonicProcessing/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\AuphonicProcessing\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\PostProduction\AuphonicProcessing\Services\AuphonicService;

class CompleteController extends Controller
{
    /**
     * Display the "Auphonic Complete" screen.
     *
     * Shows the episode details, a link to review the production in the
     * Auphonic console, and a button to proceed to the next pipeline step.
     *
     * Ownership is enforced — 403 if the episode belongs to another user.
     */
    public function __invoke(PodcastEpisode $podcastEpisode, AuphonicService $auphonic): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
         if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // Guard: only show this page when the episode is actually complete.
        if ($podcastEpisode->status !== PodcastEpisodeStatus::auphonic_complete) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for this page.');
        }

        $consoleUrl = $auphonic->buildAuphonicConsoleUrl($podcastEpisode->auphonic_production_uuid);

        return view('media_platform.podcast_studio.post_production.auphonic_processing.complete', [
            'episode'    => $podcastEpisode,
            //'consoleUrl' => $consoleUrl,  // Getting a 404. 
                                            // I am convinced that Auphonic is blocking external access
              'consoleUrl' => 'https://auphonic.com/engine/',                              
        ]);
    }
}