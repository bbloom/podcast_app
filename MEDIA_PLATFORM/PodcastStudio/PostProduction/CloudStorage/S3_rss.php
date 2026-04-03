<?php 

namespace MediaPlatform\PodcastStudio\PostProduction\CloudStorage;

class S3_rss
{
    // -----------------------------------------------------------------------------
    // Each show has its own S3 bucket for its RSS feed file.
    // All shows store their RSS file in the same folder name within their bucket.
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

        throw new \RuntimeException("No S3 RSS bucket was found for show slug: {$podcast_show_slug}");
    }

    public function getFolderPath(): string
    {
       return "rss";
    }
}