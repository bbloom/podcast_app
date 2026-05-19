<?php

// =============================================================================
// SubmitController
//
// Handles two actions:
//
//   show()   — displays the episode detail page with a "Submit to Auphonic"
//               button. Before rendering, lists the files in the episode's
//               S3 folder and compares against the expected filename. If the
//               file is missing, mismatched, or there are multiple files, a
//               warning is shown instead of the Submit button.
//
//   submit() — receives the form POST, calls the Auphonic API to create and
//              start a production, stores the returned UUID on the episode,
//              advances the status to `processing_at_auphonic`, and renders
//              the processing waiting view.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/AuphonicProcessing/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Services\AuphonicService;
use MediaPlatform\Podcasts\Publishing\PostProduction\CloudStorage\S3_work_in_progress_audio;

class SubmitController extends Controller
{
    // -------------------------------------------------------------------------
    // S3_work_in_progress_audio is injected via the constructor so Laravel's
    // container resolves it — this allows the mock to bind correctly in tests.
    // Direct `new` instantiation bypasses the container and makes the class
    // impossible to mock.
    // -------------------------------------------------------------------------

    /**
     * Inject the S3 storage helper and AuphonicService.
     */
    public function __construct(
        private readonly S3_work_in_progress_audio $s3Storage,
        private readonly AuphonicService $auphonic,
    ) {
        //
    }

    // -------------------------------------------------------------------------
    // S3 check status constants — passed to the view to drive conditional UI.
    // -------------------------------------------------------------------------

    // The expected file is present and is the only file in the folder.
    public const S3_STATUS_MATCH    = 'match';

    // A file is present but its name does not match raw_input_audio_filename.
    public const S3_STATUS_MISMATCH = 'mismatch';

    // More than one file is present in the folder — ambiguous, cannot confirm.
    public const S3_STATUS_MULTIPLE = 'multiple';

    // No files found in the folder at all.
    public const S3_STATUS_EMPTY    = 'empty';

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  show()                                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Display the episode detail page with a "Submit to Auphonic" button.
     *
     * Before rendering, lists the files in the episode's S3 folder and
     * compares against the expected filename (raw_input_audio_filename).
     * The result is passed to the view as $s3Status, which controls whether
     * the Submit button or a warning panel is shown.
     *
     * If the episode is already `processing_at_auphonic`, the processing
     * waiting view is rendered instead. If Auphonic has already completed,
     * the user is redirected to the complete page.
     *
     * Ownership is enforced — redirects with an error if the episode belongs
     * to another user.
     */
    public function show(PodcastEpisode $podcastEpisode): View|RedirectResponse
    {
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // If somehow the episode is already processing, show the waiting screen.
        if ($podcastEpisode->status === PodcastEpisodeStatus::processing_at_auphonic) {
            return view('media_platform.podcasts.publishing.post_production.auphonic_processing.processing', [
                'episode' => $podcastEpisode,
            ]);
        }

        // If Auphonic has already completed (webhook already fired), redirect
        // to the complete view so the user can act on it.
        if ($podcastEpisode->status === PodcastEpisodeStatus::auphonic_complete) {
            return redirect()->route('post_production.auphonic_processing.complete', $podcastEpisode);
        }

        // ── S3 file check ─────────────────────────────────────────────────────
        // List what is actually in the show's S3 folder and compare against
        // the expected filename stored on the episode record. The result drives
        // the conditional UI in the view.
        $filesInS3  = $this->s3Storage->listFiles($podcastEpisode->show->slug);
        $consoleUrl = $this->s3Storage->buildConsoleUrl($podcastEpisode->show->slug);
        $expected   = $podcastEpisode->raw_input_audio_filename;

        $s3Status = match(true) {
            count($filesInS3) === 0                                => self::S3_STATUS_EMPTY,
            count($filesInS3) > 1                                  => self::S3_STATUS_MULTIPLE,
            count($filesInS3) === 1 && $filesInS3[0] === $expected => self::S3_STATUS_MATCH,
            default                                                => self::S3_STATUS_MISMATCH,
        };

        return view('media_platform.podcasts.publishing.post_production.auphonic_processing.show', [
            'episode'         => $podcastEpisode,
            's3Status'        => $s3Status,
            'filesInS3'       => $filesInS3,
            'consoleUrl'      => $consoleUrl,
            'auphonicCredits' => $this->auphonic->fetchCredits(),
        ]);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  submit()                                                              │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Submit the episode to Auphonic for processing.
     *
     * Steps:
     *   1. Ownership check.
     *   2. Guard against double-submission.
     *   3. Call the Auphonic API (create + start production in one request).
     *   4. On success: store the production UUID, advance status, show waiting view.
     *   5. On failure: redirect back with a descriptive error message.
     *
     * Ownership is enforced — redirects with an error if the episode belongs
     * to another user.
     */
    public function submit(PodcastEpisode $podcastEpisode, AuphonicService $auphonic): RedirectResponse|View
    {
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // Guard: prevent double-submission if the episode is already processing.
        if ($podcastEpisode->status === PodcastEpisodeStatus::processing_at_auphonic) {
            return view('media_platform.podcasts.publishing.post_production.auphonic_processing.processing', [
                'episode' => $podcastEpisode,
            ]);
        }

        // Guard: only allow submission from `ready_for_auphonic` status.
        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_for_auphonic) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not ready for Auphonic submission.');
        }

        // ── Call the Auphonic API ─────────────────────────────────────────────
        try {
            $response = $auphonic->submitProduction($podcastEpisode);
        } catch (\Throwable $e) {
            // Network-level failure (connection refused, DNS failure, timeout, etc.)
            return redirect()
                ->route('post_production.auphonic_processing.show', $podcastEpisode)
                ->with('error', 'Could not reach the Auphonic API. Please check your connection and try again. Error: ' . $e->getMessage());
        }

        // ── Handle API error responses ────────────────────────────────────────
        if ($response->failed()) {
            $body        = $response->json();
            $errorDetail = $body['error_message'] ?? ('HTTP ' . $response->status());

            return redirect()
                ->route('post_production.auphonic_processing.show', $podcastEpisode)
                ->with('error', "Auphonic returned an error: {$errorDetail}. Please check the filename in S3 and try again.");
        }

        // ── Extract the Auphonic production UUID ──────────────────────────────
        $body                   = $response->json();
        $auphonicProductionUuid = $body['data']['uuid'] ?? null;

        if (! $auphonicProductionUuid) {
            return redirect()
                ->route('post_production.auphonic_processing.show', $podcastEpisode)
                ->with('error', 'Auphonic did not return a production UUID. The submission may have failed. Please try again.');
        }

        // ── Persist the UUID and advance the status ───────────────────────────
        $podcastEpisode->update([
            'auphonic_production_uuid' => $auphonicProductionUuid,
            'status'                   => PodcastEpisodeStatus::processing_at_auphonic,
        ]);

        // ── Render the waiting screen ─────────────────────────────────────────
        return view('media_platform.podcasts.publishing.post_production.auphonic_processing.processing', [
            'episode' => $podcastEpisode->fresh(),
        ]);
    }
}