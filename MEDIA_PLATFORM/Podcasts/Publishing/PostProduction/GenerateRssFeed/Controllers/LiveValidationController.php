<?php

// =============================================================================
// LiveValidationController — GenerateRssFeed
//
// Handles the Live Validation step — the final gate before the RSS feed
// goes live on Cloudflare R2 (the public CDN polled by Apple, Spotify, etc.).
//
// At this point the feed is already on the live S3 bucket. S3 is used for
// validation; R2 is what the world sees. R2 must not be updated until the
// user confirms the feed is correct.
//
// Three actions:
//
//   show()        — Displays the live S3 URL and external validator links.
//                   Reads the URL from the session set by Step5Controller.
//                   If the session has expired, shows a recovery section.
//
//   promoteToR2() — Uploads the XML to the live R2 bucket (hard failure).
//                   On success: deletes the local file, advances status to
//                   `published`, clears session, redirects to the done page.
//
//   fail()        — User marks validation as failed ("needs attention").
//                   Deletes local file, clears session, sets status to
//                   `rss_validation_failed`, redirects to episode show page.
//                   Dashboard will surface this as "Needs Attention".
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/GenerateRssFeed/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers;

use App\Http\Controllers\Controller;
use Aws\S3\S3Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Publishing\PostProduction\CloudStorage\R2_rss;

class LiveValidationController extends Controller
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  show()                                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Display the Live Validation page.
     *
     * Shows the live S3 URL for copy/paste into external validators.
     * If the session has expired (e.g. user navigated away and returned
     * via the dashboard), a recovery section is shown instead of the URL.
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
        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_upload_rss_feed) {
            return redirect()
                ->route($podcastEpisode->status->postProductionShowRoute(), $podcastEpisode)
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for live validation.');
        }

        $liveS3Url      = session('wizard.generate_rss_feed.live_s3_url');
        $sessionExpired = ! $liveS3Url;

        return view(
            'media_platform.podcasts.publishing.post_production.generate_rss_feed.live_validation',
            [
                'episode'        => $podcastEpisode->load('show'),
                'liveS3Url'      => $liveS3Url,
                'sessionExpired' => $sessionExpired,
            ]
        );
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  promoteToR2()                                                         │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Upload the RSS feed to the live R2 bucket after validation passes.
     *
     * R2 is the public-facing CDN — this is the final step that makes the
     * feed available to Apple, Spotify, and other directories.
     *
     * R2 upload failure is a hard failure — the user is redirected back to
     * the Live Validation page with an error so they can retry. The local
     * file and session are preserved for retries.
     */
    public function promoteToR2(PodcastEpisode $podcastEpisode): RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_upload_rss_feed) {
            return redirect()
                ->route($podcastEpisode->status->postProductionShowRoute(), $podcastEpisode)
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for R2 promotion.');
        }

        // ── Retrieve session values ───────────────────────────────────────────
        $filename = session('wizard.generate_rss_feed.rss_filename');

        if (! $filename) {
            return redirect()
                ->route('post_production.generate_rss_feed.restart', $podcastEpisode)
                ->with('error', 'Session expired. Please restart the RSS feed wizard.');
        }

        $localPath = storage_path('app/podcasts/rss/' . $filename);

        if (! file_exists($localPath)) {
            return redirect()
                ->route('post_production.generate_rss_feed.restart', $podcastEpisode)
                ->with('error', 'Local RSS file not found. Please restart the wizard to regenerate.');
        }

        // ── Upload to live R2 RSS bucket ──────────────────────────────────────
        $podcastEpisode->load('show');
        $showSlug = $podcastEpisode->show->slug;
        $xml      = file_get_contents($localPath);
        $r2Rss    = new R2_rss();

        try {
            $r2Endpoint = $r2Rss->get_S3_API_endpoint($showSlug);
            $r2Bucket   = basename($r2Endpoint);

            $r2Client = new S3Client([
                'version'     => 'latest',
                'region'      => 'auto',
                'endpoint'    => 'https://' . config('podcast_post_production.cloudflare.account_id') . '.r2.cloudflarestorage.com',
                'credentials' => [
                    'key'    => config('podcast_post_production.cloudflare.access_key_id'),
                    'secret' => config('podcast_post_production.cloudflare.secret_access_key'),
                ],
                'use_path_style_endpoint' => true,
            ]);

            $r2Client->putObject([
                'Bucket'      => $r2Bucket,
                'Key'         => $filename,
                'Body'        => $xml,
                'ContentType' => 'application/rss+xml',
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('LiveValidationController (GenerateRssFeed): R2 upload failed.', [
                'episode_id' => $podcastEpisode->id,
                'error'      => $e->getMessage(),
            ]);

            return redirect()
                ->route('post_production.generate_rss_feed.live_validation', $podcastEpisode)
                ->with('error', 'Could not upload the RSS feed to R2. Error: ' . $e->getMessage() . '. Please try again.');
        }

        // ── Delete local file ─────────────────────────────────────────────────
        if (file_exists($localPath)) {
            unlink($localPath);
        }

        // ── Advance episode status to published ───────────────────────────────
        $podcastEpisode->update([
            'status' => PodcastEpisodeStatus::published,
        ]);

        // ── Clear wizard session ──────────────────────────────────────────────
        session()->forget([
            'wizard.generate_rss_feed.podcast_episode_id',
            'wizard.generate_rss_feed.staging_url',
            'wizard.generate_rss_feed.rss_filename',
            'wizard.generate_rss_feed.rss_s3_key',
            'wizard.generate_rss_feed.live_s3_url',
            'wizard.generate_rss_feed.enclosure_manually_verified_' . $podcastEpisode->id,
        ]);

        return redirect()->route('post_production.generate_rss_feed.done', $podcastEpisode);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  fail()                                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Mark the RSS validation as failed and park the episode for later attention.
     *
     * Sets status to `rss_validation_failed` — the dashboard will surface this
     * as "RSS Validation Failed — Needs Attention". The RestartController
     * resets the episode back into the wizard when the user is ready to retry.
     *
     * The local RSS file is deleted and the session is cleared since this
     * attempt is being abandoned.
     */
    public function fail(PodcastEpisode $podcastEpisode): RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_upload_rss_feed) {
            return redirect()
                ->route($podcastEpisode->status->postProductionShowRoute(), $podcastEpisode)
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status.');
        }

        // ── Delete local file ─────────────────────────────────────────────────
        $filename  = session('wizard.generate_rss_feed.rss_filename');
        $localPath = $filename ? storage_path('app/podcasts/rss/' . $filename) : null;

        if ($localPath && file_exists($localPath)) {
            unlink($localPath);
        }

        // ── Clear session ─────────────────────────────────────────────────────
        session()->forget([
            'wizard.generate_rss_feed.podcast_episode_id',
            'wizard.generate_rss_feed.staging_url',
            'wizard.generate_rss_feed.rss_filename',
            'wizard.generate_rss_feed.rss_s3_key',
            'wizard.generate_rss_feed.live_s3_url',
            'wizard.generate_rss_feed.enclosure_manually_verified_' . $podcastEpisode->id,
        ]);

        // ── Advance status to rss_validation_failed ───────────────────────────
        $podcastEpisode->update([
            'status' => PodcastEpisodeStatus::rss_validation_failed,
        ]);

        return redirect()
            ->route('podcast_episodes.show', $podcastEpisode)
            ->with('error', 'RSS validation marked as failed. The episode is flagged on your dashboard. Fix the issue and restart the RSS wizard when ready.');
    }
}