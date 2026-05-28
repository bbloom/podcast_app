<?php

// =============================================================================
// ReplaceRecordingController
//
// Resets an episode back to `ready_to_upload_recording` so the user can
// re-upload the correct WAV file via the UploadRecording flow.
//
// This is triggered from the show.blade.php S3 warning panel when the file
// in S3 is missing, mismatched, or ambiguous.
//
// The wrong file in S3 (if any) is NOT deleted here — it cannot be safely
// identified when there are multiple files. The user handles S3 clean-up
// manually via the AWS console link provided on the show page.
//
// Path: MEDIA_PLATFORM/Podcasts/PostProduction/AuphonicProcessing/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;

class ReplaceRecordingController extends Controller
{
    /**
     * Reset the episode status to `ready_to_upload_recording` and redirect
     * to the upload flow so the user can re-upload the correct recording.
     *
     * Only allowed from `ready_for_auphonic` status — the status the episode
     * holds when it is on the show page awaiting Auphonic submission.
     *
     * Ownership is enforced — redirects with an error if the episode belongs
     * to another user.
     */
    public function __invoke(PodcastEpisode $podcastEpisode): RedirectResponse
    {
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // Guard: only allow from `ready_for_auphonic` status.
        // The show page is only reachable in this status, so any other status
        // here means something unexpected has happened.
        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_for_auphonic) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status to replace its recording.');
        }

        // Reset status back to the beginning of the upload flow.
        // raw_input_audio_filename is intentionally left unchanged — it holds
        // the correct expected filename set during episode creation.
        $podcastEpisode->update([
            'status' => PodcastEpisodeStatus::ready_to_upload_recording,
        ]);

        return redirect()
            ->route('post_production.upload_recording.show', $podcastEpisode)
            ->with('success', 'Please delete the incorrect file from S3, then re-upload the correct recording.');
    }
}