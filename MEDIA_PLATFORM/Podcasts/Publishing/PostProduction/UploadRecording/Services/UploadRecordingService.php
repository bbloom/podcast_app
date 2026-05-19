<?php

namespace MediaPlatform\Podcasts\Publishing\PostProduction\UploadRecording\Services;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Publishing\PostProduction\CloudStorage\S3_work_in_progress_audio;
use MediaPlatform\Podcasts\Publishing\PostProduction\UploadRecording\Exceptions\UploadRecordingException;

class UploadRecordingService
{
    // -------------------------------------------------------------------------
    // Pre-signed URL expiry — 15 minutes. Large WAV files need time to upload
    // directly from the browser to S3, but we do not want the URL to be valid
    // indefinitely.
    // -------------------------------------------------------------------------
    private const PRESIGNED_URL_EXPIRY = '+15 minutes';

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  PUBLIC METHODS                                                        ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  generatePresignedUrl()                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Generate a pre-signed S3 PUT URL so the browser can upload the WAV file
     * directly to S3 without routing the file through this server.
     *
     * Returns the pre-signed URL string.
     * Throws UploadRecordingException if the S3 client cannot be built or the
     * pre-signed URL cannot be generated.
     */
    public function generatePresignedUrl(PodcastEpisode $episode, string $filename): string
    {
        try {
            $storage = new S3_work_in_progress_audio();
            $client  = $this->buildS3Client();
            $bucket  = $storage->getBucket();
            $key     = $this->buildKey($episode, $filename);

            // Create a PutObject command — we do not execute it, we sign it.
            $cmd = $client->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);

            $request = $client->createPresignedRequest($cmd, self::PRESIGNED_URL_EXPIRY);

            return (string) $request->getUri();

        } catch (AwsException $e) {
            throw new UploadRecordingException(
                'Could not generate upload URL: ' . $e->getAwsErrorMessage(),
                0,
                $e
            );
        } catch (\Throwable $e) {
            throw new UploadRecordingException(
                'Could not generate upload URL: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  buildKey()                                                            │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Construct the S3 object key for the recording file.
     *
     * Format: {show-folder}/raw_input_files/{filename}
     * Example: bobbloomshow/raw_input_files/episode-recording.wav
     *
     * Public so it can be called by the controller when storing the key in the
     * session after generating the pre-signed URL, keeping key construction
     * in one place — the service.
     */
    public function buildKey(PodcastEpisode $episode, string $filename): string
    {
        $storage = new S3_work_in_progress_audio();

        // getFolderPath() already includes the trailing slash.
        return $storage->getFolderPath($episode->show->slug) . $filename;
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  confirmFileExists()                                                   │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Confirm that the uploaded file actually exists in S3 before advancing
     * the episode status. Uses a HeadObject call — cheap and does not
     * download the file.
     *
     * Returns true if the object exists.
     * Throws UploadRecordingException if the object is not found or if the
     * S3 call itself fails.
     */
    public function confirmFileExists(PodcastEpisode $episode, string $key): bool
    {
        try {
            $storage = new S3_work_in_progress_audio();
            $client  = $this->buildS3Client();

            $client->headObject([
                'Bucket' => $storage->getBucket(),
                'Key'    => $key,
            ]);

            return true;

        } catch (AwsException $e) {
            // A 404 from HeadObject means the file did not land in S3.
            if ($e->getStatusCode() === 404) {
                throw new UploadRecordingException(
                    'The uploaded file could not be found in S3. Please try uploading again.',
                    0,
                    $e
                );
            }

            throw new UploadRecordingException(
                'Could not verify the uploaded file: ' . $e->getAwsErrorMessage(),
                0,
                $e
            );

        } catch (\Throwable $e) {
            throw new UploadRecordingException(
                'Could not verify the uploaded file: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  PRIVATE METHODS                                                       ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  buildS3Client()                                                       │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Build and return an S3Client using credentials from
     * config/podcast_post_production.php.
     *
     * Private — only this service should construct the S3 client. All other
     * methods that need S3 access call this method rather than building their
     * own client.
     */
    private function buildS3Client(): S3Client
    {
        return new S3Client([
            'version'     => 'latest',
            'region'      => config('podcast_post_production.aws.region'),
            'credentials' => [
                'key'    => config('podcast_post_production.aws.access_key_id'),
                'secret' => config('podcast_post_production.aws.secret_access_key'),
            ],
        ]);
    }
}