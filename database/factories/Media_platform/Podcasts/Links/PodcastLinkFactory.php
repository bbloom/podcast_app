<?php

namespace Database\Factories\Media_platform\Podcasts\Links;

use MediaPlatform\Podcasts\Links\Models\PodcastLink;
use Illuminate\Database\Eloquent\Factories\Factory;

class PodcastLinkFactory extends Factory
{
    protected $model = PodcastLink::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'title'       => fake()->sentence(4),
            'link'        => fake()->url(),
            'description' => fake()->optional()->paragraph(),
            'comments'    => fake()->optional()->sentence(),
            'enabled'     => true,
        ];
    }

    /**
     * Mark this link as disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn () => ['enabled' => false]);
    }
}