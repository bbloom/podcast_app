<?php

namespace Database\Factories\Media_platform\Tools\PhpServerlessProjectSponsors;

use MediaPlatform\Tools\PhpServerlessProjectSponsors\Models\PhpServerlessProjectSponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhpServerlessProjectSponsorFactory extends Factory
{
    protected $model = PhpServerlessProjectSponsor::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'full_name'               => fake()->unique()->name(),
            'image_url'               => fake()->optional()->imageUrl(400, 400),
            'image_thumbnail_url'     => fake()->optional()->imageUrl(100, 100),
            'profile_full'            => fake()->paragraphs(2, true),
            'profile_short'           => fake()->optional()->sentence(),
            'link_to_sponsor_website' => fake()->optional()->url(),
            'email_address'           => fake()->safeEmail(),
            'umbrella_sponsor'        => true,
            'basecamp_sponsor'        => false,
            'restream_sponsor'        => false,
            'former_sponsor'          => false,
            'internal_comment'        => fake()->optional()->sentence(),
            'enabled'                 => true,
        ];
    }

    /**
     * Mark this sponsor as a former sponsor.
     */
    public function former(): static
    {
        return $this->state(fn () => ['former_sponsor' => true, 'enabled' => false]);
    }

    /**
     * Mark this sponsor as disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn () => ['enabled' => false]);
    }
}