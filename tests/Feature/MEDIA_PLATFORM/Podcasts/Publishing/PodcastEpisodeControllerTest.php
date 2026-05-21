<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing;

use App\Models\User;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PodcastEpisodeControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a show and episode belonging to the given user.
     * Returns the episode.
     */
    private function episodeForUser(User $user): PodcastEpisode
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::ready_to_upload_recording,
        ]);
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_shows_episodes_to_authenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes.index'))
            ->assertOk()
            ->assertSee($episode->title);
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_episodes.index'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_displays_the_users_own_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes.show', $episode))
            ->assertOk()
            ->assertSee($episode->title);
    }

    public function test_show_redirects_with_error_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($other);

        $this->actingAs($user)
            ->get(route('podcast_episodes.show', $episode))
            ->assertRedirect(route('podcast_episodes.index'))
            ->assertSessionHas('error');
    }

    // -------------------------------------------------------------------------
    // edit and update — see PodcastEpisodeUpdateControllerTest
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    // deleteConfirm — ownership
    // -------------------------------------------------------------------------

    public function test_delete_confirm_redirects_with_error_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($other);

        $this->actingAs($user)
            ->get(route('podcast_episodes.delete.confirm', $episode))
            ->assertRedirect(route('podcast_episodes.index'))
            ->assertSessionHas('error');
    }

    // -------------------------------------------------------------------------
    // deleteConfirm — blocking conditions
    // -------------------------------------------------------------------------

    public function test_delete_confirm_is_blocked_when_episode_is_published(): void
    {
        $user    = User::factory()->create();
        $show    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $episode = PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::published,
        ]);

        $this->actingAs($user)
            ->get(route('podcast_episodes.delete.confirm', $episode))
            ->assertOk()
            ->assertSee('This episode cannot be deleted.');
    }

    public function test_delete_confirm_is_blocked_when_episode_has_links(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        \DB::table('podcast_link_episode')->insert([
            'podcast_episode_id' => $episode->id,
            'podcast_link_id'    => \MediaPlatform\Podcasts\Links\Models\PodcastLink::factory()->create()->id,
        ]);

        $this->actingAs($user)
            ->get(route('podcast_episodes.delete.confirm', $episode))
            ->assertOk()
            ->assertSee('This episode cannot be deleted.');
    }

    public function test_delete_confirm_is_blocked_when_episode_has_guests(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        \DB::table('podcast_guest_episode')->insert([
            'podcast_episode_id' => $episode->id,
            'podcast_guest_id'   => \MediaPlatform\Podcasts\Guests\Models\PodcastGuest::factory()->create()->id,
        ]);

        $this->actingAs($user)
            ->get(route('podcast_episodes.delete.confirm', $episode))
            ->assertOk()
            ->assertSee('This episode cannot be deleted.');
    }

    // -------------------------------------------------------------------------
    // destroy — ownership
    // -------------------------------------------------------------------------

    public function test_destroy_redirects_with_error_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($other);

        $this->actingAs($user)
            ->delete(route('podcast_episodes.destroy', $episode))
            ->assertRedirect(route('podcast_episodes.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('podcast_episodes_published', ['id' => $episode->id]);
    }

    // -------------------------------------------------------------------------
    // destroy — blocking conditions
    // -------------------------------------------------------------------------

    public function test_destroy_is_blocked_when_episode_is_published(): void
    {
        $user    = User::factory()->create();
        $show    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $episode = PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::published,
        ]);

        $this->actingAs($user)
            ->delete(route('podcast_episodes.destroy', $episode))
            ->assertRedirect(route('podcast_episodes.show', $episode))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('podcast_episodes_published', ['id' => $episode->id]);
    }

    public function test_destroy_is_blocked_when_episode_has_links(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        \DB::table('podcast_link_episode')->insert([
            'podcast_episode_id' => $episode->id,
            'podcast_link_id'    => \MediaPlatform\Podcasts\Links\Models\PodcastLink::factory()->create()->id,
        ]);

        $this->actingAs($user)
            ->delete(route('podcast_episodes.destroy', $episode))
            ->assertRedirect(route('podcast_episodes.show', $episode))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('podcast_episodes_published', ['id' => $episode->id]);
    }

    public function test_destroy_is_blocked_when_episode_has_guests(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        \DB::table('podcast_guest_episode')->insert([
            'podcast_episode_id' => $episode->id,
            'podcast_guest_id'   => \MediaPlatform\Podcasts\Guests\Models\PodcastGuest::factory()->create()->id,
        ]);

        $this->actingAs($user)
            ->delete(route('podcast_episodes.destroy', $episode))
            ->assertRedirect(route('podcast_episodes.show', $episode))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('podcast_episodes_published', ['id' => $episode->id]);
    }
}