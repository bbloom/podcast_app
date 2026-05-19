<?php

// =============================================================================
// ShowController
//
// Displays the confirmation page for publishing a specific episode on the
// website. Shows episode details and a link to the episode show page
// (target="_blank") for a final review before confirming.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/PublishOnWebsite/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

class ShowController extends Controller
{
    /**
     * Display the publish confirmation page for the given episode.
     *
     * Only available when the episode status is `ready_to_publish`.
     * A link to the episode show page (target="_blank") is provided so
     * the user can review all fields before confirming.
     */
    public function __invoke(PodcastEpisode $podcastEpisode): View|RedirectResponse
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

        return view('media_platform.podcasts.publishing.post_production.publish_on_website.show', [
            'episode' => $podcastEpisode->load('show'),
        ]);
    }
}