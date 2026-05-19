<?php 

namespace MediaPlatform\Podcasts\Publishing\PostProduction\CloudStorage;

class R2_production_audio
{
    // -----------------------------------------------------------------------------
    // Each show has its own Cloudflare R2 bucket for final production audio files.
    // R2 is accessed via the S3-compatible API — the endpoint URL includes the
    // Cloudflare account ID and the bucket name.
    // -----------------------------------------------------------------------------

    public function get_S3_API_endpoint(string $podcast_show_slug): string 
    {    
        if ($podcast_show_slug == 'bob-bloom-show') {
            return 'https://68d1ba01a4211132571c91b73756aa21.r2.cloudflarestorage.com/bobbloomshowdotcom-podcasts';
        }

        if ($podcast_show_slug == 'bob-bloom-interviews-show') {
            return 'https://68d1ba01a4211132571c91b73756aa21.r2.cloudflarestorage.com/bobbloominterviewsdotcom-podcasts';
        }

        if ($podcast_show_slug == 'php-serverless-news') {
            return 'https://68d1ba01a4211132571c91b73756aa21.r2.cloudflarestorage.com/phpserverlessnewsdotcom-podcasts';
        }

        if ($podcast_show_slug == 'php-serverless-profiles') {
            return 'https://68d1ba01a4211132571c91b73756aa21.r2.cloudflarestorage.com/phpserverlessprofilesdotcom-podcasts';
        }

        if ($podcast_show_slug == 'php-serverless-project-updates') {
            return 'https://68d1ba01a4211132571c91b73756aa21.r2.cloudflarestorage.com/phpserverlessprojectupdatesdotcom-podcasts';
        }

        throw new \RuntimeException("No Cloudflare R2 production audio endpoint was found for this show slug: {$podcast_show_slug}");
    }
}