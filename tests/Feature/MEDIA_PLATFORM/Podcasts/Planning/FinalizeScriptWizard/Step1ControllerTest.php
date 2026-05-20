<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\FinalizeScriptWizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step1ControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeEpisode(User $user, PodcastEpisodePlanningStatus $status): PodcastEpisodePlanning
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);
        return PodcastEpisodePlanning::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
        ]);
    }

    public function test_show_renders_and_sets_session_for_correct_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodePlanningStatus::ready_to_finalize_the_script);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.finalize.step1', $episode))
            ->assertOk();

        $this->assertEquals($episode->id, session('wizard.finalize_script.episode_id'));
    }

    public function test_show_redirects_with_error_for_wrong_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodePlanningStatus::writing_script);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.finalize.step1', $episode))
            ->assertRedirect(route('podcast_episodes_planning.show', $episode))
            ->assertSessionHas('error');
    }

    public function test_show_redirects_with_error_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode($other, PodcastEpisodePlanningStatus::ready_to_finalize_the_script);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.finalize.step1', $episode))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodePlanningStatus::ready_to_finalize_the_script);

        $this->get(route('podcast_episodes_planning.wizard.finalize.step1', $episode))
            ->assertRedirect(route('login'));
    }
}