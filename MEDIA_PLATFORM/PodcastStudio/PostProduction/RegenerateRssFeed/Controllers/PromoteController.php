<?php

// =============================================================================
// PromoteController
//
// Promotes the staged RSS feed from the podcast-work-in-progress S3 bucket
// to the live S3 RSS bucket and the live Cloudflare R2 RSS bucket.
//
// Steps:
//   1. Read XML from local storage.
//   2. Upload to live S3 RSS bucket (hard failure).
//   3. Upload to live R2 RSS bucket (soft failure — logged, pipeline continues).
//   4. Delete the staging file from S3.
//   5. Delete the local file.
//   6. Clear the regenerate session keys.
//
// No episode status changes occur — this is a show-level operation.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/RegenerateRssFeed/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\RegenerateRssFeed\Controllers;

use App\Http\Controllers\Controller;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Illuminate\Http\RedirectResponse;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PostProduction\CloudStorage\R2_rss;
use MediaPlatform\PodcastStudio\PostProduction\CloudStorage\S3_rss;

class PromoteController extends Controller
{
    /**
     * Promote the staged RSS feed to the live S3 and R2 buckets.
     *
     * Reads from local storage, uploads to live S3 (hard failure) and R2
     * (soft failure), deletes staging and local files, clears session.
     */
    public function promote(PodcastShow $podcastShow): RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastShow->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.regenerate_rss_feed.index')
                ->with('error', 'You do not have permission to access that show.');
        }

        // ── Retrieve session values ───────────────────────────────────────────
        $filename   = session('regenerate_rss_feed.rss_filename');
        $stagingKey = session('regenerate_rss_feed.rss_s3_key');
        $showId     = session('regenerate_rss_feed.show_id');

        if (! $filename || ! $stagingKey || $showId !== $podcastShow->id) {
            return redirect()
                ->route('post_production.regenerate_rss_feed.stage', $podcastShow)
                ->with('error', 'Session expired. Please regenerate the RSS feed.');
        }

        // ── Read XML from local storage ───────────────────────────────────────
        $localPath = storage_path('app/podcasts/rss/' . $filename);

        if (! file_exists($localPath)) {
            return redirect()
                ->route('post_production.regenerate_rss_feed.stage', $podcastShow)
                ->with('error', 'Local RSS file not found. Please regenerate the RSS feed.');
        }

        $xml      = file_get_contents($localPath);
        $showSlug = $podcastShow->slug;
        $s3Rss    = new S3_rss();

        // ── Build S3 client ───────────────────────────────────────────────────
        $s3Client = new S3Client([
            'version'     => 'latest',
            'region'      => config('podcast_post_production.aws.region'),
            'credentials' => [
                'key'    => config('podcast_post_production.aws.access_key_id'),
                'secret' => config('podcast_post_production.aws.secret_access_key'),
            ],
        ]);

        // ── Step 1: Upload to live S3 RSS bucket (hard failure) ───────────────
        $liveBucket = $s3Rss->getBucket($showSlug);
        $liveKey    = $s3Rss->getFolderPath() . '/' . $filename;

        try {
            $s3Client->putObject([
                'Bucket'      => $liveBucket,
                'Key'         => $liveKey,
                'Body'        => $xml,
                'ContentType' => 'application/rss+xml',
            ]);
        } catch (S3Exception $e) {
            \Illuminate\Support\Facades\Log::error('PromoteController (RegenerateRssFeed): Live S3 upload failed.', [
                'show_id' => $podcastShow->id,
                'bucket'  => $liveBucket,
                'key'     => $liveKey,
                'error'   => $e->getMessage(),
            ]);

            return redirect()
                ->route('post_production.regenerate_rss_feed.stage', $podcastShow)
                ->with('error', 'Could not upload RSS feed to the live S3 bucket. Error: ' . $e->getMessage());
        }

        // ── Step 2: Upload to live R2 RSS bucket (soft failure) ───────────────
        $r2Warning = null;
        $r2Rss     = new R2_rss();

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
            $r2Warning = 'R2 upload failed: ' . $e->getMessage();

            \Illuminate\Support\Facades\Log::warning('PromoteController (RegenerateRssFeed): R2 upload failed.', [
                'show_id' => $podcastShow->id,
                'error'   => $e->getMessage(),
            ]);
        }

        // ── Step 3: Delete staging file from S3 ──────────────────────────────
        try {
            $s3Client->deleteObject([
                'Bucket' => $s3Rss->getWorkInProgressBucket(),
                'Key'    => $stagingKey,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('PromoteController (RegenerateRssFeed): Could not delete staging file.', [
                'show_id' => $podcastShow->id,
                'key'     => $stagingKey,
                'error'   => $e->getMessage(),
            ]);
        }

        // ── Step 4: Delete local file ─────────────────────────────────────────
        if (file_exists($localPath)) {
            unlink($localPath);
        }

        // ── Step 5: Clear session ─────────────────────────────────────────────
        session()->forget([
            'regenerate_rss_feed.staging_url',
            'regenerate_rss_feed.rss_filename',
            'regenerate_rss_feed.rss_s3_key',
            'regenerate_rss_feed.show_id',
        ]);

        // ── Redirect with result ──────────────────────────────────────────────
        $successMessage = 'RSS feed for "' . $podcastShow->title . '" has been regenerated and is now live.';

        if ($r2Warning) {
            return redirect()
                ->route('post_production.regenerate_rss_feed.index')
                ->with('success', $successMessage . ' Warning: ' . $r2Warning);
        }

        return redirect()
            ->route('post_production.regenerate_rss_feed.index')
            ->with('success', $successMessage);
    }
}