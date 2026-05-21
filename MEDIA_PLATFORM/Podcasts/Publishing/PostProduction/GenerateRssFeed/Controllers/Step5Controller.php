<?php

// =============================================================================
// Step5Controller
//
// Step 5 of the Generate RSS Feed wizard — promote to live.
//
// store() copies the XML from the staging S3 bucket to the live S3 bucket
// and live R2 bucket, deletes the staging file, deletes the local file,
// advances the episode status to `ready_to_publish`, and clears the wizard
// session.
//
// S3 upload is a hard failure — if it fails the episode status does not advance.
// R2 upload is a soft failure — logged and shown as a warning, but the pipeline
// still advances since S3 is the primary store.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/GenerateRssFeed/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers;

use App\Http\Controllers\Controller;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Publishing\PostProduction\CloudStorage\R2_rss;
use MediaPlatform\Podcasts\Publishing\PostProduction\CloudStorage\S3_rss;

class Step5Controller extends Controller
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  store()                                                               │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Promote the RSS feed from staging to live.
     *
     * Steps:
     *   1. Read XML from local storage.
     *   2. Upload to the live S3 RSS bucket (hard failure).
     *   3. Upload to the live R2 RSS bucket (soft failure).
     *   4. Delete the staging file from S3.
     *   5. Delete the local file.
     *   6. Advance episode status to `ready_to_publish`.
     *   7. Clear wizard session.
     */
    public function store(PodcastEpisode $podcastEpisode): RedirectResponse|View
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

        // ── Retrieve session values ───────────────────────────────────────────
        $filename    = session('wizard.generate_rss_feed.rss_filename');
        $stagingKey  = session('wizard.generate_rss_feed.rss_s3_key');

        if (! $filename || ! $stagingKey) {
            return redirect()
                ->route('post_production.generate_rss_feed.step3', $podcastEpisode)
                ->with('error', 'Session expired. Please regenerate the RSS feed.');
        }

        // ── Load the show ─────────────────────────────────────────────────────
        $podcastEpisode->load('show');
        $show     = $podcastEpisode->show;
        $showSlug = $show->slug;

        // ── Read XML from local storage ───────────────────────────────────────
        $localPath = storage_path('app/podcasts/rss/' . $filename);

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

        $s3Rss = new S3_rss();

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
            \Illuminate\Support\Facades\Log::error('Step5Controller: Live S3 upload failed.', [
                'episode_id' => $podcastEpisode->id,
                'bucket'     => $liveBucket,
                'key'        => $liveKey,
                'error'      => $e->getMessage(),
            ]);

            return redirect()
                ->route('post_production.generate_rss_feed.step4', $podcastEpisode)
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

            \Illuminate\Support\Facades\Log::warning('Step5Controller: R2 upload failed.', [
                'episode_id' => $podcastEpisode->id,
                'error'      => $e->getMessage(),
            ]);
        }

        // ── Step 3: Delete staging file from S3 ──────────────────────────────
        try {
            $s3Client->deleteObject([
                'Bucket' => $s3Rss->getWorkInProgressBucket(),
                'Key'    => $stagingKey,
            ]);
        } catch (\Throwable $e) {
            // Non-blocking — log and continue.
            \Illuminate\Support\Facades\Log::warning('Step5Controller: Could not delete staging file from S3.', [
                'episode_id' => $podcastEpisode->id,
                'key'        => $stagingKey,
                'error'      => $e->getMessage(),
            ]);
        }

        // ── Step 4: Delete local file ─────────────────────────────────────────
        if (file_exists($localPath)) {
            unlink($localPath);
        }

        // ── Step 5: Advance episode status ────────────────────────────────────
        $podcastEpisode->update([
            'status' => PodcastEpisodeStatus::ready_to_publish,
        ]);

        // ── Step 6: Clear wizard session ──────────────────────────────────────
        session()->forget([
            'wizard.generate_rss_feed.podcast_episode_id',
            'wizard.generate_rss_feed.staging_url',
            'wizard.generate_rss_feed.rss_filename',
            'wizard.generate_rss_feed.rss_s3_key',
            'wizard.generate_rss_feed.enclosure_manually_verified_' . $podcastEpisode->id,
        ]);

        // ── Redirect with result ──────────────────────────────────────────────
        $successMessage = '"' . $podcastEpisode->title . '" RSS feed is live. Episode is ready to publish.';

        if ($r2Warning) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('success', $successMessage . ' Warning: ' . $r2Warning);
        }

        return redirect()
            ->route('post_production.generate_rss_feed.index')
            ->with('success', $successMessage);
    }
}