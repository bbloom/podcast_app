<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PreProduction\CreateEpisode;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step2ControllerTest extends TestCase
{
    use RefreshDatabase;


    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function withShowInSession(User $user, PodcastShow $show): static
    {
        return $this->actingAs($user)->withSession([
            'wizard.create_episode.podcast_show_id' => $show->id,
        ]);
    }


    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_renders_for_authenticated_user_with_session(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $this->withShowInSession($user, $show)
            ->get(route('pre_production_create_podcast_episode.step2'))
            ->assertOk();
    }

    public function test_show_redirects_unauthenticated_user(): void
    {
        $this->get(route('pre_production_create_podcast_episode.step2'))
            ->assertRedirect(route('login'));
    }

    public function test_show_redirects_to_step1_when_session_is_missing(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('pre_production_create_podcast_episode.step2'))
            ->assertRedirect(route('pre_production_create_podcast_episode.step1'));
    }

    public function test_show_redirects_to_step1_with_error_when_show_belongs_to_another_user(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $show  = PodcastShow::factory()->create(['user_id' => $other->id]);

        $this->withShowInSession($user, $show)
            ->get(route('pre_production_create_podcast_episode.step2'))
            ->assertRedirect(route('pre_production_create_podcast_episode.step1'))
            ->assertSessionHas('error');
    }

    public function test_show_displays_selected_show_name(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id, 'title' => 'My Podcast Show']);

        $this->withShowInSession($user, $show)
            ->get(route('pre_production_create_podcast_episode.step2'))
            ->assertOk()
            ->assertSee('My Podcast Show');
    }

    public function test_show_prepopulates_title_with_next_episode_number(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        // Create an existing episode with number 5.
        PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::created,
            'itunes_episode'  => 5,
        ]);

        $this->withShowInSession($user, $show)
            ->get(route('pre_production_create_podcast_episode.step2'))
            ->assertOk()
            ->assertSee('#6 - Title To Be Determined');
    }

    public function test_show_prepopulates_title_with_1_when_no_episodes_exist(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $this->withShowInSession($user, $show)
            ->get(route('pre_production_create_podcast_episode.step2'))
            ->assertOk()
            ->assertSee('#1 - Title To Be Determined');
    }

    public function test_show_displays_five_most_recent_episodes(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        // Create 6 episodes — only the top 5 by episode number should appear.
        foreach (range(1, 6) as $n) {
            PodcastEpisode::factory()->create([
                'user_id'         => $user->id,
                'podcast_show_id' => $show->id,
                'status'          => PodcastEpisodeStatus::created,
                'itunes_episode'  => $n,
                'title'           => "Episode {$n}",
            ]);
        }

        $this->withShowInSession($user, $show)
            ->get(route('pre_production_create_podcast_episode.step2'))
            ->assertOk()
            ->assertSee('Episode 6')
            ->assertSee('Episode 5')
            ->assertSee('Episode 4')
            ->assertSee('Episode 3')
            ->assertSee('Episode 2')
            ->assertDontSee('Episode 1');
    }
}