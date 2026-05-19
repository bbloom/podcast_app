<?php

namespace Database\Factories\Media_platform\Podcasts\Publishing;

use App\Models\User;
use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;


use Illuminate\Database\Eloquent\Factories\Factory;

class PodcastEpisodeFactory extends Factory
{
    // -------------------------------------------------------------------------
    // Bind this factory to its model.
    // -------------------------------------------------------------------------
    protected $model = PodcastEpisode::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->words(4, true);

        return [
            'podcast_show_id' => PodcastShow::factory(),
            'user_id'         => User::factory(),
            'status'          => PodcastEpisodeStatus::created,

            'auphonic_production_uuid' => null,

            // Core
            'title'                    => $title,
            'slug'                     => \Illuminate\Support\Str::slug($title),
            'scheduled_date'           => null,
            'draft'                    => null,
            'raw_input_audio_filename' => null,

            // iTunes
            'itunes_title_tag'         => null,
            'itunes_enclosure_url'     => null,
            'itunes_enclosure_length'  => null,
            'itunes_enclosure_type'    => null,
            'itunes_guid'              => null,
            'itunes_pubdate'           => null,
            'itunes_description'       => null,
            'itunes_duration'          => null,
            'itunes_link'              => null,
            'itunes_image'             => null,
            'itunes_explicit'          => false,
            'itunes_itunestitle_tag'   => null,
            'itunes_episode'           => 0,
            'itunes_season'            => 0,
            'itunes_episode_type'      => 'full',
            'itunes_block'             => false,
            'itunes_summary'           => null,
            'itunes_subtitle'          => null,
            'itunes_content_encoded'   => null,

            // RSS
            'rss_feed_enabled'         => false,

            // Website
            'website_content'          => null,
            'website_excerpt'          => null,
            'website_meta_description' => null,
            'website_episode_notes'    => null,
            'website_attribution'      => null,
            'website_featured_image'   => null,
            'website_publish_on'       => now()->toDateString(),
            'website_enabled'          => false,
        ];
    }

    /**
     * State: website-enabled episode with all required website fields filled.
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