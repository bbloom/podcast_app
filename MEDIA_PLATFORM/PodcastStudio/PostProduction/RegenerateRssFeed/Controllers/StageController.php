<?php

// =============================================================================
// StageController
//
// Generates the RSS XML for the selected show and uploads it to the
// podcast-work-in-progress S3 staging bucket for external validation.
//
// On success renders the staging page with the public URL and links to
// external validators. The staging URL is stored in the session so
// PromoteController can retrieve it without rebuilding it.
//
// On generation failure (no eligible episodes) redirects back to the
// index with a clear error message.
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
use Illuminate\View\View;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PostProduction\CloudStorage\S3_rss;
use MediaPlatform\PodcastStudio\PostProduction\GenerateRssFeed\Services\RssFeedGeneratorService;

class StageController extends Controller
{
    /**
     * Generate the RSS XML for the show and upload it to the staging bucket.
     *
     * Writes the XML to local storage, uploads to the podcast-work-in-progress
     * S3 bucket under rss/, stores the staging URL and filename in the session,
     * then renders the staging view with the URL and external validator links.
     */
    public function __invoke(PodcastShow $podcastShow, RssFeedGeneratorService $generator): View|RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastShow->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.regenerate_rss_feed.index')
                ->with('error', 'You do not have permission to access that show.');
        }

        // ── Generate the XML ──────────────────────────────────────────────────
        $result = $generator->generate($podcastShow);

        if (! $result->ok()) {
            return redirect()
                ->route('post_production.regenerate_rss_feed.index')
                ->with('error', 'RSS generation failed: ' . $result->error());
        }

        $xml      = $result->xml();
        $filename = $generator->getFileName($podcastShow);

        // ── Write XML to local storage ────────────────────────────────────────
        $localDir  = storage_path('app/podcasts/rss');
        $localPath = $localDir . '/' . $filename;

        if (! is_dir($localDir)) {
            mkdir($localDir, 0755, recursive: true);
        }

        file_put_contents($localPath, $xml);

        // ── Upload to staging S3 bucket ───────────────────────────────────────
        // The podcast-work-in-progress bucket is already public — no ACL needed.
        $s3Rss       = new S3_rss();
        $bucket      = $s3Rss->getWorkInProgressBucket();
        $folder      = $s3Rss->getWorkInProgressFolder();
        $s3Key       = $folder . '/' . $filename;
        $stagingUrl  = null;
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

            $region     = config('podcast_post_production.aws.region');
            $stagingUrl = 'https://' . $bucket . '.s3.' . $region . '.amazonaws.com/' . $s3Key;

            // Store staging details in session for PromoteController.
            session([
                'regenerate_rss_feed.staging_url'  => $stagingUrl,
                'regenerate_rss_feed.rss_filename'  => $filename,
                'regenerate_rss_feed.rss_s3_key'    => $s3Key,
                'regenerate_rss_feed.show_id'        => $podcastShow->id,
            ]);

        } catch (S3Exception $e) {
            $uploadError = 'Could not upload to the staging bucket: ' . $e->getMessage();

            \Illuminate\Support\Facades\Log::error('StageController (RegenerateRssFeed): S3 staging upload failed.', [
                'show_id' => $podcastShow->id,
                'bucket'  => $bucket,
                'key'     => $s3Key,
                'error'   => $e->getMessage(),
            ]);
        }

        return view('media_platform.podcast_studio.post_production.regenerate_rss_feed.stage', [
            'show'        => $podcastShow,
            'stagingUrl'  => $stagingUrl,
            'uploadError' => $uploadError,
            'filename'    => $filename,
        ]);
    }
}