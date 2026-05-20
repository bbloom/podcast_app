<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\CreateEpisodeWizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step3ControllerTest extends TestCase
{
    use RefreshDatabase;

    private function withShowInSession(User $user): PodcastShow
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id, 'title' => 'The Bob Bloom Show']);
        session(['wizard.create_episode_planning.podcast_show_id' => $show->id]);
        return $show;
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_renders_when_session_is_valid(): void
    {
        $user = User::factory()->create();
        $this->withShowInSession($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.create.step3'))
            ->assertOk();
    }

    public function test_show_redirects_to_step1_when_session_is_missing(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.wizard.create.step3'))
            ->assertRedirect(route('podcast_episodes_planning.wizard.create.step1'));
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_episodes_planning.wizard.create.step3'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_episode_and_redirects_to_step4(): void
    {
        $user = User::factory()->create();
        $show = $this->withShowInSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.create.step3.store'), [
                'title'          => 'My New Episode',
                'episode_number' => 42,
                'scheduled_date' => '2026-09-01',
                'theme'          => 'A theme about serverless.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('podcast_episodes_planning', [
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'title'           => 'My New Episode',
            'episode_number'  => 42,
            'status'          => PodcastEpisodePlanningStatus::new_episode_created->value,
        ]);
    }

    public function test_store_clears_session_after_creating_episode(): void
    {
        $user = User::factory()->create();
        $this->withShowInSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.create.step3.store'), [
                'title' => 'My Episode',
            ]);

        $this->assertNull(session('wizard.create_episode_planning.podcast_show_id'));
    }

    public function test_store_validates_required_title(): void
    {
        $user = User::factory()->create();
        $this->withShowInSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.create.step3.store'), [])
            ->assertSessionHasErrors(['title']);
    }

    public function test_store_redirects_to_step1_when_session_is_missing(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_episodes_planning.wizard.create.step3.store'), ['title' => 'Test'])
            ->assertRedirect(route('podcast_episodes_planning.wizard.create.step1'));
    }

    public function test_store_redirects_unauthenticated_users(): void
    {
        $this->post(route('podcast_episodes_planning.wizard.create.step3.store'), [])
            ->assertRedirect(route('login'));
    }
}