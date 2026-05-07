<?php

namespace Database\Factories\Media_platform\Videos;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use MediaPlatform\Videos\Enums\VideoStatus;
use MediaPlatform\Videos\Models\Video;

/**
 * Factory for the Video model.
 */
class VideoFactory extends Factory
{
    protected $model = Video::class;

    /**
     * Define the default state.
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'user_id'             => User::factory(),
            'title'               => $title,
            'slug'                => Str::slug($title) . '-' . fake()->unique()->numerify('###'),
            'description'         => fake()->paragraph(),
            'scheduled_date'      => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'status'              => VideoStatus::not_published_to_youtube,
            'youtube_title'       => null,
            'youtube_description' => null,
            'youtube_chapters'    => null,
            'youtube_url'         => null,
        ];
    }

    /**
     * State: video belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * State: video is published to YouTube.
     */
    public function published(): static
    {
        return $this->state(fn () => [
            'status'      => VideoStatus::published_to_youtube,
            'youtube_url' => 'https://www.youtube.com/watch?v=' . Str::random(11),
        ]);
    }
}