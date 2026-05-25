<?php

// =============================================================================
// PrepareTriggerBuildsController
//
// Bridge controller between the post-production dashboard Continue button
// (for episodes in `website_published` status) and the TriggerBuilds flow.
//
// TriggerBuildsController operates at the show level ({podcastShow}), but
// `postProductionShowRoute()` returns episode-level routes ({podcastEpisode}).
// This controller resolves the mismatch by:
//   1. Accepting the episode via route model binding.
//   2. Storing the episode ID in the session for TriggerBuildsController
//      to consume after firing deploy hooks.
//   3. Redirecting to the show-level TriggerBuilds select page.
//
// This is also the entry point when the user returns to the pipeline via
// the dashboard Continue button from `website_published` status.
//
// Path: MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/PublishOnWebsite/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

class PrepareTriggerBuildsController extends Controller
{
    /**
     * Store the episode ID in the session and redirect to TriggerBuilds.
     *
     * TriggerBuildsController::trigger() reads the session key after firing
     * hooks and uses it to advance the episode to `build_triggered` before
     * redirecting to BuildConfirmation.
     */
    public function __invoke(PodcastEpisode $podcastEpisode): RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        if ($podcastEpisode->status !== PodcastEpisodeStatus::website_published) {
            return redirect()
                ->route($podcastEpisode->status->postProductionShowRoute(), $podcastEpisode)
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status.');
        }

        // ── Store episode ID for TriggerBuildsController ──────────────────────
        session(['build_confirmation.pending_episode_id' => $podcastEpisode->id]);

        return redirect()->route('post_production.trigger_builds.select', $podcastEpisode->show);
    }
}