<?php

// =============================================================================
// CleanUpController
//
// Handles the clean-up step after the production audio file has been uploaded
// to S3 and R2.
//
// Clean-up deletes the MP3 from local server storage (storage_path('podcasts/'))
// and advances the episode status to `ready_to_generate_rss_feed`.
//
// A confirmation page is shown before anything is deleted, consistent with
// the no-modals convention — destructive actions always require a dedicated
// confirmation page.
//
// Steps performed on confirm:
//   1. Delete the MP3 from storage_path('podcasts/').
//   2. Advance the episode status to `ready_to_generate_rss_feed`.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/UploadProductionAudio/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\UploadProductionAudio\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;

class CleanUpController extends Controller
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  confirm()                                                             │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Display the clean-up confirmation page.
     *
     * Shows the episode details, the filename that will be deleted, and
     * whether the file currently exists on the server.
     *
     * Only allowed when the episode is in `ready_to_upload_production_file` status.
     * The upload to S3 and R2 must have succeeded (and the status advanced) before
     * clean-up is available. Clean-up is a separate step reached from the index.
     */
    public function confirm(PodcastEpisode $podcastEpisode): View|RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        // Clean-up is only available after the upload to S3 and R2 has succeeded.
        // At that point the status has advanced to `ready_to_generate_rss_feed`.
        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_generate_rss_feed) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for clean-up.');
        }

        $expectedFilename = pathinfo($podcastEpisode->raw_input_audio_filename, PATHINFO_FILENAME) . '.mp3';
        $filePath         = storage_path('podcasts/' . $expectedFilename);
        $fileExists       = file_exists($filePath);

        return view('media_platform.podcast_studio.post_production.upload_production_audio.cleanup_confirm', [
            'episode'          => $podcastEpisode->load('show'),
            'expectedFilename' => $expectedFilename,
            'fileExists'       => $fileExists,
        ]);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  destroy()                                                             │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Run the clean-up sequence.
     *
     * Deletes the production MP3 from local server storage.
     * If the file does not exist, that is treated as a soft failure —
     * the status still advances since the upload to S3 and R2 is already done.
     */
    public function destroy(PodcastEpisode $podcastEpisode): RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_generate_rss_feed) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for clean-up.');
        }

        $expectedFilename = pathinfo($podcastEpisode->raw_input_audio_filename, PATHINFO_FILENAME) . '.mp3';
        $filePath         = storage_path('podcasts/' . $expectedFilename);

        // ── Delete the local MP3 ──────────────────────────────────────────────
        // Soft failure — if the file is missing it was already cleaned up or
        // never arrived. The status still advances.
        $warning = null;

        if (file_exists($filePath)) {
            if (! unlink($filePath)) {
                $warning = 'Could not delete "' . $expectedFilename . '" from the server. You may need to remove it manually.';

                \Illuminate\Support\Facades\Log::warning('CleanUpController (UploadProductionAudio): Could not delete local MP3.', [
                    'episode_id' => $podcastEpisode->id,
                    'file'       => $filePath,
                ]);
            }
        } else {
            // File not found — log for awareness but do not block the pipeline.
            \Illuminate\Support\Facades\Log::info('CleanUpController (UploadProductionAudio): File not found during clean-up — already removed.', [
                'episode_id' => $podcastEpisode->id,
                'file'       => $filePath,
            ]);
        }

        // ── Redirect with appropriate flash message ───────────────────────────
        if ($warning) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('success', 'Clean-up complete with a warning: ' . $warning);
        }

        return redirect()
            ->route('post_production.upload_production_audio.index')
            ->with('success', 'Clean-up complete. "' . $podcastEpisode->title . '" is ready for RSS feed generation.');
    }
}