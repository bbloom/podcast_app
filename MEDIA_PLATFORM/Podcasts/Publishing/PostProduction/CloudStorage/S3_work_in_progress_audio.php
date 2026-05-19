<?php

// =============================================================================
// S3_work_in_progress_audio
//
// Provides bucket name, folder path resolution, and file listing for the
// podcast work-in-progress S3 bucket. All raw WAV recordings land here,
// organised by show folder.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/CloudStorage/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\CloudStorage;

use Aws\S3\S3Client;

class S3_work_in_progress_audio
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  getBucket()                                                           │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Returns the S3 bucket name for work-in-progress audio recordings.
     */
    public function getBucket(): string
    {
        return 'podcast-work-in-progress';
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  getFolderPath()                                                       │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Returns the S3 folder prefix for the given podcast show slug.
     *
     * All raw WAV recordings land in a single S3 bucket, organised by show
     * folder. Throws a RuntimeException if no folder is mapped to the slug.
     */
    public function getFolderPath(string $podcast_show_slug): string
    {
        if ($podcast_show_slug == 'bob-bloom-show') {
            return 'bobbloomshow/raw_input_files/';
        }

        if ($podcast_show_slug == 'bob-bloom-interviews-show') {
            return 'bobbloominterviews/raw_input_files/';
        }

        if ($podcast_show_slug == 'php-serverless-news') {
            return 'phpserverlessnews/raw_input_files/';
        }

        if ($podcast_show_slug == 'php-serverless-profiles') {
            return 'phpserverlessprofiles/raw_input_files/';
        }

        if ($podcast_show_slug == 'php-serverless-project-updates') {
            return 'phpserverlessprojectupdates/raw_input_files/';
        }

        throw new \RuntimeException("No S3 work-in-progress folder path was found for show slug: {$podcast_show_slug}");
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  listFiles()                                                           │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Lists the basenames of all files in the show's S3 folder.
     *
     * Uses a ListObjectsV2 call restricted to the show's folder prefix so
     * only relevant files are returned. The folder prefix itself is excluded
     * from the results — only actual file entries are returned.
     *
     * Returns an array of basenames (e.g. ['episode-047.wav']).
     * Returns an empty array if the folder contains no files.
     *
     * @param  string  $podcast_show_slug
     * @return string[]
     */
    public function listFiles(string $podcast_show_slug): array
    {
        $bucket = $this->getBucket();
        $prefix = $this->getFolderPath($podcast_show_slug);

        $s3Client = new S3Client([
            'version'     => 'latest',
            'region'      => config('podcast_post_production.aws.region'),
            'credentials' => [
                'key'    => config('podcast_post_production.aws.access_key_id'),
                'secret' => config('podcast_post_production.aws.secret_access_key'),
            ],
        ]);

        $result = $s3Client->listObjectsV2([
            'Bucket' => $bucket,
            'Prefix' => $prefix,
        ]);

        $files = [];

        foreach ($result->get('Contents') ?? [] as $object) {
            $key      = $object['Key'];
            $basename = basename($key);

            // Skip the folder entry itself — S3 sometimes returns the prefix
            // as an object with an empty basename or a trailing slash key.
            if ($basename === '' || $key === $prefix) {
                continue;
            }

            $files[] = $basename;
        }

        return $files;
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  buildConsoleUrl()                                                     │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Builds the AWS S3 console URL for the show's folder.
     *
     * Deep-links directly to the specific folder in the AWS console.
     * If the user is not logged in, AWS will redirect to the console login.
     *
     * @param  string  $podcast_show_slug
     * @return string
     */
    public function buildConsoleUrl(string $podcast_show_slug): string
    {
        $bucket = $this->getBucket();
        $prefix = $this->getFolderPath($podcast_show_slug);
        $region = config('podcast_post_production.aws.region');

        return 'https://s3.console.aws.amazon.com/s3/buckets/' . $bucket
            . '?region=' . $region
            . '&prefix=' . rawurlencode($prefix)
            . '&showversions=false';
    }
}