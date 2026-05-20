<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\CreateEpisodeWizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step2ControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_renders_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.wizard.create.step2'))
            ->assertOk();
    }

    public function test_show_only_lists_active_shows_for_the_user(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        PodcastShow::factory()->create(['user_id' => $user->id,  'title' => 'The Bob Bloom Show']);
        PodcastShow::factory()->create(['user_id' => $other->id, 'title' => 'PHP Serverless News']);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.create.step2'))
            ->assertOk()
            ->assertSee('The Bob Bloom Show')
            ->assertDontSee('PHP Serverless News');
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_episodes_planning.wizard.create.step2'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_saves_show_to_session_and_redirects_to_step3(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id, 'title' => 'The Bob Bloom Show']);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.create.step2.store'), [
                'podcast_show_id' => $show->id,
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.create.step3'));

        $this->assertEquals($show->id, session('wizard.create_episode_planning.podcast_show_id'));
    }

    public function test_store_rejects_show_belonging_to_another_user(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $show  = PodcastShow::factory()->create(['user_id' => $other->id, 'title' => 'The Bob Bloom Show']);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.create.step2.store'), [
                'podcast_show_id' => $show->id,
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.create.step2'))
            ->assertSessionHas('error');
    }

    public function test_store_validates_required_show(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_episodes_planning.wizard.create.step2.store'), [])
            ->assertSessionHasErrors(['podcast_show_id']);
    }

    public function test_store_redirects_unauthenticated_users(): void
    {
        $this->post(route('podcast_episodes_planning.wizard.create.step2.store'), [])
            ->assertRedirect(route('login'));
    }
}