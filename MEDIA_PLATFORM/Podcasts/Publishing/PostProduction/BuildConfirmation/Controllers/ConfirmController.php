<?php

// =============================================================================
// ConfirmController — BuildConfirmation
//
// Advances the episode status from `build_triggered` to
// `ready_to_generate_rss_feed` and redirects to GenerateRssFeed Step 1.
//
// Reached in two ways:
//   1. Automatically — Alpine.js detects build success via the build status
//      polling endpoint and presents a "Continue" link the user clicks.
//   2. Manually — the user has verified the build is complete in their
//      Cloudflare dashboard and clicks "Confirm manually".
//
// The controller is idempotent: if the episode has already advanced past
// `build_triggered` (e.g. the user double-clicks), it redirects to the
// correct step for the current status rather than erroring.
//
// Path: MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/BuildConfirmation/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\BuildConfirmation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

class ConfirmController extends Controller
{
    /**
     * Advance the episode to `ready_to_generate_rss_feed` and continue.
     */
    public function __invoke(PodcastEpisode $podcastEpisode): RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Idempotency — already advanced past build_triggered ───────────────
        // If the episode has moved on (e.g. double-click, back button), redirect
        // to wherever it currently belongs rather than treating this as an error.
        if ($podcastEpisode->status === PodcastEpisodeStatus::ready_to_generate_rss_feed) {
            return redirect()
                ->route('post_production.generate_rss_feed.step1', $podcastEpisode);
        }

        // ── Status guard ──────────────────────────────────────────────────────
        if ($podcastEpisode->status !== PodcastEpisodeStatus::build_triggered) {
            return redirect()
                ->route($podcastEpisode->status->postProductionShowRoute(), $podcastEpisode)
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for build confirmation.');
        }

        // ── Advance status ────────────────────────────────────────────────────
        $podcastEpisode->update([
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed,
        ]);

        return redirect()
            ->route('post_production.generate_rss_feed.step1', $podcastEpisode);
    }
}