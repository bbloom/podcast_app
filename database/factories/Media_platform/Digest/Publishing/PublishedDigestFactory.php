<?php

namespace Database\Factories\Media_platform\Digest\Publishing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Publishing\Models\PublishedDigest;

class PublishedDigestFactory extends Factory
{
    protected $model = PublishedDigest::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-30 days', 'now');

        return [
            'list_id'              => ListModel::factory(),
            'user_id'              => User::factory(),
            'slug'                 => 'test-digest-' . $date->format('Y-m-d'),
            'digest_date'          => $date->format('Y-m-d'),
            'total_items'          => $this->faker->numberBetween(1, 20),
            'source_count'         => $this->faker->numberBetween(1, 5),
            'payload'              => $this->fakePayload(),
            'deploy_hook_fired_at' => null,
            'api_fetched_at'       => null,
        ];
    }

    // -------------------------------------------------------------------------
    // States
    // -------------------------------------------------------------------------

    /**
     * Set the owning user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set the owning list.
     */
    public function forList(ListModel $list): static
    {
        return $this->state(fn () => [
            'list_id' => $list->id,
            'user_id' => $list->user_id,
        ]);
    }

    /**
     * Mark the deploy hook as fired.
     */
    public function hookFired(): static
    {
        return $this->state(fn () => [
            'deploy_hook_fired_at' => now(),
        ]);
    }

    /**
     * Mark the digest as fetched by the static site.
     */
    public function fetched(): static
    {
        return $this->state(fn () => [
            'deploy_hook_fired_at' => now()->subMinutes(5),
            'api_fetched_at'       => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a realistic fake payload matching the API response structure.
     */
    private function fakePayload(): array
    {
        return [
            [
                'source_name' => 'Test Channel',
                'source_type' => 'youtube_channel',
                'items'       => [
                    [
                        'source_url'          => 'https://youtube.com/watch?v=abc123',
                        'source_title'        => 'Test Video Title',
                        'source_description'  => 'A test video about testing.',
                        'source_published_at' => now()->subDay()->toIso8601String(),
                        'summary_html'        => '<p>This video covers testing strategies and best practices.</p>',
                    ],
                ],
            ],
            [
                'source_name' => 'Test RSS Feed',
                'source_type' => 'text_based_rss_feed',
                'items'       => [
                    [
                        'source_url'          => 'https://example.com/article-1',
                        'source_title'        => 'Test Article',
                        'source_description'  => 'An article about software development.',
                        'source_published_at' => now()->subHours(6)->toIso8601String(),
                        'summary_html'        => '<p>A deep dive into modern software architecture patterns.</p>',
                    ],
                ],
            ],
        ];
    }
}