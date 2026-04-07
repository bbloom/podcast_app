<?php

// =============================================================================
// PublishController
//
// Handles the confirmation POST for publishing an episode on the website.
//
// Sets website_enabled = true, leaves website_publish_on as-is, and advances
// the episode status to `published`. Redirects to the index on success.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/PublishOnWebsite/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\PublishOnWebsite\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;

class PublishController extends Controller
{
    /**
     * Publish the episode on the website.
     *
     * Sets website_enabled = true and advances status to `published`.
     * website_publish_on is left unchanged — it was set at episode creation time.
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

        return redirect()
            ->route('post_production.publish_on_website.index')
            ->with('success', '"' . $podcastEpisode->title . '" is now published on the website.');
    }
}