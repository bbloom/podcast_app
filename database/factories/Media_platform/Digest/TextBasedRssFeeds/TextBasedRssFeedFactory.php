<?php

namespace Database\Factories\Media_platform\Digest\TextBasedRssFeeds;

use App\Models\User;
use MediaPlatform\Digest\ContentSources\TextBasedRssFeeds\Models\TextBasedRssFeed;
use Illuminate\Database\Eloquent\Factories\Factory;

class TextBasedRssFeedFactory extends Factory
{
    protected $model = TextBasedRssFeed::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'rss_url'     => fake()->unique()->url() . '/rss',
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
