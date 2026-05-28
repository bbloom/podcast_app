<?php

// =============================================================================
// UploadToStorageController
//
// Handles uploading the production MP3 from the app server to both AWS S3
// and Cloudflare R2, then extracting and persisting the file metadata
// (duration and filesize) to the episode record.
//
// RSS PIPELINE REORDER CHANGE:
//   Step 5 previously advanced the episode status to `ready_to_generate_rss_feed`.
//   In the reordered pipeline, publishing on the website and triggering the static
//   site build must happen before RSS generation. Status now advances to
//   `ready_to_publish_website` so the dashboard Continue button routes to
//   PublishOnWebsite instead of GenerateRssFeed.
//
// Path: MEDIA_PLATFORM/Podcasts/PostProduction/UploadProductionAudio/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\UploadProductionAudio\Controllers;

use App\Http\Controllers\Controller;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\PostProduction\CloudStorage\S3_production_audio;
use MediaPlatform\Podcasts\Publishing\PostProduction\CloudStorage\R2_production_audio;

class UploadToStorageController extends Controller
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  show()                                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Display the upload-to-storage confirmation page.
     */
    public function show(PodcastEpisode $podcastEpisode): View|RedirectResponse
    {
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_upload_production_file) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for production audio upload.');
        }

        $expectedFilename = pathinfo($podcastEpisode->raw_input_audio_filename, PATHINFO_FILENAME) . '.mp3';
        $filePath         = storage_path('app/podcasts/' . $expectedFilename);
        $fileExists       = file_exists($filePath);

        return view('media_platform.podcasts.publishing.post_production.upload_production_audio.upload_to_storage', [
            'episode'          => $podcastEpisode->load('show'),
            'expectedFilename' => $expectedFilename,
            'fileExists'       => $fileExists,
        ]);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  store()                                                               │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Run the upload-to-storage sequence.
     *
     * Steps:
     *   1. Confirm the file exists on the server (hard failure).
     *   2. Extract duration and filesize via getID3 (hard failure).
     *   3. Upload to AWS S3 (hard failure — S3 is the primary store).
     *   4. Upload to Cloudflare R2 (soft failure — logged, pipeline continues).
     *   5. Persist metadata and advance episode status to `ready_to_publish_website`.
     */
    public function store(PodcastEpisode $podcastEpisode): RedirectResponse
    {
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_upload_production_file) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for production audio upload.');
        }

        $showSlug         = $podcastEpisode->show->slug;
        $expectedFilename = pathinfo($podcastEpisode->raw_input_audio_filename, PATHINFO_FILENAME) . '.mp3';
        $filePath         = storage_path('app/podcasts/' . $expectedFilename);

        // ── Step 1: Confirm the file exists on the server ─────────────────────
        if (! file_exists($filePath)) {
            return redirect()
                ->route('post_production.upload_production_audio.upload_to_storage', $podcastEpisode)
                ->with('error', 'The file "' . $expectedFilename . '" was not found on the server. Please upload it first.');
        }

        // ── Step 2: Extract duration and filesize via getID3 ──────────────────
        try {
            [$duration, $filesize] = $this->extractMetadata($filePath);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('UploadToStorageController: getID3 metadata extraction failed.', [
                'episode_id' => $podcastEpisode->id,
                'file'       => $filePath,
                'error'      => $e->getMessage(),
            ]);

            return redirect()
                ->route('post_production.upload_production_audio.upload_to_storage', $podcastEpisode)
                ->with('error', 'Could not read the audio file metadata. Error: ' . $e->getMessage());
        }

        // ── Step 3: Upload to AWS S3 ──────────────────────────────────────────
        $s3Storage = new S3_production_audio();
        $s3Bucket  = $s3Storage->getBucket($showSlug);
        $s3Key     = $s3Storage->getFolderPath() . '/' . $expectedFilename;

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
                'Bucket'      => $s3Bucket,
                'Key'         => $s3Key,
                'SourceFile'  => $filePath,
                'ContentType' => 'audio/mpeg',
            ]);

        } catch (S3Exception $e) {
            \Illuminate\Support\Facades\Log::error('UploadToStorageController: S3 upload failed.', [
                'episode_id' => $podcastEpisode->id,
                'bucket'     => $s3Bucket,
                'key'        => $s3Key,
                'error'      => $e->getMessage(),
            ]);

            return redirect()
                ->route('post_production.upload_production_audio.upload_to_storage', $podcastEpisode)
                ->with('error', 'Could not upload the file to S3. Error: ' . $e->getMessage());
        }

        // ── Step 4: Upload to Cloudflare R2 ──────────────────────────────────
        $r2Warning = null;
        $r2Storage = new R2_production_audio();

        try {
            $r2Endpoint = $r2Storage->get_S3_API_endpoint($showSlug);
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
                'Key'         => $expectedFilename,
                'SourceFile'  => $filePath,
                'ContentType' => 'audio/mpeg',
            ]);

        } catch (\Throwable $e) {
            $r2Warning = 'R2 upload failed: ' . $e->getMessage();

            \Illuminate\Support\Facades\Log::warning('UploadToStorageController: R2 upload failed.', [
                'episode_id' => $podcastEpisode->id,
                'error'      => $e->getMessage(),
            ]);
        }

        // ── Build the public S3 enclosure URL ─────────────────────────────────
        $region       = config('podcast_post_production.aws.region');
        $enclosureUrl = 'https://' . $s3Bucket . '.s3.' . $region . '.amazonaws.com/' . $s3Key;

        // ── Step 5: Persist metadata and advance episode status ───────────────
        // Status advances to `ready_to_publish_website` (RSS Pipeline Reorder).
        // The episode must be published on the website and the static site build
        // must complete before RSS generation begins.
        $podcastEpisode->update([
            'itunes_duration'         => $duration,
            'itunes_enclosure_length' => (string) $filesize,
            'itunes_enclosure_url'    => $enclosureUrl,
            'status'                  => PodcastEpisodeStatus::ready_to_publish_website,
        ]);

        // ── Redirect with appropriate flash message ───────────────────────────
        if ($r2Warning) {
            return redirect()
                ->route('post_production.upload_production_audio.index')
                ->with('success', 'Production file uploaded to S3 successfully. Warning: ' . $r2Warning);
        }

        return redirect()
            ->route('post_production.upload_production_audio.index')
            ->with('success', '"' . $podcastEpisode->title . '" is uploaded and ready to publish on the website.');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  PRIVATE METHODS                                                       ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Extracts the duration and filesize from an MP3 file using getID3.
     *
     * @return array{string, int}
     * @throws \RuntimeException
     */
    private function extractMetadata(string $filePath): array
    {
        $getID3 = new \getID3();
        $info   = $getID3->analyze($filePath);

        if (empty($info['playtime_seconds'])) {
            throw new \RuntimeException('getID3 could not determine the audio duration from the file.');
        }

        $totalSeconds = (int) round($info['playtime_seconds']);
        $filesize     = $info['filesize'] ?? filesize($filePath);

        $hours   = (int) floor($totalSeconds / 3600);
        $minutes = (int) floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        if ($hours > 0) {
            $duration = $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT);
        } else {
            $duration = str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT);
        }

        return [$duration, (int) $filesize];
    }
}