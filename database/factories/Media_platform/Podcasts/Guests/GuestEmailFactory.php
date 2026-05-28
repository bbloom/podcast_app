<?php

namespace Database\Factories\Media_platform\Podcasts\Guests;

use MediaPlatform\Podcasts\Guests\Enums\GuestEmailDirection;
use MediaPlatform\Podcasts\Guests\Models\GuestEmail;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GuestEmailFactory extends Factory
{
    protected $model = GuestEmail::class;

    /**
     * Default state — an outbound email sent by the app.
     */
    public function definition(): array
    {
        return [
            'podcast_guest_id' => PodcastGuest::factory(),
            'direction'        => GuestEmailDirection::Outbound,
            'subject'          => fake()->sentence(),
            'body_stripped'    => fake()->paragraphs(2, true),
            'body_full'        => fake()->paragraphs(2, true),
            'message_id'       => Str::uuid() . '@bobbloominterviews.com',
            'in_reply_to'      => null,
            'sent_at'          => now(),
            'received_at'      => null,
        ];
    }

    /**
     * Mark this as an inbound reply received from a guest.
     */
    public function inbound(): static
    {
        return $this->state(fn () => [
            'direction'   => GuestEmailDirection::Inbound,
            'sent_at'     => null,
            'received_at' => now(),
        ]);
    }
}