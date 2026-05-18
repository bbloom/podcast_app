<?php

namespace Database\Factories\Media_platform\Podcasts\Guests;

use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use Illuminate\Database\Eloquent\Factories\Factory;

class PodcastGuestFactory extends Factory
{
    protected $model = PodcastGuest::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $fullName = fake()->unique()->name();

        return [
            'full_name'             => $fullName,
            'slug'                  => str_replace(' ', '-', strtolower(trim($fullName))),
            'image_url'             => fake()->optional()->imageUrl(400, 400),
            'image_thumbnail_url'   => fake()->optional()->imageUrl(100, 100),
            'profile_full'          => fake()->paragraphs(2, true),
            'profile_short'         => fake()->optional()->sentence(),
            'link_to_guest_website' => fake()->optional()->url(),
            'email_address'         => fake()->safeEmail(),
            'internal_comment'      => fake()->optional()->sentence(),
            'enabled'               => true,
        ];
    }

    /**
     * Mark this guest as disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn () => ['enabled' => false]);
    }
}