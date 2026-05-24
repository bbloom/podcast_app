<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\FinalizeScriptWizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step3ControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeEpisodeWithSession(User $user): PodcastEpisodePlanning
    {
        $show    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $episode = PodcastEpisodePlanning::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
        ]);
        session(['wizard.finalize_script.episode_id' => $episode->id]);
        return $episode;
    }

    public function test_show_renders_with_valid_session(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.finalize.step3'))
            ->assertOk();
    }

    public function test_show_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.wizard.finalize.step3'))
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }

    public function test_store_updates_title_and_redirects_to_step4(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step3.store'), [
                'title' => 'Updated Title',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step4'));

        $this->assertDatabaseHas('podcast_episodes_planning', [
            'id'    => $episode->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_store_validates_required_title(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step3.store'), ['title' => ''])
            ->assertSessionHasErrors(['title']);
    }

    public function test_store_rejects_titles_starting_with_a_digit_or_hash_digit(): void
    {
        // The episode number prefix is added automatically on publishing.
        // Titles must not start with a digit or #digit — all permutations are tested here.
        $user = User::factory()->create();

        $cases = [
            'bare number'              => '12',
            'number dash title'        => '12 - My Episode',
            'number em-dash title'     => '12 — My Episode',
            'hash number em-dash'      => '#12 — My Episode',
            'hash number dash'         => '#12 - My Episode',
            'number colon title'       => '12: My Episode',
            'number with no separator' => '12My Episode',
            'single digit'             => '1 Episode',
        ];

        foreach ($cases as $description => $title) {
            $this->makeEpisodeWithSession($user);

            $this->actingAs($user)
                ->post(route('podcast_episodes_planning.wizard.finalize.step3.store'), [
                    'title' => $title,
                ])
                ->assertSessionHasErrors(['title'], "Expected validation error for: {$description}");
        }
    }

    public function test_store_accepts_title_starting_with_a_word(): void
    {
        // Titles that spell out numbers as words must pass — e.g. "Ten Things I Learned".
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step3.store'), [
                'title' => 'Ten Things I Learned',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step4'));

        $this->assertDatabaseHas('podcast_episodes_planning', [
            'id'    => $episode->id,
            'title' => 'Ten Things I Learned',
        ]);
    }

    public function test_store_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_episodes_planning.wizard.finalize.step3.store'), ['title' => 'Test'])
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }
}