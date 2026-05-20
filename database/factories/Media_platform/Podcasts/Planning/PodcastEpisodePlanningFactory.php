<?php

namespace Database\Factories\Media_platform\Podcasts\Planning;

use App\Models\User;
use Illuminate\Support\Str;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Illuminate\Database\Eloquent\Factories\Factory;

class PodcastEpisodePlanningFactory extends Factory
{
    protected $model = PodcastEpisodePlanning::class;

    /**
     * Default state — produces a freshly created planning episode.
     */
    public function definition(): array
    {
        $title  = fake()->sentence(4);
        $number = fake()->numberBetween(1, 200);

        return [
            'podcast_show_id' => PodcastShow::factory(),
            'user_id'         => User::factory(),
            'status'          => PodcastEpisodePlanningStatus::new_episode_created,
            'title'           => $title,
            'episode_number'  => $number,
            'scheduled_date'  => fake()->optional()->dateTimeBetween('now', '+6 months'),
            'notes'           => null,
            'theme'           => fake()->optional()->paragraph(),
            'script'          => null,
            'website_content' => null,
            'website_excerpt' => null,
        ];
    }

    // -------------------------------------------------------------------------
    // Named states — one per status for convenient test setup.
    // -------------------------------------------------------------------------

    /** Episode is freshly created, no work started yet. */
    public function newEpisodeCreated(): static
    {
        return $this->state(fn () => ['status' => PodcastEpisodePlanningStatus::new_episode_created]);
    }

    /** User is actively working on the theme. */
    public function workingOnTheme(): static
    {
        return $this->state(fn () => ['status' => PodcastEpisodePlanningStatus::working_on_theme]);
    }

    /** User is actively writing the script. */
    public function writingScript(): static
    {
        return $this->state(fn () => [
            'status' => PodcastEpisodePlanningStatus::writing_script,
            'theme'  => fake()->paragraph(),
        ]);
    }

    /** Script writing is done; ready for the Finalize Script Wizard. */
    public function readyToFinalizeTheScript(): static
    {
        return $this->state(fn () => [
            'status' => PodcastEpisodePlanningStatus::ready_to_finalize_the_script,
            'theme'  => fake()->paragraph(),
            'script' => fake()->paragraphs(3, true),
        ]);
    }

    /** Script is locked; ready to record. Set by Finalize Script Wizard. */
    public function readyToRecord(): static
    {
        return $this->state(fn () => [
            'status' => PodcastEpisodePlanningStatus::ready_to_record,
            'theme'  => fake()->paragraph(),
            'script' => fake()->paragraphs(3, true),
        ]);
    }

    /** Raw audio has been recorded but needs editing before Auphonic. */
    public function rawAudioNeedsEditing(): static
    {
        return $this->state(fn () => [
            'status' => PodcastEpisodePlanningStatus::raw_audio_needs_editing,
            'theme'  => fake()->paragraph(),
            'script' => fake()->paragraphs(3, true),
        ]);
    }

    /** WAV is ready and website content is complete — handoff point. */
    public function readyForPublishing(): static
    {
        return $this->state(fn () => [
            'status'          => PodcastEpisodePlanningStatus::ready_for_publishing,
            'theme'           => fake()->paragraph(),
            'script'          => fake()->paragraphs(3, true),
            'website_content' => fake()->paragraphs(2, true),
            'website_excerpt' => fake()->sentence(10),
        ]);
    }
}