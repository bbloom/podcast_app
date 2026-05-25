<?php

// =============================================================================
// CleanUpController
//
// Handles the clean-up step after the production audio file has been uploaded
// to S3 and R2.
//
// STATUS CHANGE (RSS Pipeline Reorder):
//   The upload controller that runs before this one previously set the episode
//   status to `ready_to_generate_rss_feed`. In the reordered pipeline it now
//   sets `ready_to_publish_website`. The guards here are updated to match.
//
//   *** The UploadProductionAudio S3+R2 upload controller (not shown here)
//   also needs its status advancement changed from `ready_to_generate_rss_feed`
//   to `ready_to_publish_website`. Apply the same pattern there. ***
//
// Path: MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/UploadProductionAudio/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\UploadProductionAudio\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;

class CleanUpController extends Controller
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  confirm()                                                             │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Display the clean-up confirmation page.
     *
     * Clean-up is only available after the upload to S3 and R2 has succeeded.
     * At that point the status has advanced to `ready_to_publish_website`.
     */
    public function confirm(PodcastEpisode $podcastEpisode): View|RedirectResponse
    {
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_publish_website) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for clean-up.');
        }

        $expectedFilename = pathinfo($podcastEpisode->raw_input_audio_filename, PATHINFO_FILENAME) . '.mp3';
        $filePath         = storage_path('app/podcasts/' . $expectedFilename);
        $fileExists       = file_exists($filePath);

        return view('media_platform.podcasts.publishing.post_production.upload_production_audio.cleanup_confirm', [
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
     * The status remains `ready_to_publish_website` — it was set by the
     * upload controller and is not changed here.
     */
    public function destroy(PodcastEpisode $podcastEpisode): RedirectResponse
    {
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_publish_website) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for clean-up.');
        }

        $expectedFilename = pathinfo($podcastEpisode->raw_input_audio_filename, PATHINFO_FILENAME) . '.mp3';
        $filePath         = storage_path('app/podcasts/' . $expectedFilename);

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
            \Illuminate\Support\Facades\Log::info('CleanUpController (UploadProductionAudio): File not found during clean-up — already removed.', [
                'episode_id' => $podcastEpisode->id,
                'file'       => $filePath,
            ]);
        }

        if ($warning) {
            return redirect()
                ->route('post_production.upload_production_audio.done', $podcastEpisode)
                ->with('warning', $warning);
        }

        return redirect()->route('post_production.upload_production_audio.done', $podcastEpisode);
    }
}