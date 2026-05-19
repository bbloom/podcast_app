<?php

namespace Database\Factories\Media_platform\Podcasts\Links;

use App\Models\User;
use MediaPlatform\Podcasts\Links\Models\PodcastLink;
use Illuminate\Database\Eloquent\Factories\Factory;

class PodcastLinkFactory extends Factory
{
    protected $model = PodcastLink::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'title'       => fake()->sentence(4),
            'link'        => fake()->url(),
            'description' => fake()->optional()->paragraph(),
            'comments'    => fake()->optional()->sentence(),
            'enabled'     => true,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['enabled' => false]);
    }
}