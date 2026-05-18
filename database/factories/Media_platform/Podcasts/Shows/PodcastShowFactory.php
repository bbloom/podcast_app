<?php

namespace Database\Factories\Media_platform\Podcasts\Shows;

use App\Models\User;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Illuminate\Database\Eloquent\Factories\Factory;

class PodcastShowFactory extends Factory
{
    // -------------------------------------------------------------------------
    // Bind this factory to its model.
    // -------------------------------------------------------------------------
    protected $model = PodcastShow::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->words(3, true);

        return [
            'user_id'                   => User::factory(),
            'title'                     => $title,
            'slug'                      => \Illuminate\Support\Str::slug($title),
            'description'               => $this->faker->paragraph(),
            'rss_link'                  => null,

            // iTunes
            'itunes_image'              => null,
            'itunes_language'           => 'en',
            'itunes_category_primary'   => null,
            'itunes_category_secondary' => null,
            'itunes_explicit'           => false,
            'itunes_author'             => null,
            'itunes_link'               => null,
            'itunes_email'              => null,
            'itunes_name'               => null,
            'itunes_title'              => null,
            'itunes_type'               => null,
            'itunes_copyright'          => null,
            'itunes_new_feed_url'       => null,
            'itunes_block'              => false,
            'itunes_complete'           => false,
            'itunes_summary'            => null,
            'itunes_subtitle'           => null,
            'itunes_content_encoded'    => null,

            // Spotify
            'spotify_limit'             => 0,
            'spotify_country_of_origin' => 'global',

            // Website
            'website_content'           => null,
            'website_excerpt'           => null,
            'website_meta_description'  => null,
            'website_featured_image'    => null,
            'website_publish_on'        => now()->toDateString(),
            'website_enabled'           => false,

            // Storage
            'storage_artwork_url'       => null,
            'storage_video_files_url'   => null,
            'storage_audio_files_url'   => null,
        ];
    }

    /**
     * State: website-enabled show with all required website fields filled.
     */
    public function websiteEnabled(): static
    {
        return $this->state([
            'website_content'          => $this->faker->paragraphs(2, true),
            'website_excerpt'          => $this->faker->sentence(),
            'website_meta_description' => $this->faker->sentence(),
            'website_enabled'          => true,
        ]);
    }
}