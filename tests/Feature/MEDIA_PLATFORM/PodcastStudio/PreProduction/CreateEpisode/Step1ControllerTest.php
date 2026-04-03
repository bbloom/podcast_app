<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PreProduction\CreateEpisode;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use Tests\TestCase;

class Step1ControllerTest extends TestCase
{
    use RefreshDatabase;


    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('pre_production_create_podcast_episode.step1'))
            ->assertOk();
    }

    public function test_show_redirects_unauthenticated_user(): void
    {
        $this->get(route('pre_production_create_podcast_episode.step1'))
            ->assertRedirect(route('login'));
    }

    public function test_show_displays_only_active_shows_in_correct_order(): void
    {
        $user = User::factory()->create();

        // Create the five active shows.
        PodcastShow::factory()->create(['user_id' => $user->id, 'title' => 'PHP Serverless News']);
        PodcastShow::factory()->create(['user_id' => $user->id, 'title' => 'PHP Serverless Profiles']);
        PodcastShow::factory()->create(['user_id' => $user->id, 'title' => 'The Bob Bloom Interviews']);
        PodcastShow::factory()->create(['user_id' => $user->id, 'title' => 'The Bob Bloom Show']);
        PodcastShow::factory()->create(['user_id' => $user->id, 'title' => 'PHP Serverless Project Updates']);

        // Create a sixth show that should NOT appear.
        PodcastShow::factory()->create(['user_id' => $user->id, 'title' => 'Inactive Show']);

        $response = $this->actingAs($user)
            ->get(route('pre_production_create_podcast_episode.step1'))
            ->assertOk()
            ->assertSee('PHP Serverless News')
            ->assertSee('PHP Serverless Profiles')
            ->assertSee('The Bob Bloom Interviews')
            ->assertSee('The Bob Bloom Show')
            ->assertSee('PHP Serverless Project Updates')
            ->assertDontSee('Inactive Show');

        // Verify display order.
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'PHP Serverless Profiles'),
            strpos($content, 'PHP Serverless News')
        );
        $this->assertLessThan(
            strpos($content, 'The Bob Bloom Interviews'),
            strpos($content, 'PHP Serverless Profiles')
        );
        $this->assertLessThan(
            strpos($content, 'The Bob Bloom Show'),
            strpos($content, 'The Bob Bloom Interviews')
        );
        $this->assertLessThan(
            strpos($content, 'PHP Serverless Project Updates'),
            strpos($content, 'The Bob Bloom Show')
        );
    }


    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_saves_show_id_to_session_and_redirects_to_step2(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('pre_production_create_podcast_episode.step1.store'), [
                'podcast_show_id' => $show->id,
            ])
            ->assertRedirect(route('pre_production_create_podcast_episode.step2'));

        $this->assertEquals(
            $show->id,
            session('wizard.create_episode.podcast_show_id')
        );
    }

    public function test_store_redirects_unauthenticated_user(): void
    {
        $this->post(route('pre_production_create_podcast_episode.step1.store'), [
            'podcast_show_id' => 1,
        ])->assertRedirect(route('login'));
    }

    public function test_store_validates_that_podcast_show_id_is_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('pre_production_create_podcast_episode.step1.store'), [])
            ->assertSessionHasErrors(['podcast_show_id']);
    }

    public function test_store_validates_that_podcast_show_id_exists(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('pre_production_create_podcast_episode.step1.store'), [
                'podcast_show_id' => 99999,
            ])
            ->assertSessionHasErrors(['podcast_show_id']);
    }

    public function test_store_redirects_to_step1_with_error_when_show_belongs_to_another_user(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $show  = PodcastShow::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->post(route('pre_production_create_podcast_episode.step1.store'), [
                'podcast_show_id' => $show->id,
            ])
            ->assertRedirect(route('pre_production_create_podcast_episode.step1'))
            ->assertSessionHas('error');
    }
}