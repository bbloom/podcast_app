<?php

// =============================================================================
// Step4Controller
//
// Step 4 of the Generate RSS Feed wizard — external validator links page.
//
// show() displays the staging URL and links to external validators so the
// user can manually validate the feed before promoting it to live.
//
// failed() handles the "something failed" button — redirects to the episode
// show page so the user can review and edit fields. The episode retains its
// ready_to_generate_rss_feed status so the wizard can be restarted from the
// index when the user is ready.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/GenerateRssFeed/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

class Step4Controller extends Controller
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  show()                                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Display the external validator links page.
     *
     * Shows the staging URL prominently and links to the major external
     * feed validators. The user validates manually, then either proceeds
     * to Step 5 (promote to live) or clicks "Something failed".
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

        // ── Session guard ─────────────────────────────────────────────────────
        $stagingUrl = session('wizard.generate_rss_feed.staging_url');

        if (! $stagingUrl) {
            return redirect()
                ->route('post_production.generate_rss_feed.step3', $podcastEpisode)
                ->with('error', 'No staging URL found. Please regenerate the feed.');
        }

        return view('media_platform.podcasts.publishing.post_production.generate_rss_feed.step4', [
            'episode'    => $podcastEpisode->load('show'),
            'stagingUrl' => $stagingUrl,
        ]);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  failed()                                                              │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Handle the "Something failed" button.
     *
     * Redirects to the episode show page so the user can review and correct
     * fields. The episode retains its ready_to_generate_rss_feed status —
     * the user can restart the wizard from the index once fixes are made.
     *
     * The wizard session is cleared so a fresh run starts cleanly.
     */
    public function failed(PodcastEpisode $podcastEpisode): RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Clear the wizard session ──────────────────────────────────────────
        $this->clearWizardSession($podcastEpisode);

        // ── Redirect to episode show page ─────────────────────────────────────
        return redirect()
            ->route('podcast_episodes.show', $podcastEpisode)
            ->with('error', 'RSS feed validation failed. Please review and correct the episode fields, then return to the Post-Production Dashboard to regenerate.');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  PRIVATE METHODS                                                       ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Clear all wizard session keys for this episode.
     */
    private function clearWizardSession(PodcastEpisode $podcastEpisode): void
    {
        session()->forget([
            'wizard.generate_rss_feed.podcast_episode_id',
            'wizard.generate_rss_feed.staging_url',
            'wizard.generate_rss_feed.rss_filename',
            'wizard.generate_rss_feed.rss_s3_key',
            'wizard.generate_rss_feed.enclosure_manually_verified_' . $podcastEpisode->id,
        ]);
    }
}