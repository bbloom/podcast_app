<?php

// =============================================================================
// PromoteController — RegenerateRssFeed
//
// Promotes the staged RSS feed to the live S3 bucket only.
//
// RSS PIPELINE REORDER CHANGES:
//   Previously uploaded to both S3 (hard) and R2 (soft) in one pass.
//   Now uploads to S3 only and redirects to Live Validation. R2 upload
//   is deferred to LiveValidationController::promoteToR2() after the user
//   manually validates the live S3 feed.
//
//   The local file is KEPT for the R2 upload step.
//
// Steps:
//   1. Read XML from local storage.
//   2. Upload to live S3 RSS bucket (hard failure).
//   3. Delete the staging file from the WIP S3 bucket.
//   4. Store the live S3 URL in session.
//   5. Redirect to Live Validation.
//
// No episode status changes — this is a show-level operation.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/RegenerateRssFeed/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\RegenerateRssFeed\Controllers;

use App\Http\Controllers\Controller;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Illuminate\Http\RedirectResponse;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\Podcasts\Publishing\PostProduction\CloudStorage\S3_rss;

class PromoteController extends Controller
{
    /**
     * Upload the staged RSS feed to the live S3 bucket and redirect to Live Validation.
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

        // ── Upload to live S3 RSS bucket (hard failure) ───────────────────────
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

        // ── Delete staging file from WIP S3 bucket ────────────────────────────
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

        // ── Build and store the live S3 URL ───────────────────────────────────
        $region    = config('podcast_post_production.aws.region');
        $liveS3Url = 'https://' . $liveBucket . '.s3.' . $region . '.amazonaws.com/' . $liveKey;

        session([
            'regenerate_rss_feed.live_s3_url'  => $liveS3Url,
            // Clear staging keys — no longer relevant after staging deletion.
            'regenerate_rss_feed.staging_url'   => null,
            'regenerate_rss_feed.rss_s3_key'    => null,
        ]);

        return redirect()->route('post_production.regenerate_rss_feed.live_validation', $podcastShow);
    }
}