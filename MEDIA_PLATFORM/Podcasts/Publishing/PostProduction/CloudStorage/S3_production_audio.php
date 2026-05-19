<?php 

namespace MediaPlatform\Podcasts\Publishing\PostProduction\CloudStorage;

class S3_production_audio 
{
    // -----------------------------------------------------------------------------
    // Each show has its own S3 bucket for final production audio files.
    // All shows share the same folder name within their bucket.
    // -----------------------------------------------------------------------------

    public function getBucket(string $podcast_show_slug): string
    {
         if ($podcast_show_slug == 'bob-bloom-show') {
            return 'bobbloomshowdotcom';
        }

        if ($podcast_show_slug == 'bob-bloom-interviews-show') {
            return 'bobbloominterviewsdotcom';
        }

        if ($podcast_show_slug == 'php-serverless-news') {
            return 'phpserverlessnewsdotcom';
        }

        if ($podcast_show_slug == 'php-serverless-profiles') {
            return 'phpserverlessprofilesdotcom';
        }

        if ($podcast_show_slug == 'php-serverless-project-updates') {
            return 'phpserverlessprojectupdatesdotcom';
        }

        throw new \RuntimeException("No S3 production audio bucket was found for show slug: {$podcast_show_slug}");
    }

    public function getFolderPath(): string
    {
       return "podcasts";
    }
}