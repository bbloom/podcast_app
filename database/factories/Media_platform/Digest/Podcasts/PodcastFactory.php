<?php

namespace Database\Factories\Media_platform\Digest\Podcasts;

use App\Models\User;
use MediaPlatform\Digest\ContentSources\Podcasts\Models\Podcast;
use Illuminate\Database\Eloquent\Factories\Factory;

class PodcastFactory extends Factory
{
    protected $model = Podcast::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'rss_url'     => fake()->unique()->url() . '/feed.xml',
            'title'       => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'site_url'    => fake()->optional()->url(),
            'thumbnail'   => fake()->optional()->imageUrl(),
            'enabled'     => true,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }
}
