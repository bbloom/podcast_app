<?php

// =============================================================================
// Step5Controller — GenerateRssFeed
//
// Step 5 of the Generate RSS Feed wizard — upload to live S3 only.
//
// RSS PIPELINE REORDER CHANGES:
//   Previously uploaded to both S3 (hard failure) and R2 (soft failure) in
//   one pass, then advanced to `ready_to_publish` and redirected to the
//   done page.
//
//   Now:
//   - Uploads to live S3 only (hard failure).
//   - Deletes the staging S3 file and local file is KEPT for R2 upload.
//   - Advances status to `ready_to_upload_rss_feed`.
//   - Stores the live S3 URL in session for the Live Validation page.
//   - Redirects to Live Validation (not done page).
//
//   R2 upload is deferred to LiveValidationController::promoteToR2() after
//   the user manually validates the live S3 feed and confirms it passes.
//   R2 is the public-facing CDN polled by Apple, Spotify, etc. — it must
//   not go live until validation confirms the feed is correct.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/GenerateRssFeed/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers;

use App\Http\Controllers\Controller;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Illuminate\Http\RedirectResponse;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Publishing\PostProduction\CloudStorage\S3_rss;

class Step5Controller extends Controller
{
    /**
     * Upload the RSS feed to the live S3 bucket and redirect to Live Validation.
     *
     * Steps:
     *   1. Read XML from local storage.
     *   2. Upload to the live S3 RSS bucket (hard failure).
     *   3. Delete the staging file from the WIP S3 bucket.
     *   4. Store the live S3 URL and filename in session.
     *   5. Advance episode status to `ready_to_upload_rss_feed`.
     *   6. Redirect to Live Validation.
     *
     * The local file is NOT deleted here — it is kept for
     * LiveValidationController::promoteToR2() to use when uploading to R2.
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
        // Accept ready_to_generate_rss_feed (normal flow) and
        // ready_to_upload_rss_feed (re-run after session expiry or restart).
        $acceptableStatuses = [
            PodcastEpisodeStatus::ready_to_generate_rss_feed,
            PodcastEpisodeStatus::ready_to_upload_rss_feed,
        ];

        if (! in_array($podcastEpisode->status, $acceptableStatuses)) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for RSS feed promotion.');
        }

        // ── Retrieve session values ───────────────────────────────────────────
        $filename   = session('wizard.generate_rss_feed.rss_filename');
        $stagingKey = session('wizard.generate_rss_feed.rss_s3_key');

        if (! $filename || ! $stagingKey) {
            return redirect()
                ->route('post_production.generate_rss_feed.step3', $podcastEpisode)
                ->with('error', 'Session expired. Please regenerate the RSS feed.');
        }

        // ── Load the show ─────────────────────────────────────────────────────
        $podcastEpisode->load('show');
        $showSlug  = $podcastEpisode->show->slug;
        $localPath = storage_path('app/podcasts/rss/' . $filename);
        $s3Rss     = new S3_rss();

        // ── Read XML from local storage ───────────────────────────────────────
        if (! file_exists($localPath)) {
            return redirect()
                ->route('post_production.generate_rss_feed.step3', $podcastEpisode)
                ->with('error', 'Local RSS file not found. Please regenerate the RSS feed.');
        }

        $xml = file_get_contents($localPath);

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
            \Illuminate\Support\Facades\Log::error('Step5Controller (GenerateRssFeed): Live S3 upload failed.', [
                'episode_id' => $podcastEpisode->id,
                'bucket'     => $liveBucket,
                'key'        => $liveKey,
                'error'      => $e->getMessage(),
            ]);

            return redirect()
                ->route('post_production.generate_rss_feed.step3', $podcastEpisode)
                ->with('error', 'Could not upload RSS feed to the live S3 bucket. Error: ' . $e->getMessage());
        }

        // ── Delete staging file from WIP S3 bucket ────────────────────────────
        try {
            $s3Client->deleteObject([
                'Bucket' => $s3Rss->getWorkInProgressBucket(),
                'Key'    => $stagingKey,
            ]);
        } catch (\Throwable $e) {
            // Non-blocking — log and continue.
            \Illuminate\Support\Facades\Log::warning('Step5Controller (GenerateRssFeed): Could not delete staging file from S3.', [
                'episode_id' => $podcastEpisode->id,
                'key'        => $stagingKey,
                'error'      => $e->getMessage(),
            ]);
        }

        // ── Build and store the live S3 URL ───────────────────────────────────
        $region    = config('podcast_post_production.aws.region');
        $liveS3Url = 'https://' . $liveBucket . '.s3.' . $region . '.amazonaws.com/' . $liveKey;

        session([
            'wizard.generate_rss_feed.live_s3_url' => $liveS3Url,
            // Retain rss_filename for LiveValidationController::promoteToR2()
            // Staging URL and S3 key are no longer relevant — clear them.
            'wizard.generate_rss_feed.staging_url'  => null,
            'wizard.generate_rss_feed.rss_s3_key'   => null,
        ]);

        // ── Advance episode status ────────────────────────────────────────────
        $podcastEpisode->update([
            'status' => PodcastEpisodeStatus::ready_to_upload_rss_feed,
        ]);

        return redirect()->route('post_production.generate_rss_feed.live_validation', $podcastEpisode);
    }
}