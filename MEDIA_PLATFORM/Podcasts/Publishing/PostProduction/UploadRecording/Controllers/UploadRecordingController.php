<?php

namespace MediaPlatform\Podcasts\Publishing\PostProduction\UploadRecording\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Publishing\PostProduction\UploadRecording\Exceptions\UploadRecordingException;
use MediaPlatform\Podcasts\Publishing\PostProduction\UploadRecording\Services\UploadRecordingService;

class UploadRecordingController extends Controller
{
    // -------------------------------------------------------------------------
    // Session key used to pass the S3 object key between store() and
    // complete(). Keeping it as a constant avoids magic strings scattered
    // across methods.
    // -------------------------------------------------------------------------
    private const SESSION_KEY = 'upload_recording.pending_key';

    // -------------------------------------------------------------------------
    // The service is used by all four methods, so it is injected via the
    // constructor rather than individual method injection.
    // -------------------------------------------------------------------------

    /**
     * Inject the UploadRecordingService.
     */
    public function __construct(private readonly UploadRecordingService $service)
    {
        //
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  CONTROLLER METHODS                                                    ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  index()                                                               │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * List all episodes belonging to the authenticated user that are ready
     * to have their recording uploaded to S3.
     */
    public function index(): View
    {
        $episodes = PodcastEpisode::forUser(auth()->id())
            ->withStatus(PodcastEpisodeStatus::ready_to_upload_recording)
            ->orderByScheduledDate()
            ->with('show')
            ->get();

        return view('media_platform.podcasts.publishing.post_production.upload_recording.index', [
            'episodes' => $episodes,
        ]);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  show()                                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Show the upload page for a specific episode.
     * Redirects with an error if the episode does not belong to the
     * authenticated user, or if the episode is not in the correct status.
     */
    public function show(PodcastEpisode $episode): View|RedirectResponse
    {
        if ($episode->user_id !== auth()->id()) {
            return redirect()->route('post_production.upload_recording.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        if ($episode->status !== PodcastEpisodeStatus::ready_to_upload_recording) {
            return redirect()->route('post_production.upload_recording.index')
                ->with('error', 'That episode is not ready for upload. Please set the episode status to "Ready to Upload Recording" first.');
        }

        return view('media_platform.podcasts.publishing.post_production.upload_recording.show', [
            'episode' => $episode->load('show'),
        ]);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  store()                                                               │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Generate a pre-signed S3 PUT URL for the browser to upload the WAV file
     * directly to S3.
     *
     * The S3 object key is stored in the session — it is not returned to the
     * browser — so that complete() has no user-supplied input to trust.
     *
     * Returns JSON { url } on success.
     * Returns JSON { error } on failure.
     */
    public function store(PodcastEpisode $episode, Request $request): JsonResponse
    {
        if ($episode->user_id !== auth()->id()) {
            return response()->json(['error' => 'You do not have permission to access that episode.'], 403);
        }

        if ($episode->status !== PodcastEpisodeStatus::ready_to_upload_recording) {
            return response()->json(['error' => 'That episode is not ready for upload.'], 422);
        }

        $request->validate([
            'filename' => ['required', 'string', 'regex:/\.wav$/i'],
        ]);

        try {
            $key = $this->service->buildKey($episode, $request->filename);
            $url = $this->service->generatePresignedUrl($episode, $request->filename);

            // Store the key server-side. complete() will read it from here —
            // nothing sensitive passes through the browser.
            session()->put(self::SESSION_KEY, $key);

            return response()->json(['url' => $url]);

        } catch (UploadRecordingException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  complete()                                                            │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Called by Alpine after the browser has successfully PUT the file to S3.
     *
     * Reads the S3 key from the session (stored by store()), confirms the file
     * actually exists in S3 via a HeadObject call, then advances the episode
     * status to ready_for_auphonic and records the filename.
     *
     * Redirects to the post-production dashboard on success.
     * Redirects back with an error on any failure.
     */
    public function complete(PodcastEpisode $episode, Request $request): RedirectResponse
    {
        if ($episode->user_id !== auth()->id()) {
            return redirect()->route('post_production.upload_recording.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        if ($episode->status !== PodcastEpisodeStatus::ready_to_upload_recording) {
            return redirect()->route('post_production.upload_recording.index')
                ->with('error', 'That episode is not ready for upload.');
        }

        // Guard: complete() must always be preceded by store(). If the session
        // key is missing the user has somehow called this endpoint directly
        // without going through the upload flow.
        $key = session()->get(self::SESSION_KEY);

        if (! $key) {
            return redirect()->route('post_production.upload_recording.show', $episode)
                ->with('error', 'No pending upload found. Please start the upload process again.');
        }

        try {
            $this->service->confirmFileExists($episode, $key);
        } catch (UploadRecordingException $e) {
            return redirect()->route('post_production.upload_recording.show', $episode)
                ->with('error', $e->getMessage());
        }

        // File confirmed in S3 — record the filename and advance the status.
        $episode->update([
            'raw_input_audio_filename' => basename($key),
            'status'                   => PodcastEpisodeStatus::ready_for_auphonic,
        ]);

        // Clear the session key — this upload flow is complete.
        session()->forget(self::SESSION_KEY);

        return redirect()->route('post_production.dashboard')
            ->with('success', 'Recording uploaded successfully. The episode is now ready for Auphonic.');
    }
}