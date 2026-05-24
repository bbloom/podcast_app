<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\FinalizeScriptWizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step4ControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeEpisodeWithSession(User $user, array $overrides = []): PodcastEpisodePlanning
    {
        $show    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $episode = PodcastEpisodePlanning::factory()->create(array_merge([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'script'          => 'The script.',
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
            ->get(route('podcast_episodes_planning.wizard.finalize.step4'))
            ->assertOk();
    }

    public function test_show_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.wizard.finalize.step4'))
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_episodes_planning.wizard.finalize.step4'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // saveScratch (Alpine fetch — returns JSON)
    // -------------------------------------------------------------------------

    public function test_save_scratch_saves_field_and_returns_json_success(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->patchJson(route('podcast_episodes_planning.wizard.finalize.step4.save_scratch'), [
                'script_scratch' => 'AI modified version goes here.',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('podcast_episodes_planning', [
            'id'             => $episode->id,
            'script_scratch' => 'AI modified version goes here.',
        ]);
    }

    public function test_save_scratch_can_clear_the_field(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user, ['script_scratch' => 'Old scratch.']);

        $this->actingAs($user)
            ->patchJson(route('podcast_episodes_planning.wizard.finalize.step4.save_scratch'), [
                'script_scratch' => null,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('podcast_episodes_planning', [
            'id'             => $episode->id,
            'script_scratch' => null,
        ]);
    }

    public function test_save_scratch_returns_401_json_without_session(): void
    {
        $user = User::factory()->create();
        // No session set — no makeEpisodeWithSession call.
        $this->actingAs($user)
            ->patchJson(route('podcast_episodes_planning.wizard.finalize.step4.save_scratch'), [
                'script_scratch' => 'AI content.',
            ])
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_save_scratch_requires_auth(): void
    {
        // Use patch (not patchJson) — patchJson sets Accept: application/json,
        // which causes the auth middleware to return 401 instead of a redirect.
        $this->patch(route('podcast_episodes_planning.wizard.finalize.step4.save_scratch'), [
            'script_scratch' => 'AI content.',
        ])->assertRedirect(route('login'));
    }

    public function test_save_scratch_does_not_modify_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        // Set up the session as the other user's episode — simulating a tampered session.
        $show    = PodcastShow::factory()->create(['user_id' => $other->id]);
        $episode = PodcastEpisodePlanning::factory()->create([
            'user_id'         => $other->id,
            'podcast_show_id' => $show->id,
            'script_scratch'  => null,
        ]);
        session(['wizard.finalize_script.episode_id' => $episode->id]);

        $this->actingAs($user)
            ->patchJson(route('podcast_episodes_planning.wizard.finalize.step4.save_scratch'), [
                'script_scratch' => 'Tampered content.',
            ])
            ->assertStatus(401);

        $this->assertDatabaseHas('podcast_episodes_planning', [
            'id'             => $episode->id,
            'script_scratch' => null,
        ]);
    }
}