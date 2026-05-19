<?php

// =============================================================================
// PublishController
//
// Handles the confirmation POST for publishing an episode on the website.
//
// Sets website_enabled = true, leaves website_publish_on as-is, and advances
// the episode status to `published`.
//
// After publishing:
//   - If website_publish_on <= today: redirects to "Trigger Static Site Builds"
//     so the user can immediately trigger the relevant front-end builds.
//   - If website_publish_on is a future date: redirects to the index with a
//     success message. The episode is published in the database but will not
//     appear on the site until the date arrives — triggering a build now
//     would have no effect.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/PublishOnWebsite/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

class PublishController extends Controller
{
    /**
     * Publish the episode on the website.
     *
     * Sets website_enabled = true and advances status to `published`.
     * website_publish_on is left unchanged — it was set at episode creation time.
     *
     * Redirects to "Trigger Static Site Builds" if the episode is due today
     * or in the past. Redirects to the index for future-dated episodes.
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
        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_publish) {
            return redirect()
                ->route('post_production.publish_on_website.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for publishing.');
        }

        // ── Publish ───────────────────────────────────────────────────────────
        $podcastEpisode->update([
            'website_enabled' => true,
            'status'          => PodcastEpisodeStatus::published,
        ]);

        // ── Redirect based on publish date ────────────────────────────────────
        // If the episode is due today or in the past, it is immediately visible
        // on the website — offer to trigger static site builds now.
        // If the publish date is in the future, the episode will not appear yet
        // so triggering a build would be pointless.
        if ($podcastEpisode->website_publish_on->lte(now()->startOfDay())) {
            return redirect()
                ->route('post_production.trigger_builds.select', $podcastEpisode->show)
                ->with('success', '"' . $podcastEpisode->title . '" is now published. Select the builds to trigger.');
        }

        return redirect()
            ->route('post_production.publish_on_website.index')
            ->with('success', '"' . $podcastEpisode->title . '" is published and scheduled for '
                . $podcastEpisode->website_publish_on->format('M j, Y') . '. '
                . 'Trigger a build on or after that date for it to appear on the site.');
    }
}