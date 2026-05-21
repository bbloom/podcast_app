<?php

// =============================================================================
// Step1Controller
//
// Step 1 of the Generate RSS Feed wizard.
//
// Displays the episode review page — a link to the episode's show page so the
// user can give all fields a once-over, plus the enclosure length (filesize)
// and duration displayed prominently for manual verification.
//
// On confirm (POST), the episode is stored in the wizard session and the user
// is forwarded to Step 2 (pre-generation validation).
//
// No fields are edited here. No status change occurs at this step.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/GenerateRssFeed/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;

class Step1Controller extends Controller
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  show()                                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Display the Step 1 episode review page.
     *
     * Shows a link to the episode show page (target="_blank") so the user can
     * review all fields before proceeding. Enclosure length and duration are
     * displayed explicitly since they are critical for a valid RSS enclosure tag.
     *
     * Only available when the episode status is `ready_to_generate_rss_feed`.
     */
    public function show(PodcastEpisode $podcastEpisode): View|RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_generate_rss_feed) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for RSS feed generation.');
        }

        return view('media_platform.podcasts.publishing.post_production.generate_rss_feed.step1', [
            'episode' => $podcastEpisode->load('show'),
        ]);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  store()                                                               │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Confirm the episode review and advance to Step 2.
     *
     * Stores the episode ID in the wizard session and redirects to Step 2.
     * No status change occurs at this step — status only advances on completion
     * of the full generation and upload sequence.
     */
    public function store(PodcastEpisode $podcastEpisode): RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_generate_rss_feed) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for RSS feed generation.');
        }

        // ── Store episode ID in wizard session ────────────────────────────────
        session(['wizard.generate_rss_feed.podcast_episode_id' => $podcastEpisode->id]);

        // ── Advance to Step 2 ─────────────────────────────────────────────────
        return redirect()->route('post_production.generate_rss_feed.step2', $podcastEpisode);
    }
}