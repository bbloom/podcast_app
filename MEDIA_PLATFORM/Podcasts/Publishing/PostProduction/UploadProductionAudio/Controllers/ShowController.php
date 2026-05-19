<?php

// =============================================================================
// ShowController
//
// Displays the decision page for a specific episode.
//
// The user must decide whether the production MP3 is already on the server
// (in storage_path('app/podcasts/')) or still on their local machine.
//
// For admin users, a list of files currently in storage_path('app/podcasts/') is
// displayed as a convenience — filenames, sizes, and modified timestamps.
// This list is informational only. Because files are not scoped per user,
// the admin makes the Yes/No decision manually.
//
// Non-admin users see the Yes/No decision buttons only.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/UploadProductionAudio/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\UploadProductionAudio\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;

class ShowController extends Controller
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  __invoke()                                                            │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Display the decision page for the given episode.
     *
     * Checks ownership and status, then builds the file listing from
     * storage_path('app/podcasts/') for display to admin users.
     */
    public function __invoke(PodcastEpisode $podcastEpisode): View|RedirectResponse
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

        // ── Build file listing for admin users ────────────────────────────────
        // Lists all files in storage_path('app/podcasts/'). This cannot be scoped
        // per user — the listing is for convenience only. The admin decides
        // manually whether their file is present.
        $serverFiles = $this->getServerFiles();

        // ── Derive the expected MP3 filename from the episode record ──────────
        $expectedFilename = pathinfo($podcastEpisode->raw_input_audio_filename, PATHINFO_FILENAME) . '.mp3';

        return view('media_platform.podcasts.publishing.post_production.upload_production_audio.show', [
            'episode'          => $podcastEpisode->load('show'),
            'serverFiles'      => $serverFiles,
            'expectedFilename' => $expectedFilename,
        ]);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  PRIVATE METHODS                                                       ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  getServerFiles()                                                      │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Returns an array of file metadata for all files in storage_path('app/podcasts/').
     *
     * Each entry contains:
     *   - name     (string)  — the filename
     *   - size     (string)  — human-readable filesize (e.g. "45.2 MB")
     *   - modified (string)  — formatted modified timestamp (e.g. "Apr 5, 2026 14:32")
     *
     * Returns an empty array if the directory does not exist or contains no files.
     */
    private function getServerFiles(): array
    {
        $dir = storage_path('app/podcasts');

        if (! is_dir($dir)) {
            return [];
        }

        $files = [];

        foreach (scandir($dir) as $filename) {
            // Skip directory entries.
            if ($filename === '.' || $filename === '..') {
                continue;
            }

            $path = $dir . '/' . $filename;

            // Skip subdirectories — we only want files.
            if (! is_file($path)) {
                continue;
            }

            $bytes = filesize($path);

            $files[] = [
                'name'     => $filename,
                'size'     => $this->formatBytes($bytes),
                'modified' => date('M j, Y H:i', filemtime($path)),
            ];
        }

        return $files;
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  formatBytes()                                                         │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Converts a byte count to a human-readable string (KB, MB, GB).
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_073_741_824) {
            return number_format($bytes / 1_073_741_824, 2) . ' GB';
        }

        if ($bytes >= 1_048_576) {
            return number_format($bytes / 1_048_576, 2) . ' MB';
        }

        if ($bytes >= 1_024) {
            return number_format($bytes / 1_024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}