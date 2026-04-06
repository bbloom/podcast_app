<?php

// =============================================================================
// ResubmitController
//
// Handles re-submission of an episode to Auphonic when the user is unhappy
// with the processed MP3 (wrong output, bad preset result, etc.).
//
// Two actions:
//
//   confirm()  — displays a confirmation page so the user can review what
//                will happen before the destructive re-submit runs.
//                Cancel returns the user to the correct page based on the
//                episode's current status.
//
//   resubmit() — performs the destructive sequence:
//                1. Delete the existing Auphonic production via the API.
//                2. Clear the `auphonic_production_uuid` on the episode record.
//                3. Create and start a brand new Auphonic production.
//                4. Store the new production UUID on the episode.
//                5. Reset the episode status to `processing_at_auphonic`.
//                6. Render the processing waiting view.
//
// Can be triggered from either the `auphonic_complete` screen (user reviewed
// and decided the MP3 is not acceptable) or from the `processing_at_auphonic`
// screen (Auphonic returned an error via webhook).
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

class ResubmitController extends Controller
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  confirm()                                                             │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Display the re-submission confirmation page.
     *
     * Shows the episode details and a summary of what will happen, requiring
     * the user to explicitly confirm before the destructive re-submit runs.
     *
     * The cancel link is determined by the episode's current status, so the
     * user is always returned to the correct page if they change their mind:
     *   - auphonic_complete      → complete page
     *   - processing_at_auphonic → show (processing waiting) page
     *
     * Only allowed from `auphonic_complete` or `processing_at_auphonic` status.
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

        $allowedStatuses = [
            PodcastEpisodeStatus::auphonic_complete,
            PodcastEpisodeStatus::processing_at_auphonic,
        ];

        if (! in_array($podcastEpisode->status, $allowedStatuses)) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" cannot be re-submitted from its current status.');
        }

        // Determine where the cancel link should return the user, based on
        // the episode's current status — the episode is the source of truth.
        $cancelRoute = match($podcastEpisode->status) {
            PodcastEpisodeStatus::auphonic_complete      => route('post_production.auphonic_processing.complete', $podcastEpisode),
            PodcastEpisodeStatus::processing_at_auphonic => route('post_production.auphonic_processing.show', $podcastEpisode),
        };

        return view('media_platform.podcast_studio.post_production.auphonic_processing.resubmit_confirm', [
            'episode'     => $podcastEpisode,
            'cancelRoute' => $cancelRoute,
        ]);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  resubmit()                                                            │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Delete the existing Auphonic production and submit a new one.
     *
     * Ownership is enforced — redirects with an error if the episode belongs
     * to another user.
     */
    public function resubmit(PodcastEpisode $podcastEpisode, AuphonicService $auphonic): View|RedirectResponse
    {
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // Guard: only allow re-submit from `auphonic_complete` or
        // `processing_at_auphonic` (error scenario) statuses.
        $allowedStatuses = [
            PodcastEpisodeStatus::auphonic_complete,
            PodcastEpisodeStatus::processing_at_auphonic,
        ];

        if (! in_array($podcastEpisode->status, $allowedStatuses)) {
            return redirect()
                ->route('post_production.auphonic_processing.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" cannot be re-submitted from its current status.');
        }

        // ── Step 1: Delete the existing Auphonic production ───────────────────
        if ($podcastEpisode->auphonic_production_uuid) {
            try {
                $deleteResponse = $auphonic->deleteProduction($podcastEpisode->auphonic_production_uuid);

                // A 404 from Auphonic means the production no longer exists —
                // that is acceptable (it may have already been deleted manually).
                // Any other non-success response is treated as a warning but we
                // proceed anyway, since the goal is to clear and re-start.
                if ($deleteResponse->failed() && $deleteResponse->status() !== 404) {
                    \Illuminate\Support\Facades\Log::warning('Auphonic delete returned a non-success status during re-submit.', [
                        'episode_id'               => $podcastEpisode->id,
                        'auphonic_production_uuid' => $podcastEpisode->auphonic_production_uuid,
                        'http_status'              => $deleteResponse->status(),
                    ]);
                }
            } catch (\Throwable $e) {
                // Network failure during delete — log but continue.
                // We still want to attempt a fresh submission.
                \Illuminate\Support\Facades\Log::warning('Could not delete Auphonic production during re-submit.', [
                    'episode_id' => $podcastEpisode->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        // ── Step 2: Clear the old UUID from the episode record ────────────────
        $podcastEpisode->update([
            'auphonic_production_uuid' => null,
        ]);

        // ── Step 3: Submit a new Auphonic production ──────────────────────────
        try {
            $response = $auphonic->submitProduction($podcastEpisode);
        } catch (\Throwable $e) {
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
                ->with('error', "Auphonic returned an error during re-submit: {$errorDetail}. Please check the filename in S3 and try again.");
        }

        // ── Step 4: Store the new UUID and advance status ─────────────────────
        $body                      = $response->json();
        $newAuphonicProductionUuid = $body['data']['uuid'] ?? null;

        if (! $newAuphonicProductionUuid) {
            return redirect()
                ->route('post_production.auphonic_processing.show', $podcastEpisode)
                ->with('error', 'Auphonic did not return a production UUID during re-submit. Please try again.');
        }

        $podcastEpisode->update([
            'auphonic_production_uuid' => $newAuphonicProductionUuid,
            'status'                   => PodcastEpisodeStatus::processing_at_auphonic,
        ]);

        // ── Step 5: Render the waiting screen ─────────────────────────────────
        return view('media_platform.podcast_studio.post_production.auphonic_processing.processing', [
            'episode' => $podcastEpisode->fresh(),
        ]);
    }
}