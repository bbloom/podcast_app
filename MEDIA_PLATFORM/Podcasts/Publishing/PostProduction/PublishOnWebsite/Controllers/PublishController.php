<?php

// =============================================================================
// PublishController
//
// Handles the confirmation POST for publishing an episode on the website.
//
// RSS PIPELINE REORDER CHANGES:
//   - Status advanced to `website_published` (was `published`).
//   - Episode ID stored in session for TriggerBuildsController to consume
//     after firing deploy hooks — it uses this to advance the episode to
//     `build_triggered` and redirect to BuildConfirmation.
//   - Future-date branching removed. The static site must be built before
//     RSS generation regardless of publish date, so all episodes now proceed
//     to TriggerBuilds immediately after publishing.
//
// Path: MEDIA_PLATFORM/Podcasts/PostProduction/PublishOnWebsite/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

class PublishController extends Controller
{
    /**
     * Publish the episode on the website and advance to `website_published`.
     *
     * Sets website_enabled = true. Does not set `published` — that happens
     * after GenerateRssFeed completes. The pipeline continues:
     *   website_published → TriggerBuilds → build_triggered
     *   → BuildConfirmation → ready_to_generate_rss_feed → GenerateRssFeed
     *   → published
     */
    public function publish(PodcastEpisode $podcastEpisode): RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.publish_on_website.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        // Accept both the new pipeline status and the legacy status for any
        // episodes that entered the old pipeline order.
        $acceptableStatuses = [
            PodcastEpisodeStatus::ready_to_publish_website,
            PodcastEpisodeStatus::ready_to_publish,
        ];

        if (! in_array($podcastEpisode->status, $acceptableStatuses)) {
            return redirect()
                ->route('post_production.publish_on_website.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for publishing.');
        }

        // ── Publish ───────────────────────────────────────────────────────────
        $podcastEpisode->update([
            'website_enabled' => true,
            'status'          => PodcastEpisodeStatus::website_published,
        ]);

        // ── Store episode ID for TriggerBuildsController ──────────────────────
        // TriggerBuildsController operates at show level and has no episode
        // parameter. The session bridges the two controllers so that after
        // deploy hooks are fired, the episode is advanced to `build_triggered`
        // and the user is sent to BuildConfirmation.
        session(['build_confirmation.pending_episode_id' => $podcastEpisode->id]);

        return redirect()
            ->route('post_production.trigger_builds.select', $podcastEpisode->show)
            ->with('success', '"' . $podcastEpisode->title . '" is now published on the website. Select the builds to trigger.');
    }
}