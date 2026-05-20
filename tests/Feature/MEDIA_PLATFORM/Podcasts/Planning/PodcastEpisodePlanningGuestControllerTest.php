<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class PodcastEpisodePlanningGuestControllerTest extends TestCase
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

    // -------------------------------------------------------------------------
    // attachIndex
    // -------------------------------------------------------------------------

    public function test_attach_index_shows_unattached_enabled_guests(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $available = PodcastGuest::factory()->create(['full_name' => 'Available Guest', 'enabled' => true]);
        $disabled  = PodcastGuest::factory()->create(['full_name' => 'Disabled Guest',  'enabled' => false]);
        $attached  = PodcastGuest::factory()->create(['full_name' => 'Attached Guest',  'enabled' => true]);

        $episode->guests()->attach($attached->id);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.guests.attach.index', $episode))
            ->assertOk()
            ->assertSee('Available Guest')
            ->assertDontSee('Disabled Guest')
            ->assertDontSee('Attached Guest');
    }

    public function test_attach_index_redirects_with_error_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $ep    = $this->makeEpisode($other);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.guests.attach.index', $ep))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_attach_index_redirects_unauthenticated_users(): void
    {
        $ep = $this->makeEpisode(User::factory()->create());
        $this->get(route('podcast_episodes_planning.guests.attach.index', $ep))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // attach
    // -------------------------------------------------------------------------

    public function test_attach_links_guest_to_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $guest   = PodcastGuest::factory()->create();

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.guests.attach', [$episode, $guest]))
            ->assertRedirect(route('podcast_episodes_planning.show', $episode))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('podcast_guest_episode_planning', [
            'podcast_episode_planning_id' => $episode->id,
            'podcast_guest_id'            => $guest->id,
        ]);
    }

    public function test_attach_is_idempotent(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $guest   = PodcastGuest::factory()->create();

        $this->actingAs($user)->post(route('podcast_episodes_planning.guests.attach', [$episode, $guest]));
        $this->actingAs($user)->post(route('podcast_episodes_planning.guests.attach', [$episode, $guest]));

        $this->assertSame(1, \DB::table('podcast_guest_episode_planning')
            ->where('podcast_episode_planning_id', $episode->id)
            ->where('podcast_guest_id', $guest->id)
            ->count());
    }

    public function test_attach_redirects_with_error_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $ep    = $this->makeEpisode($other);
        $guest = PodcastGuest::factory()->create();

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.guests.attach', [$ep, $guest]))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_attach_redirects_unauthenticated_users(): void
    {
        $ep    = $this->makeEpisode(User::factory()->create());
        $guest = PodcastGuest::factory()->create();

        $this->post(route('podcast_episodes_planning.guests.attach', [$ep, $guest]))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // detach
    // -------------------------------------------------------------------------

    public function test_detach_removes_guest_from_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $guest   = PodcastGuest::factory()->create();

        $episode->guests()->attach($guest->id);

        $this->actingAs($user)
            ->delete(route('podcast_episodes_planning.guests.detach', [$episode, $guest]))
            ->assertRedirect(route('podcast_episodes_planning.show', $episode))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('podcast_guest_episode_planning', [
            'podcast_episode_planning_id' => $episode->id,
            'podcast_guest_id'            => $guest->id,
        ]);
    }

    public function test_detach_redirects_with_error_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $ep    = $this->makeEpisode($other);
        $guest = PodcastGuest::factory()->create();

        $this->actingAs($user)
            ->delete(route('podcast_episodes_planning.guests.detach', [$ep, $guest]))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_detach_redirects_unauthenticated_users(): void
    {
        $ep    = $this->makeEpisode(User::factory()->create());
        $guest = PodcastGuest::factory()->create();

        $this->delete(route('podcast_episodes_planning.guests.detach', [$ep, $guest]))
            ->assertRedirect(route('login'));
    }
}