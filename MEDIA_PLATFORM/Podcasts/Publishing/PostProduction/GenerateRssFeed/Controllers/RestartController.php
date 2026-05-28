<?php

// =============================================================================
// RestartController — GenerateRssFeed
//
// Resets an episode back into the GenerateRssFeed wizard entry point.
//
// Handles two statuses:
//
//   `rss_validation_failed`    — The user is restarting after validation
//                                failed and they have fixed the underlying
//                                issue (episode data, audio file, etc.).
//
//   `ready_to_upload_rss_feed` — The user's session expired while on the
//                                Live Validation page. The RSS is already on
//                                S3 but they need to regenerate to get a fresh
//                                session and retry the full flow.
//
// Steps:
//   1. Clean up the local RSS file if one exists (old attempt).
//   2. Clear all wizard session keys.
//   3. Reset status to `ready_to_generate_rss_feed`.
//   4. Redirect to Step 1 so the user can re-enter the wizard normally.
//
// Path: MEDIA_PLATFORM/Podcasts/PostProduction/GenerateRssFeed/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

class RestartController extends Controller
{
    /**
     * Reset the episode into the GenerateRssFeed wizard entry point.
     */
    public function __invoke(PodcastEpisode $podcastEpisode): RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        $restartableStatuses = [
            PodcastEpisodeStatus::rss_validation_failed,
            PodcastEpisodeStatus::ready_to_upload_rss_feed,
        ];

        if (! in_array($podcastEpisode->status, $restartableStatuses)) {
            return redirect()
                ->route($podcastEpisode->status->postProductionShowRoute(), $podcastEpisode)
                ->with('error', 'Episode "' . $podcastEpisode->title . '" cannot be restarted from its current status.');
        }

        // ── Clean up local file from previous attempt ─────────────────────────
        $filename  = session('wizard.generate_rss_feed.rss_filename');
        $localPath = $filename ? storage_path('app/podcasts/rss/' . $filename) : null;

        if ($localPath && file_exists($localPath)) {
            unlink($localPath);
        }

        // ── Clear all wizard session keys ─────────────────────────────────────
        session()->forget([
            'wizard.generate_rss_feed.podcast_episode_id',
            'wizard.generate_rss_feed.staging_url',
            'wizard.generate_rss_feed.rss_filename',
            'wizard.generate_rss_feed.rss_s3_key',
            'wizard.generate_rss_feed.live_s3_url',
            'wizard.generate_rss_feed.enclosure_manually_verified_' . $podcastEpisode->id,
        ]);

        // ── Reset status to wizard entry point ────────────────────────────────
        $podcastEpisode->update([
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed,
        ]);

        return redirect()
            ->route('post_production.generate_rss_feed.step1', $podcastEpisode)
            ->with('success', 'RSS feed wizard reset. You can now regenerate the feed from the beginning.');
    }
}