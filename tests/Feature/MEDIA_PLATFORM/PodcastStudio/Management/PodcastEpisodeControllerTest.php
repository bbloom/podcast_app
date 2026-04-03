<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\Management;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use Tests\TestCase;

class PodcastEpisodeControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a minimum valid episode payload.
     * Requires a show already created in the DB.
     */
    private function episodePayload(PodcastShow $show, array $overrides = []): array
    {
        return array_merge([
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::created->value,
            'title'           => 'My Test Episode',
        ], $overrides);
    }

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
            'status'          => PodcastEpisodeStatus::created,
        ]);
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_shows_only_the_authenticated_users_episodes(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $myShow    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $theirShow = PodcastShow::factory()->create(['user_id' => $other->id]);

        PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $myShow->id,
            'status'          => PodcastEpisodeStatus::created,
            'title'           => 'My Episode',
        ]);

        PodcastEpisode::factory()->create([
            'user_id'         => $other->id,
            'podcast_show_id' => $theirShow->id,
            'status'          => PodcastEpisodeStatus::created,
            'title'           => 'Their Episode',
        ]);

        $this->actingAs($user)
            ->get(route('podcast_episodes.index'))
            ->assertOk()
            ->assertSee('My Episode')
            ->assertDontSee('Their Episode');
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_episodes.index'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    // The standard CRUD create is now handled by the CREATE PODCAST EPISODE wizard


    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    // The STORE is not handled by the CREATE PODCAST EPISODE wizard


    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_displays_the_users_own_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);
        $episode->title = 'My Episode';
        $episode->save();

        $this->actingAs($user)
            ->get(route('podcast_episodes.show', $episode))
            ->assertOk()
            ->assertSee('My Episode');
    }

    public function test_show_returns_403_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($other);

        $this->actingAs($user)
            ->get(route('podcast_episodes.show', $episode))
            ->assertForbidden();
    }

    public function test_show_returns_404_for_non_existent_episode(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes.show', 99999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    public function test_edit_shows_form_to_the_episodes_owner(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes.edit', $episode))
            ->assertOk();
    }

    public function test_edit_returns_403_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($other);

        $this->actingAs($user)
            ->get(route('podcast_episodes.edit', $episode))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_saves_changes_to_the_episode(): void
    {
        $user    = User::factory()->create();
        $show    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $episode = PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::created,
            'title'           => 'Old Title',
        ]);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $this->episodePayload($show, ['title' => 'New Title']))
            ->assertRedirect(route('podcast_episodes.show', $episode));

        $this->assertDatabaseHas('podcast_episodes', ['id' => $episode->id, 'title' => 'New Title']);
    }

    public function test_update_returns_403_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $show    = PodcastShow::factory()->create(['user_id' => $other->id]);
        $episode = PodcastEpisode::factory()->create([
            'user_id'         => $other->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::created,
        ]);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $this->episodePayload($show))
            ->assertForbidden();
    }

    public function test_update_returns_403_when_reassigning_to_another_users_show(): void
    {
        $user      = User::factory()->create();
        $other     = User::factory()->create();
        $myShow    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $theirShow = PodcastShow::factory()->create(['user_id' => $other->id]);
        $episode   = PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $myShow->id,
            'status'          => PodcastEpisodeStatus::created,
        ]);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $this->episodePayload($theirShow))
            ->assertForbidden();
    }

    public function test_update_validates_required_fields(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), [])
            ->assertSessionHasErrors([
                'podcast_show_id',
                'status',
                'title',
            ]);
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
            'podcast_link_id'    => \MediaPlatform\PodcastStudio\Management\Models\PodcastLink::factory()->create()->id,
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
            'podcast_guest_id'   => \MediaPlatform\PodcastStudio\Management\Models\PodcastGuest::factory()->create()->id,
        ]);

        $this->actingAs($user)
            ->get(route('podcast_episodes.delete.confirm', $episode))
            ->assertOk()
            ->assertSee('This episode cannot be deleted.');
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

        $this->assertDatabaseHas('podcast_episodes', ['id' => $episode->id]);
    }

    public function test_destroy_is_blocked_when_episode_has_links(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        \DB::table('podcast_link_episode')->insert([
            'podcast_episode_id' => $episode->id,
            'podcast_link_id'    => \MediaPlatform\PodcastStudio\Management\Models\PodcastLink::factory()->create()->id,
        ]);

        $this->actingAs($user)
            ->delete(route('podcast_episodes.destroy', $episode))
            ->assertRedirect(route('podcast_episodes.show', $episode))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('podcast_episodes', ['id' => $episode->id]);
    }

    public function test_destroy_is_blocked_when_episode_has_guests(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        \DB::table('podcast_guest_episode')->insert([
            'podcast_episode_id' => $episode->id,
            'podcast_guest_id'   => \MediaPlatform\PodcastStudio\Management\Models\PodcastGuest::factory()->create()->id,
        ]);

        $this->actingAs($user)
            ->delete(route('podcast_episodes.destroy', $episode))
            ->assertRedirect(route('podcast_episodes.show', $episode))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('podcast_episodes', ['id' => $episode->id]);
    }
}