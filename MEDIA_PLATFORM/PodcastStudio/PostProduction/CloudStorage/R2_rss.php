<?php 

namespace MediaPlatform\PodcastStudio\PostProduction\CloudStorage;

class R2_rss
{
    // -----------------------------------------------------------------------------
    // Each show has its own Cloudflare R2 bucket for its RSS feed file.
    // R2 is accessed via the S3-compatible API — the endpoint URL includes the
    // Cloudflare account ID and the bucket name.
    // -----------------------------------------------------------------------------

    public function get_S3_API_endpoint(string $podcast_show_slug): string 
    {    
        if ($podcast_show_slug == 'bob-bloom-show') {
            return 'https://68d1ba01a4211132571c91b73756aa21.r2.cloudflarestorage.com/bobbloomshowdotcom-rss';
        }

        if ($podcast_show_slug == 'bob-bloom-interviews-show') {
            return 'https://68d1ba01a4211132571c91b73756aa21.r2.cloudflarestorage.com/bobbloominterviewsdotcom-rss';
        }

        if ($podcast_show_slug == 'php-serverless-news') {
            return 'https://68d1ba01a4211132571c91b73756aa21.r2.cloudflarestorage.com/phpserverlessnewsdotcom-rss';
        }

        if ($podcast_show_slug == 'php-serverless-profiles') {
            return 'https://68d1ba01a4211132571c91b73756aa21.r2.cloudflarestorage.com/phpserverlessprofilesdotcom-rss';
        }

        if ($podcast_show_slug == 'php-serverless-project-updates') {
            return 'https://68d1ba01a4211132571c91b73756aa21.r2.cloudflarestorage.com/phpserverlessprojectupdatesdotcom-rss';
        }

        throw new \RuntimeException("No Cloudflare R2 RSS endpoint was found for this show slug: {$podcast_show_slug}");
    }
}