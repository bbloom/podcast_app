<?php

// =============================================================================
// ManualUploadController
//
// Handles the manual upload flow for when the production MP3 is on the user's
// local machine rather than already on the server.
//
// The uploaded file must have the same base name (stem) as the episode's
// raw_input_audio_filename field — only the extension changes (.wav → .mp3).
// If the wrong file is uploaded, the user is redirected back with a message
// telling them exactly which filename is expected.
//
// On success the file is saved to storage_path('podcasts/{filename}'), which
// is the same location used by the Auphonic download. From this point on,
// the UploadToStorageController handles the rest of the pipeline identically
// regardless of how the file arrived on the server.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/UploadProductionAudio/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\UploadProductionAudio\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;

class ManualUploadController extends Controller
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  show()                                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Display the manual upload form for the given episode.
     *
     * Shows the expected filename so the user knows exactly which file to select.
     */
    public function show(PodcastEpisode $podcastEpisode): View|RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_upload_production_file) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for production audio upload.');
        }

        // ── Derive the expected MP3 filename ──────────────────────────────────
        $expectedFilename = pathinfo($podcastEpisode->raw_input_audio_filename, PATHINFO_FILENAME) . '.mp3';

        return view('media_platform.podcast_studio.post_production.upload_production_audio.manual_upload', [
            'episode'          => $podcastEpisode->load('show'),
            'expectedFilename' => $expectedFilename,
        ]);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  store()                                                               │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Handle the manual file upload.
     *
     * Validates that the uploaded file's base name matches the stem of the
     * episode's raw_input_audio_filename. If the filename is wrong, redirects
     * back with a message specifying the expected filename.
     *
     * On success, saves the file to storage_path('podcasts/{expectedFilename}')
     * and redirects to the upload-to-storage step.
     */
    public function store(PodcastEpisode $podcastEpisode, Request $request): RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_upload_production_file) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for production audio upload.');
        }

        // ── Basic validation ──────────────────────────────────────────────────
        $request->validate([
            'production_file' => ['required', 'file', 'mimes:mpga,mp3'],
        ]);

        // ── Filename validation ───────────────────────────────────────────────
        // The uploaded file's base name must match the stem of raw_input_audio_filename.
        // Only the extension is allowed to differ (.wav → .mp3).
        $expectedStem     = pathinfo($podcastEpisode->raw_input_audio_filename, PATHINFO_FILENAME);
        $expectedFilename = $expectedStem . '.mp3';
        $uploadedFilename = $request->file('production_file')->getClientOriginalName();
        $uploadedStem     = pathinfo($uploadedFilename, PATHINFO_FILENAME);

        if ($uploadedStem !== $expectedStem) {
            return redirect()
                ->route('post_production.upload_production_audio.manual_upload', $podcastEpisode)
                ->with('error', 'Wrong file uploaded. The filename does not match. Please upload: ' . $expectedFilename);
        }

        // ── Save the file to storage_path('podcasts/') ────────────────────────
        // The destination directory is created automatically if it does not exist.
        $destinationDir = storage_path('podcasts');

        if (! is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, recursive: true);
        }

        $request->file('production_file')->move($destinationDir, $expectedFilename);

        // ── Redirect to the upload-to-storage step ────────────────────────────
        return redirect()
            ->route('post_production.upload_production_audio.upload_to_storage', $podcastEpisode)
            ->with('success', 'File uploaded to server successfully. Ready to upload to S3 and R2.');
    }
}