<?php 

namespace MediaPlatform\PodcastStudio\PostProduction\AuphonicProcessing\Presets;

class Auphonic_preset
{
    public function getPreset(string $podcast_show_slug): string 
    {    
        if ($podcast_show_slug == 'bob-bloom-show') {
            return 'ow8cRQf2TY6tgUjXFsDGVf';
        }

        if ($podcast_show_slug == 'bob-bloom-interviews-show') {
            return 'Ce7XKUPk8c72aYDkEgDibM';
        }

        if ($podcast_show_slug == 'php-serverless-news') {
            return 'KJgKX4GNsPwfA5vKfjkU9o';
        }

        if ($podcast_show_slug == 'php-serverless-profiles') {
            return 'NFiPQZVfKnpNoaUSFE3yWV';
        }

        if ($podcast_show_slug == 'php-serverless-project-updates') {
            return '249hEVobvDgzGvRwxGdKiK';
        }

        throw new \RuntimeException("No Auphonic preset found for this show slug: {$podcast_show_slug}");
    }
}