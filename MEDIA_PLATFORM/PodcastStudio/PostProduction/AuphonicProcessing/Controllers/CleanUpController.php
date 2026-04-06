<?php

// =============================================================================
// CleanUpController
//
// Handles the clean-up step after Auphonic processing is complete and the
// user has reviewed the MP3 and is satisfied with the result.
//
// Clean-up is destructive and irreversible, so it requires explicit
// confirmation via a dedicated confirmation page before anything is deleted.
//
// Steps performed on confirm:
//   1. Download the processed MP3 from Auphonic to local storage.
//      This is a hard failure — if the download fails, nothing is deleted
//      and the user is redirected back to the confirmation page with an error.
//   2. Delete the raw WAV recording from the work-in-progress S3 bucket.
//   3. Delete the Auphonic production via the Auphonic API.
//   4. Clear the `auphonic_production_uuid` on the episode record.
//   5. Advance the episode status to `ready_to_upload_production_file`.
//
// Steps 2 and 3 are soft failures — errors are logged and collected as
// warnings, but the pipeline always advances. The download in step 1 must
// succeed before any deletion occurs.
//
// AuphonicService is injected via the constructor because it is used by
// both confirm() and destroy() — constructor injection ensures the mock
// binds correctly in tests.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/AuphonicProcessing/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\AuphonicProcessing\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\PostProduction\AuphonicProcessing\Services\AuphonicService;
use MediaPlatform\PodcastStudio\PostProduction\CloudStorage\S3_work_in_progress_audio;

class CleanUpController extends Controller
{
    // -------------------------------------------------------------------------
    // AuphonicService is used by both confirm() and destroy(), so it is
    // injected via the constructor rather than individual method injection.
    // Constructor injection also ensures the mock binds correctly in tests.
    // -------------------------------------------------------------------------

    /**
     * Inject the AuphonicService.
     */
    public function __construct(private readonly AuphonicService $auphonic)
    {
        //
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  confirm()                                                             │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Display the clean-up confirmation page.
     *
     * Shows the episode details and a summary of what will happen,
     * requiring the user to explicitly confirm before anything runs.
     *
     * Only allowed when the episode is in `auphonic_complete` status.
     * Ownership is enforced — redirects with an error if the episode belongs
     * to another user.
     */
    public function confirm(PodcastEpisode $podcastEpisode): View|RedirectResponse
    {
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // Guard: only allow clean-up from `auphonic_complete` status.
        if ($podcastEpisode->status !== PodcastEpisodeStatus::auphonic_complete) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for clean-up.');
        }

        // Build the S3 path that will be deleted, for display on the confirmation page.
        $s3Storage = new S3_work_in_progress_audio();
        $s3Bucket  = $s3Storage->getBucket();
        $s3Key     = $s3Storage->getFolderPath($podcastEpisode->show->slug)
                     . $podcastEpisode->raw_input_audio_filename;

        return view('media_platform.podcast_studio.post_production.auphonic_processing.cleanup_confirm', [
            'episode'  => $podcastEpisode,
            's3Bucket' => $s3Bucket,
            's3Key'    => $s3Key,
        ]);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  destroy()                                                             │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Run the clean-up sequence.
     *
     * Steps:
     *   1. Download the processed MP3 from Auphonic (hard failure — aborts if
     *      this fails, nothing is deleted).
     *   2. Delete the raw WAV from the work-in-progress S3 bucket (soft failure).
     *   3. Delete the Auphonic production via the API (soft failure).
     *   4. Clear `auphonic_production_uuid` on the episode record.
     *   5. Advance status to `ready_to_upload_production_file`.
     *
     * Steps 4 and 5 always run regardless of soft failures in steps 2 and 3,
     * so the pipeline can continue even if clean-up was only partially successful.
     *
     * Ownership is enforced — redirects with an error if the episode belongs
     * to another user.
     */
    public function destroy(PodcastEpisode $podcastEpisode): RedirectResponse
    {
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // Guard: only allow clean-up from `auphonic_complete` status.
        if ($podcastEpisode->status !== PodcastEpisodeStatus::auphonic_complete) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for clean-up.');
        }

        // ── Step 1: Download the processed MP3 from Auphonic ──────────────────
        // This is the only hard failure point. If the download fails, we redirect
        // back to the confirmation page — nothing has been deleted yet, so the
        // user can safely retry.
        try {
            $this->auphonic->downloadMp3($podcastEpisode);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('CleanUpController: MP3 download from Auphonic failed.', [
                'episode_id'               => $podcastEpisode->id,
                'auphonic_production_uuid' => $podcastEpisode->auphonic_production_uuid,
                'error'                    => $e->getMessage(),
            ]);

            return redirect()
                ->route('post_production.auphonic_processing.cleanup_confirm', $podcastEpisode)
                ->with('error', 'Could not download the MP3 from Auphonic. Nothing has been deleted. Please try again. Error: ' . $e->getMessage());
        }

        $warnings = [];

        // ── Step 2: Delete the raw WAV from the work-in-progress S3 bucket ───
        try {
            $this->auphonic->deleteS3Recording($podcastEpisode);
        } catch (\Throwable $e) {
            $warnings[] = 'Could not delete the S3 recording file: ' . $e->getMessage();
            \Illuminate\Support\Facades\Log::warning('CleanUpController: S3 delete failed.', [
                'episode_id' => $podcastEpisode->id,
                'error'      => $e->getMessage(),
            ]);
        }

        // ── Step 3: Delete the Auphonic production via the API ────────────────
        if ($podcastEpisode->auphonic_production_uuid) {
            try {
                $response = $this->auphonic->deleteProduction($podcastEpisode->auphonic_production_uuid);

                // A 404 means the production was already deleted — acceptable.
                if ($response->failed() && $response->status() !== 404) {
                    $warnings[] = 'Could not delete the Auphonic production (HTTP ' . $response->status() . ').';
                    \Illuminate\Support\Facades\Log::warning('CleanUpController: Auphonic production delete failed.', [
                        'episode_id'               => $podcastEpisode->id,
                        'auphonic_production_uuid' => $podcastEpisode->auphonic_production_uuid,
                        'http_status'              => $response->status(),
                    ]);
                }

            } catch (\Throwable $e) {
                $warnings[] = 'Could not delete the Auphonic production: ' . $e->getMessage();
                \Illuminate\Support\Facades\Log::warning('CleanUpController: Auphonic production delete failed.', [
                    'episode_id' => $podcastEpisode->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        // ── Steps 4 & 5: Clear the UUID and advance the status ────────────────
        // These always run regardless of S3/Auphonic errors above, so the
        // pipeline can continue even if clean-up was only partially successful.
        $podcastEpisode->update([
            'auphonic_production_uuid' => null,
            'status'                   => PodcastEpisodeStatus::ready_to_upload_production_file,
        ]);

        // ── Redirect with appropriate flash message ───────────────────────────
        if (empty($warnings)) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('success', 'Clean-up complete. "' . $podcastEpisode->title . '" is ready for the next step.');
        }

        // Partial success — pipeline advanced but some clean-up steps failed.
        return redirect()
            ->route('post_production.auphonic_processing.index')
            ->with('success', 'Episode status has been advanced, but some clean-up steps had warnings: ' . implode(' ', $warnings));
    }
}