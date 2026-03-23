<?php

namespace Database\Factories\Media_platform\Digest\Youtube;

use App\Models\User;
use MediaPlatform\Digest\ContentSources\Youtube\Models\YoutubeChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

class YoutubeChannelFactory extends Factory
{
    protected $model = YoutubeChannel::class;

    public function definition(): array
    {
        $channelId = 'UC' . fake()->regexify('[A-Za-z0-9_-]{22}');

        return [
            'user_id'     => User::factory(),
            'channel_id'  => $channelId,
            'title'       => fake()->words(3, true),
            'handle'      => '@' . fake()->unique()->userName(),
            'channel_url' => 'https://www.youtube.com/channel/' . $channelId,
            'rss_url'     => 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $channelId,
            'thumbnail'   => fake()->optional()->imageUrl(),
            'description' => fake()->optional()->sentence(),
            'enabled'     => true,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }
}
