<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\FinalizeScriptWizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step9ControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeEpisodeWithSession(User $user, array $overrides = []): PodcastEpisodePlanning
    {
        $show    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $episode = PodcastEpisodePlanning::factory()->create(array_merge([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'script'          => 'Full assembled script.',
            'script_scratch'  => 'Some AI scratch content.',
        ], $overrides));
        session(['wizard.finalize_script.episode_id' => $episode->id]);
        return $episode;
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_renders_with_valid_session(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.finalize.step9'))
            ->assertOk();
    }

    public function test_show_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.wizard.finalize.step9'))
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_episodes_planning.wizard.finalize.step9'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_sets_status_to_ready_to_record(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step9.store'))
            ->assertRedirect(route('podcast_episodes_planning.show', $episode))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('podcast_episodes_planning', [
            'id'     => $episode->id,
            'status' => PodcastEpisodePlanningStatus::ready_to_record->value,
        ]);
    }

    public function test_store_clears_script_scratch(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user, ['script_scratch' => 'AI scratch content.']);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step9.store'));

        $this->assertDatabaseHas('podcast_episodes_planning', [
            'id'             => $episode->id,
            'script_scratch' => null,
        ]);
    }

    public function test_store_clears_wizard_session(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step9.store'));

        $this->assertNull(session('wizard.finalize_script.episode_id'));
    }

    public function test_store_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_episodes_planning.wizard.finalize.step9.store'))
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }

    public function test_store_redirects_unauthenticated_users(): void
    {
        $this->post(route('podcast_episodes_planning.wizard.finalize.step9.store'))
            ->assertRedirect(route('login'));
    }
}