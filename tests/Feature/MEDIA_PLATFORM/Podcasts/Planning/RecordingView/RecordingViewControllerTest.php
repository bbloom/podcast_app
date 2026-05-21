<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\RecordingView;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use MediaPlatform\Podcasts\Links\Models\PodcastLink;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class RecordingViewControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a planning episode belonging to the given user with the given status.
     */
    private function makeEpisode(User $user, PodcastEpisodePlanningStatus $status): PodcastEpisodePlanning
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisodePlanning::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
        ]);
    }

    // -------------------------------------------------------------------------
    // show — happy path
    // -------------------------------------------------------------------------

    public function test_show_renders_for_owner_at_ready_to_record_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodePlanningStatus::ready_to_record);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.recording.show', $episode))
            ->assertOk()
            ->assertViewIs('media_platform.podcasts.planning.recording_view.show')
            ->assertViewHas('episode');
    }

    public function test_show_displays_the_script(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodePlanningStatus::ready_to_record);
        $episode->update(['script' => 'This is the full episode script.']);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.recording.show', $episode))
            ->assertOk()
            ->assertSee('This is the full episode script.');
    }

    public function test_show_displays_attached_guests(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodePlanningStatus::ready_to_record);

        $guest = PodcastGuest::factory()->create([
            'full_name'    => 'Jane Doe',
            'profile_full' => 'Jane is an expert in PHP.',
        ]);

        $episode->guests()->attach($guest);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.recording.show', $episode))
            ->assertOk()
            ->assertSee('Jane Doe')
            ->assertSee('Jane is an expert in PHP.');
    }

    public function test_show_displays_guest_website_link(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodePlanningStatus::ready_to_record);

        $guest = PodcastGuest::factory()->create([
            'full_name'             => 'Jane Doe',
            'link_to_guest_website' => 'https://janedoe.com',
        ]);

        $episode->guests()->attach($guest);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.recording.show', $episode))
            ->assertOk()
            ->assertSee('https://janedoe.com');
    }

    public function test_show_displays_attached_links(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodePlanningStatus::ready_to_record);

        $link = PodcastLink::factory()->create([
            'user_id' => $user->id,
            'title'   => 'PHP Serverless Project',
            'link'    => 'https://phpserverless.com',
        ]);

        $episode->links()->attach($link);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.recording.show', $episode))
            ->assertOk()
            ->assertSee('PHP Serverless Project')
            ->assertSee('https://phpserverless.com');
    }

    // -------------------------------------------------------------------------
    // show — status gate
    //
    // Expanded as individual tests rather than a data provider, to stay
    // consistent with the rest of the codebase (PHPUnit 10/11 dropped the
    // @dataProvider docblock annotation in favour of #[DataProvider]).
    // -------------------------------------------------------------------------

    public function test_show_redirects_for_new_episode_created_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodePlanningStatus::new_episode_created);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.recording.show', $episode))
            ->assertRedirect(route('podcast_episodes_planning.show', $episode))
            ->assertSessionHas('error');
    }

    public function test_show_redirects_for_writing_script_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodePlanningStatus::writing_script);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.recording.show', $episode))
            ->assertRedirect(route('podcast_episodes_planning.show', $episode))
            ->assertSessionHas('error');
    }

    public function test_show_redirects_for_ready_to_finalize_the_script_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodePlanningStatus::ready_to_finalize_the_script);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.recording.show', $episode))
            ->assertRedirect(route('podcast_episodes_planning.show', $episode))
            ->assertSessionHas('error');
    }

    public function test_show_redirects_for_ready_for_publishing_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodePlanningStatus::ready_for_publishing);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.recording.show', $episode))
            ->assertRedirect(route('podcast_episodes_planning.show', $episode))
            ->assertSessionHas('error');
    }

    // -------------------------------------------------------------------------
    // show — ownership
    // -------------------------------------------------------------------------

    public function test_show_redirects_with_error_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $ep    = $this->makeEpisode($other, PodcastEpisodePlanningStatus::ready_to_record);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.recording.show', $ep))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    // -------------------------------------------------------------------------
    // show — authentication
    // -------------------------------------------------------------------------

    public function test_show_redirects_unauthenticated_users_to_login(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodePlanningStatus::ready_to_record);

        $this->get(route('podcast_episodes_planning.recording.show', $episode))
            ->assertRedirect(route('login'));
    }
}