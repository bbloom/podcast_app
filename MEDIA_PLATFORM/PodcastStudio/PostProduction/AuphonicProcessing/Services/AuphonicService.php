<?php

// =============================================================================
// AuphonicService
//
// Handles all communication with the Auphonic REST API.
//
// Responsibilities:
//   - Submit a new production (create + start in one request)
//   - Delete an existing production (for re-submit and clean-up)
//   - Download the processed MP3 after a production completes
//   - Build the S3 input file URL for the raw WAV recording
//   - Build the download URL for the processed MP3
//
// Authentication: Bearer token from config('podcast_post_production.auphonic.api_key')
// HTTP client: Laravel's built-in Http facade (wraps Guzzle)
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/AuphonicProcessing/Services/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\AuphonicProcessing\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\PostProduction\CloudStorage\S3_work_in_progress_audio;
use MediaPlatform\PodcastStudio\PostProduction\AuphonicProcessing\Presets\Auphonic_preset;

class AuphonicService
{
    // -------------------------------------------------------------------------
    // Auphonic API base URL.
    // -------------------------------------------------------------------------
    private const API_BASE = 'https://auphonic.com/api';

    // -------------------------------------------------------------------------
    // Auphonic download URL bases — tried in order when downloading the
    // processed MP3. The /engine/ endpoint is tried first; /api/ is the
    // fallback. Both require Bearer token authentication.
    //
    // Auphonic has historically served downloads from both paths, but the
    // /engine/ path has been more reliable in practice.
    // -------------------------------------------------------------------------
    private const DOWNLOAD_BASE_ENGINE = 'https://auphonic.com/engine/download/audio-result';
    private const DOWNLOAD_BASE_API    = 'https://auphonic.com/api/download/audio-result';

    // -------------------------------------------------------------------------
    // AWS S3 base URL — used to construct the public input file URL passed
    // to Auphonic so it can fetch the raw WAV recording directly from S3.
    //
    // Uses the virtual-hosted style URL format required by AWS:
    //   https://{bucket}.s3.{region}.amazonaws.com/{path}
    // -------------------------------------------------------------------------


    // =========================================================================
    // PUBLIC METHODS
    // =========================================================================

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  submitProduction()                                                    │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Creates and immediately starts a new Auphonic production for the given
     * episode. Returns the full Auphonic API response.
     *
     * The input file URL points to the raw WAV recording in the
     * podcast-work-in-progress S3 bucket. Auphonic fetches the file directly
     * from S3 — no local upload is needed.
     *
     * The webhook URL is included so Auphonic calls back when processing
     * is complete.
     *
     * @param  PodcastEpisode  $episode
     * @return Response
     */
    public function submitProduction(PodcastEpisode $episode): Response
    {
        $showSlug   = $episode->show->slug;
        $presetUuid = (new Auphonic_preset())->getPreset($showSlug);
        $inputUrl   = $this->buildS3InputUrl($episode);
        $webhookUrl = route('post_production.auphonic_processing.webhook');

        return Http::withToken(config('podcast_post_production.auphonic.api_key'))
            ->post(self::API_BASE . '/productions.json', [
                'preset'     => $presetUuid,
                'input_file' => $inputUrl,
                'metadata'   => ['title' => $episode->title],
                'webhook'    => $webhookUrl,
                'action'     => 'start',
            ]);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  deleteProduction()                                                    │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Deletes an Auphonic production by its UUID.
     *
     * Used during re-submit (to clear the failed/unwanted production before
     * creating a new one) and during clean-up (after the final audio has been
     * downloaded and confirmed).
     *
     * @param  string  $auphonicProductionUuid
     * @return Response
     */
    public function deleteProduction(string $auphonicProductionUuid): Response
    {
        return Http::withToken(config('podcast_post_production.auphonic.api_key'))
            ->delete(self::API_BASE . "/production/{$auphonicProductionUuid}.json");
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  downloadMp3()                                                         │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Downloads the processed MP3 from Auphonic and saves it to local storage.
     *
     * Two download URLs are tried in order — the /engine/ path first, then
     * the /api/ path as a fallback. Auphonic has historically served downloads
     * from both, but the availability of each has varied over time.
     *
     * The file is saved to storage_path('app/podcasts/{filename}'). The directory
     * is created automatically if it does not exist.
     *
     * Returns the absolute local path to the saved file on success.
     * Throws a RuntimeException if both download attempts fail.
     *
     * @param  PodcastEpisode  $episode
     * @return string  Absolute path to the saved MP3 file.
     *
     * @throws \RuntimeException  If both download endpoints fail.
     */
    public function downloadMp3(PodcastEpisode $episode): string
    {
        $mp3Filename  = $this->buildMp3Filename($episode);
        $productionId = $episode->auphonic_production_uuid;
        $apiKey       = config('podcast_post_production.auphonic.api_key');

        // ── Ensure the destination directory exists ───────────────────────────
        $destinationDir = storage_path('app/podcasts');

        if (! is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, recursive: true);
        }

        $destinationPath = $destinationDir . '/' . $mp3Filename;

        // ── Build both candidate download URLs ────────────────────────────────
        $engineUrl = self::DOWNLOAD_BASE_ENGINE . "/{$productionId}/{$mp3Filename}";
        $apiUrl    = self::DOWNLOAD_BASE_API    . "/{$productionId}/{$mp3Filename}";

        // ── Attempt 1: /engine/ endpoint ──────────────────────────────────────
        try {
            $response = Http::withToken($apiKey)->get($engineUrl);

            if ($response->successful()) {
                file_put_contents($destinationPath, $response->body());
                return $destinationPath;
            }
        } catch (\Throwable $e) {
            // Network-level failure on the first attempt — fall through to the
            // /api/ fallback rather than throwing immediately.
            \Illuminate\Support\Facades\Log::warning('AuphonicService: /engine/ download failed, trying /api/ fallback.', [
                'episode_id'               => $episode->id,
                'auphonic_production_uuid' => $productionId,
                'error'                    => $e->getMessage(),
            ]);
        }

        // ── Attempt 2: /api/ fallback endpoint ────────────────────────────────
        try {
            $response = Http::withToken($apiKey)->get($apiUrl);

            if ($response->successful()) {
                file_put_contents($destinationPath, $response->body());
                return $destinationPath;
            }
        } catch (\Throwable $e) {
            // Both endpoints have failed — throw a descriptive exception so the
            // controller can surface a meaningful error to the user.
            throw new \RuntimeException(
                'Both Auphonic download endpoints failed. ' .
                'Tried /engine/ and /api/. Last error: ' . $e->getMessage()
            );
        }

        // ── Both requests completed but neither returned a success status ─────
        throw new \RuntimeException(
            'Both Auphonic download endpoints returned a non-success response. ' .
            'The production may not be ready yet, or the file may not exist. ' .
            'Last HTTP status: ' . ($response->status() ?? 'unknown')
        );
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  buildDownloadUrl()                                                    │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Builds the primary Auphonic download URL for the processed MP3.
     *
     * This is the /engine/ URL. Used for display purposes (e.g. showing the
     * user where the file will come from). For actual downloading, use
     * downloadMp3() which includes the /api/ fallback.
     *
     * @param  PodcastEpisode  $episode
     * @return string
     */
    public function buildDownloadUrl(PodcastEpisode $episode): string
    {
        $mp3Filename  = $this->buildMp3Filename($episode);
        $productionId = $episode->auphonic_production_uuid;

        return self::DOWNLOAD_BASE_ENGINE . "/{$productionId}/{$mp3Filename}";
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  buildMp3Filename()                                                    │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Derives the expected MP3 filename from the raw WAV filename.
     *
     * Auphonic preserves the base name of the input file and appends the
     * output format extension (mp3).
     *
     * @param  PodcastEpisode  $episode
     * @return string
     */
    public function buildMp3Filename(PodcastEpisode $episode): string
    {
        $baseName = pathinfo($episode->raw_input_audio_filename, PATHINFO_FILENAME);

        return $baseName . '.mp3';
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  deleteS3Recording()                                                   │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Deletes the raw WAV recording from the work-in-progress S3 bucket.
     *
     * Called during clean-up after Auphonic processing is complete and the
     * MP3 has been successfully downloaded to local storage.
     *
     * @param  PodcastEpisode  $episode
     * @return void
     */
    public function deleteS3Recording(PodcastEpisode $episode): void
    {
        $showSlug = $episode->show->slug;
        $storage  = new S3_work_in_progress_audio();
        $s3Key    = $storage->getFolderPath($showSlug) . $episode->raw_input_audio_filename;

        $s3Client = new \Aws\S3\S3Client([
            'version'     => 'latest',
            'region'      => config('podcast_post_production.aws.region'),
            'credentials' => [
                'key'    => config('podcast_post_production.aws.access_key_id'),
                'secret' => config('podcast_post_production.aws.secret_access_key'),
            ],
        ]);

        $s3Client->deleteObject([
            'Bucket' => $storage->getBucket(),
            'Key'    => $s3Key,
        ]);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  buildAuphonicConsoleUrl()                                             │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Builds the URL to view this production in the Auphonic web console.
     *
     * @param  string  $auphonicProductionUuid
     * @return string
     */
    public function buildAuphonicConsoleUrl(string $auphonicProductionUuid): string
    {
        return "https://auphonic.com/engine/production/{$auphonicProductionUuid}/";
    }

    // =========================================================================
    // PRIVATE METHODS
    // =========================================================================

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  buildS3InputUrl()                                                     │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Builds the full public S3 URL for the raw WAV recording.
     *
     * Auphonic fetches the file directly from this URL — no local upload needed.
     *
     * Format: https://{bucket}.s3.{region}.amazonaws.com/{folder}/{filename}
     *
     * @param  PodcastEpisode  $episode
     * @return string
     */
    private function buildS3InputUrl(PodcastEpisode $episode): string
    {
        $showSlug = $episode->show->slug;
        $storage  = new S3_work_in_progress_audio();
        // Note: S3_work_in_progress_audio::getBucket() returns the bucket name
        // with hyphens — ensure that class matches the real AWS bucket name.
        $bucket   = $storage->getBucket();
        $folder   = $storage->getFolderPath($showSlug);
        $filename = $episode->raw_input_audio_filename;
        $region   = config('podcast_post_production.aws.region');

        return 'https://' . $bucket . '.s3.' . $region . '.amazonaws.com/' . $folder . $filename;
    }
}