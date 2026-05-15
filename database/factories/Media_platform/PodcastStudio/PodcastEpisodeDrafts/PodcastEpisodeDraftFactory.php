<?php

// =============================================================================
// PodcastEpisodeDraftFactory
//
// Path: database/factories/Media_platform/PodcastStudio/PodcastEpisodeDrafts/
// =============================================================================

namespace Database\Factories\Media_platform\PodcastStudio\PodcastEpisodeDrafts;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Enums\PodcastEpisodeDraftStatus;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Models\PodcastEpisodeDraft;

class PodcastEpisodeDraftFactory extends Factory
{
    protected $model = PodcastEpisodeDraft::class;

    public function definition(): array
    {
        return [
            'podcast_show_id' => PodcastShow::factory(),
            'user_id'         => User::factory(),
            'status'          => PodcastEpisodeDraftStatus::working_on_draft,
            'title'           => $this->faker->sentence(4),
            'date'            => null,
            'episode_number'  => null,
            'draft'           => null,
            'website_content' => null,
            'website_excerpt' => null,
            'guest_notes'     => null,
            'comments'        => null,
            'basecamp_url'    => null,
        ];
    }

    /**
     * State: draft belonging to a specific user and their show.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * State: draft with all planning fields populated.
     */
    public function withPlanning(): static
    {
        return $this->state(fn () => [
            'date'           => $this->faker->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'episode_number' => $this->faker->numberBetween(1, 100),
        ]);
    }

    /**
     * State: draft with body content written.
     */
    public function withDraft(): static
    {
        return $this->state(fn () => [
            'draft' => $this->faker->paragraphs(3, true),
        ]);
    }

    /**
     * State: draft with website content ready for production.
     */
    public function withWebsite(): static
    {
        return $this->state(fn () => [
            'website_content' => $this->faker->paragraphs(2, true),
            'website_excerpt' => $this->faker->sentence(),
        ]);
    }

    /**
     * State: fully populated draft ready for conversion.
     */
    public function readyForProduction(): static
    {
        return $this->withPlanning()->withDraft()->withWebsite()->state(fn () => [
            'status' => PodcastEpisodeDraftStatus::ready_to_create_production_episode,
        ]);
    }
}