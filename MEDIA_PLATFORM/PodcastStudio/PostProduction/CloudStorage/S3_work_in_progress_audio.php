<?php 

namespace MediaPlatform\PodcastStudio\PostProduction\CloudStorage;

class S3_work_in_progress_audio 
{
    public function getBucket(): string 
    {
        return "podcast_work_in_progress";
    }

    public function getFolderPath(string $podcast_show_slug): string
    {
        // -----------------------------------------------------------------------------
    // All raw WAV recordings land in a single S3 bucket, organised by show folder.
    // -----------------------------------------------------------------------------

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
}