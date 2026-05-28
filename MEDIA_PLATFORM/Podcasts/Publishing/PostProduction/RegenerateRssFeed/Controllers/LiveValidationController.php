<?php

// =============================================================================
// LiveValidationController — RegenerateRssFeed
//
// Handles the Live Validation step for the show-level RSS regeneration flow.
//
// Mirrors the GenerateRssFeed LiveValidationController but operates at the
// show level — no episode status changes occur.
//
// Two actions:
//
//   show()        — Displays the live S3 URL and external validator links.
//                   Reads the URL from the session set by PromoteController.
//                   If the session has expired, offers a "Regenerate" link.
//
//   promoteToR2() — Uploads the XML to the live R2 bucket (hard failure).
//                   On success: deletes the local file, clears session,
//                   redirects to the index with a success message.
//
// No "fail" action — this is a show-level operation with no episode status
// to set. If the feed is wrong, the user simply regenerates from the index.
//
// Path: MEDIA_PLATFORM/Podcasts/PostProduction/RegenerateRssFeed/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\RegenerateRssFeed\Controllers;

use App\Http\Controllers\Controller;
use Aws\S3\S3Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\Podcasts\Publishing\PostProduction\CloudStorage\R2_rss;

class LiveValidationController extends Controller
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  show()                                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Display the Live Validation page for the given show.
     */
    public function show(PodcastShow $podcastShow): View|RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastShow->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.regenerate_rss_feed.index')
                ->with('error', 'You do not have permission to access that show.');
        }

        // ── Session guard ─────────────────────────────────────────────────────
        $showId = session('regenerate_rss_feed.show_id');

        if ($showId && $showId !== $podcastShow->id) {
            return redirect()
                ->route('post_production.regenerate_rss_feed.stage', $podcastShow)
                ->with('error', 'Session mismatch. Please regenerate the RSS feed for this show.');
        }

        $liveS3Url      = session('regenerate_rss_feed.live_s3_url');
        $sessionExpired = ! $liveS3Url;

        return view(
            'media_platform.podcasts.publishing.post_production.regenerate_rss_feed.live_validation',
            [
                'show'           => $podcastShow,
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
     * R2 upload failure is a hard failure — the user is redirected back to
     * the Live Validation page with an error so they can retry.
     */
    public function promoteToR2(PodcastShow $podcastShow): RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastShow->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.regenerate_rss_feed.index')
                ->with('error', 'You do not have permission to access that show.');
        }

        // ── Retrieve session values ───────────────────────────────────────────
        $filename = session('regenerate_rss_feed.rss_filename');
        $showId   = session('regenerate_rss_feed.show_id');

        if (! $filename || $showId !== $podcastShow->id) {
            return redirect()
                ->route('post_production.regenerate_rss_feed.stage', $podcastShow)
                ->with('error', 'Session expired. Please regenerate the RSS feed.');
        }

        $localPath = storage_path('app/podcasts/rss/' . $filename);

        if (! file_exists($localPath)) {
            return redirect()
                ->route('post_production.regenerate_rss_feed.stage', $podcastShow)
                ->with('error', 'Local RSS file not found. Please regenerate the RSS feed.');
        }

        // ── Upload to live R2 RSS bucket ──────────────────────────────────────
        $xml   = file_get_contents($localPath);
        $r2Rss = new R2_rss();

        try {
            $r2Endpoint = $r2Rss->get_S3_API_endpoint($podcastShow->slug);
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
            \Illuminate\Support\Facades\Log::error('LiveValidationController (RegenerateRssFeed): R2 upload failed.', [
                'show_id' => $podcastShow->id,
                'error'   => $e->getMessage(),
            ]);

            return redirect()
                ->route('post_production.regenerate_rss_feed.live_validation', $podcastShow)
                ->with('error', 'Could not upload the RSS feed to R2. Error: ' . $e->getMessage() . '. Please try again.');
        }

        // ── Delete local file ─────────────────────────────────────────────────
        if (file_exists($localPath)) {
            unlink($localPath);
        }

        // ── Clear session ─────────────────────────────────────────────────────
        session()->forget([
            'regenerate_rss_feed.staging_url',
            'regenerate_rss_feed.rss_filename',
            'regenerate_rss_feed.rss_s3_key',
            'regenerate_rss_feed.live_s3_url',
            'regenerate_rss_feed.show_id',
        ]);

        return redirect()
            ->route('post_production.regenerate_rss_feed.index')
            ->with('success', 'RSS feed for "' . $podcastShow->title . '" has been regenerated and is now live on S3 and R2.');
    }
}