<?php

namespace Database\Factories\Media_platform\PodcastStudio\Management;

use Illuminate\Database\Eloquent\Factories\Factory;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisodeStatusLookup;

class PodcastEpisodeStatusLookupFactory extends Factory
{
    // -------------------------------------------------------------------------
    // Bind this factory to its model.
    // -------------------------------------------------------------------------
    protected $model = PodcastEpisodeStatusLookup::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'title'       => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'enabled'     => true,
        ];
    }

    /**
     * State: disabled status.
     */
    public function disabled(): static
    {
        return $this->state(['enabled' => false]);
    }
}