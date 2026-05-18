<?php

namespace Database\Factories\Media_platform\Tools\FooterLinks;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\Tools\FooterLinks\Models\FooterLink;

class FooterLinkFactory extends Factory
{
    protected $model = FooterLink::class;

    public function definition(): array
    {
        return [
            'podcast_show_id' => PodcastShow::factory(),
            'user_id'         => User::factory(),
            'link_name'       => fake()->words(2, true),
            'link_url'        => fake()->url(),
            'link_order'      => fake()->numberBetween(0, 10),
        ];
    }

    /**
     * Assign ownership to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    /**
     * Assign to a specific podcast show.
     */
    public function forShow(PodcastShow $show): static
    {
        return $this->state(fn () => [
            'podcast_show_id' => $show->id,
            'user_id'         => $show->user_id,
        ]);
    }
}