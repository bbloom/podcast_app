<?php

// =============================================================================
// WebhookStatusController
//
// Lightweight JSON endpoint polled by Alpine.js on the processing.blade.php
// page. Returns the current status of the episode so the front-end can react
// when Auphonic completes processing and the WebhookController has advanced
// the episode status to `auphonic_complete`.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/AuphonicProcessing/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\AuphonicProcessing\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;

class WebhookStatusController extends Controller
{
    /**
     * Return the current status of the episode as JSON.
     *
     * Alpine.js polls this endpoint every few seconds while the episode is
     * in `processing_at_auphonic` status. When the status advances to
     * `auphonic_complete` (set by WebhookController), the front-end shows
     * the "Ready! Click here to continue" button.
     *
     * Ownership is enforced — 403 if the episode belongs to another user.
     */
    public function __invoke(PodcastEpisode $podcastEpisode): JsonResponse
    {
        abort_if($podcastEpisode->user_id !== auth()->id(), 403);

        return response()->json([
            'status'   => $podcastEpisode->status->value,
            'complete' => $podcastEpisode->status === PodcastEpisodeStatus::auphonic_complete,
        ]);
    }
}