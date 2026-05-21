<?php

// =============================================================================
// WebhookController
//
// Receives the HTTP POST callback from Auphonic when a production finishes.
//
// Auphonic sends three fields in the POST body:
//   - uuid         : the Auphonic production UUID
//   - status       : 3 = Done, 2 = Error
//   - status_string: "Done" or "Error"
//
// Auphonic will retry with multipart/form-data if the first
// application/x-www-form-urlencoded request fails. Both formats are handled
// natively by Laravel's request() helper.
//
// This route is excluded from CSRF verification in bootstrap/app.php.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/AuphonicProcessing/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;

class WebhookController extends Controller
{
    // -------------------------------------------------------------------------
    // Auphonic status codes sent in the webhook payload.
    // -------------------------------------------------------------------------
    private const AUPHONIC_STATUS_DONE  = 3;
    private const AUPHONIC_STATUS_ERROR = 2;

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  __invoke()                                                            │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Handle the incoming Auphonic webhook POST request.
     *
     * Steps:
     *   1. Extract and validate the production UUID and status from the payload.
     *   2. Look up the episode by the Auphonic production UUID.
     *   3. If status is Done: advance to `auphonic_complete`.
     *   4. If status is Error: log and return a 200 so Auphonic does not retry
     *      indefinitely — the user will see the episode stuck in
     *      `processing_at_auphonic` and can re-submit manually.
     *   5. Return HTTP 200 — Auphonic expects a 200 to confirm receipt.
     *      Any non-200 will trigger a retry with multipart/form-data.
     */
    public function __invoke(Request $request): Response
    {
        $auphonicProductionUuid = $request->input('uuid');
        $auphonicStatus         = (int) $request->input('status');
        $auphonicStatusString   = $request->input('status_string');

        // ── Validate the payload has a UUID ───────────────────────────────────
        if (! $auphonicProductionUuid) {
            // Return 200 even on bad payloads so Auphonic does not retry.
            // A missing UUID means there is nothing we can act on.
            return response('Missing UUID in webhook payload.', 200);
        }

        // ── Look up the episode by the Auphonic production UUID ───────────────
        $episode = PodcastEpisode::where('auphonic_production_uuid', $auphonicProductionUuid)->first();

        if (! $episode) {
            // Unknown UUID — possibly a stale or duplicate webhook call.
            // Return 200 to prevent Auphonic from retrying.
            return response('No episode found for the given Auphonic production UUID.', 200);
        }

        // ── Guard: only act if the episode is currently processing ─────────────
        // If the webhook fires twice (Auphonic retries), we should not
        // overwrite a status that has already advanced.
        if ($episode->status !== PodcastEpisodeStatus::processing_at_auphonic) {
            return response('Episode is not in processing_at_auphonic status. Webhook ignored.', 200);
        }

        // ── Handle Auphonic "Error" status ────────────────────────────────────
        if ($auphonicStatus === self::AUPHONIC_STATUS_ERROR) {
            // We leave the episode in `processing_at_auphonic` so it remains
            // visible in the dashboard. The user can investigate in the Auphonic
            // console and re-submit from the UI.
            // Log the error for visibility.
            \Illuminate\Support\Facades\Log::error('Auphonic processing failed via webhook.', [
                'auphonic_production_uuid' => $auphonicProductionUuid,
                'episode_id'               => $episode->id,
                'episode_title'            => $episode->title,
                'auphonic_status_string'   => $auphonicStatusString,
            ]);

            return response('Auphonic reported an error. Episode left in processing_at_auphonic for manual review.', 200);
        }

        // ── Handle Auphonic "Done" status ─────────────────────────────────────
        if ($auphonicStatus === self::AUPHONIC_STATUS_DONE) {
            $episode->update([
                'status' => PodcastEpisodeStatus::auphonic_complete,
            ]);

            return response('OK', 200);
        }

        // ── Unknown status ────────────────────────────────────────────────────
        // Return 200 to prevent retries, but log for visibility.
        \Illuminate\Support\Facades\Log::warning('Auphonic webhook received an unrecognised status code.', [
            'auphonic_production_uuid' => $auphonicProductionUuid,
            'auphonic_status'          => $auphonicStatus,
            'auphonic_status_string'   => $auphonicStatusString,
        ]);

        return response('Unrecognised Auphonic status code.', 200);
    }
}