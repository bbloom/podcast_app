<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\CreateEpisodeWizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step4ControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeEpisode(User $user): PodcastEpisodePlanning
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);
        return PodcastEpisodePlanning::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
        ]);
    }

    public function test_show_renders_for_episode_owner(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.create.step4', $episode))
            ->assertOk()
            ->assertSee($episode->title);
    }

    public function test_show_redirects_with_error_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode($other);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.create.step4', $episode))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->get(route('podcast_episodes_planning.wizard.create.step4', $episode))
            ->assertRedirect(route('login'));
    }
}