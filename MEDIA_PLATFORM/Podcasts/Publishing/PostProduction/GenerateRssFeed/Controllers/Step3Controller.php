<?php

// =============================================================================
// Step3Controller
//
// Step 3 of the Generate RSS Feed wizard — generate XML and upload to staging.
//
// show() generates the RSS XML via RssFeedGeneratorService, writes it to local
// storage, uploads it to the podcast-work-in-progress S3 bucket for external
// validation, then renders the Step 3 view with the staging URL.
//
// The staging URL allows external validators (castfeedvalidator.com etc.) to
// fetch the feed before it is promoted to the live bucket in Step 5.
//
// No status change occurs at this step.
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
use MediaPlatform\Podcasts\Publishing\PostProduction\CloudStorage\S3_rss;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Services\RssFeedGeneratorService;

class Step3Controller extends Controller
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  show()                                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Generate the RSS XML and upload it to the staging S3 bucket.
     *
     * On success renders the Step 3 view with the staging URL so the user
     * can proceed to external validation in Step 4.
     *
     * On generation failure redirects back to Step 2 with an error message.
     * On S3 upload failure renders the view with an error — the user can retry.
     */
    public function show(PodcastEpisode $podcastEpisode, RssFeedGeneratorService $generator): View|RedirectResponse
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

        // ── Session guard — must have come through Step 1 ─────────────────────
        if (session('wizard.generate_rss_feed.podcast_episode_id') !== $podcastEpisode->id) {
            return redirect()
                ->route('post_production.generate_rss_feed.step1', $podcastEpisode)
                ->with('error', 'Please start from Step 1.');
        }

        // ── Load the show ─────────────────────────────────────────────────────
        $podcastEpisode->load('show');
        $show = $podcastEpisode->show;

        // ── Generate the XML ──────────────────────────────────────────────────
        $result = $generator->generate($show);

        if (! $result->ok()) {
            return redirect()
                ->route('post_production.generate_rss_feed.step2', $podcastEpisode)
                ->with('error', 'RSS generation failed: ' . $result->error());
        }

        $xml      = $result->xml();
        $filename = $generator->getFileName($show);

        // ── Write XML to local storage ────────────────────────────────────────
        $localDir  = storage_path('app/podcasts/rss');
        $localPath = $localDir . '/' . $filename;

        if (! is_dir($localDir)) {
            mkdir($localDir, 0755, recursive: true);
        }

        file_put_contents($localPath, $xml);

        // ── Upload to staging S3 bucket ───────────────────────────────────────
        // The podcast-work-in-progress bucket is already public — no ACL needed.
        $s3Rss      = new S3_rss();
        $bucket     = $s3Rss->getWorkInProgressBucket();
        $folder     = $s3Rss->getWorkInProgressFolder();
        $s3Key      = $folder . '/' . $filename;
        $stagingUrl = null;
        $uploadError = null;

        try {
            $s3Client = new S3Client([
                'version'     => 'latest',
                'region'      => config('podcast_post_production.aws.region'),
                'credentials' => [
                    'key'    => config('podcast_post_production.aws.access_key_id'),
                    'secret' => config('podcast_post_production.aws.secret_access_key'),
                ],
            ]);

            $s3Client->putObject([
                'Bucket'      => $bucket,
                'Key'         => $s3Key,
                'Body'        => $xml,
                'ContentType' => 'application/rss+xml',
            ]);

            // Build the public staging URL for the validator page.
            $region     = config('podcast_post_production.aws.region');
            $stagingUrl = 'https://' . $bucket . '.s3.' . $region . '.amazonaws.com/' . $s3Key;

            // Store the staging URL and filename in the wizard session for Step 5.
            session([
                'wizard.generate_rss_feed.staging_url'  => $stagingUrl,
                'wizard.generate_rss_feed.rss_filename'  => $filename,
                'wizard.generate_rss_feed.rss_s3_key'    => $s3Key,
            ]);

        } catch (S3Exception $e) {
            $uploadError = 'Could not upload the RSS file to the staging bucket: ' . $e->getMessage();

            \Illuminate\Support\Facades\Log::error('Step3Controller: S3 staging upload failed.', [
                'episode_id' => $podcastEpisode->id,
                'bucket'     => $bucket,
                'key'        => $s3Key,
                'error'      => $e->getMessage(),
            ]);
        }

        return view('media_platform.podcasts.publishing.post_production.generate_rss_feed.step3', [
            'episode'     => $podcastEpisode,
            'stagingUrl'  => $stagingUrl,
            'uploadError' => $uploadError,
            'filename'    => $filename,
        ]);
    }
}