<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\Management;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisodeStatusLookup;
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
     * Requires a show and status already created in the DB.
     */
    private function episodePayload(PodcastShow $show, PodcastEpisodeStatusLookup $status, array $overrides = []): array
    {
        return array_merge([
            'podcast_show_id'                  => $show->id,
            'podcast_episode_status_lookup_id' => $status->id,
            'title'                            => 'My Test Episode',
        ], $overrides);
    }

    /**
     * Create a show, status, and episode all belonging to the given user.
     * Returns the episode.
     */
    private function episodeForUser(User $user): PodcastEpisode
    {
        $show   = PodcastShow::factory()->create(['user_id' => $user->id]);
        $status = PodcastEpisodeStatusLookup::factory()->create();

        return PodcastEpisode::factory()->create([
            'user_id'                          => $user->id,
            'podcast_show_id'                  => $show->id,
            'podcast_episode_status_lookup_id' => $status->id,
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
        $status    = PodcastEpisodeStatusLookup::factory()->create();

        PodcastEpisode::factory()->create([
            'user_id'                          => $user->id,
            'podcast_show_id'                  => $myShow->id,
            'podcast_episode_status_lookup_id' => $status->id,
            'title'                            => 'My Episode',
        ]);

        PodcastEpisode::factory()->create([
            'user_id'                          => $other->id,
            'podcast_show_id'                  => $theirShow->id,
            'podcast_episode_status_lookup_id' => $status->id,
            'title'                            => 'Their Episode',
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

    public function test_create_shows_form_with_shows_and_statuses(): void
    {
        $user   = User::factory()->create();
        $show   = PodcastShow::factory()->create(['user_id' => $user->id]);
        $status = PodcastEpisodeStatusLookup::factory()->create();

        $this->actingAs($user)
            ->get(route('podcast_episodes.create'))
            ->assertOk()
            ->assertSee($show->title)
            ->assertSee($status->title);
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_episode_and_assigns_it_to_the_authenticated_user(): void
    {
        $user   = User::factory()->create();
        $show   = PodcastShow::factory()->create(['user_id' => $user->id]);
        $status = PodcastEpisodeStatusLookup::factory()->create();

        $this->actingAs($user)
            ->post(route('podcast_episodes.store'), $this->episodePayload($show, $status))
            ->assertRedirect(route('podcast_episodes.index'));

        $this->assertDatabaseHas('podcast_episodes', [
            'user_id' => $user->id,
            'title'   => 'My Test Episode',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_episodes.store'), [])
            ->assertSessionHasErrors([
                'podcast_show_id',
                'podcast_episode_status_lookup_id',
                'title',
            ]);
    }

    public function test_store_returns_403_when_show_belongs_to_another_user(): void
    {
        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $show   = PodcastShow::factory()->create(['user_id' => $other->id]);
        $status = PodcastEpisodeStatusLookup::factory()->create();

        $this->actingAs($user)
            ->post(route('podcast_episodes.store'), $this->episodePayload($show, $status))
            ->assertForbidden();
    }

    public function test_store_validates_that_the_selected_show_exists(): void
    {
        $user   = User::factory()->create();
        $status = PodcastEpisodeStatusLookup::factory()->create();

        $this->actingAs($user)
            ->post(route('podcast_episodes.store'), [
                'podcast_show_id'                  => 99999,
                'podcast_episode_status_lookup_id' => $status->id,
                'title'                            => 'Bad Episode',
            ])
            ->assertSessionHasErrors(['podcast_show_id']);
    }

    public function test_store_validates_that_the_selected_status_exists(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('podcast_episodes.store'), [
                'podcast_show_id'                  => $show->id,
                'podcast_episode_status_lookup_id' => 99999,
                'title'                            => 'Bad Episode',
            ])
            ->assertSessionHasErrors(['podcast_episode_status_lookup_id']);
    }

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
        $status  = PodcastEpisodeStatusLookup::factory()->create();
        $episode = PodcastEpisode::factory()->create([
            'user_id'                          => $user->id,
            'podcast_show_id'                  => $show->id,
            'podcast_episode_status_lookup_id' => $status->id,
            'title'                            => 'Old Title',
        ]);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $this->episodePayload($show, $status, ['title' => 'New Title']))
            ->assertRedirect(route('podcast_episodes.show', $episode));

        $this->assertDatabaseHas('podcast_episodes', ['id' => $episode->id, 'title' => 'New Title']);
    }

    public function test_update_returns_403_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $show    = PodcastShow::factory()->create(['user_id' => $other->id]);
        $status  = PodcastEpisodeStatusLookup::factory()->create();
        $episode = PodcastEpisode::factory()->create([
            'user_id'                          => $other->id,
            'podcast_show_id'                  => $show->id,
            'podcast_episode_status_lookup_id' => $status->id,
        ]);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $this->episodePayload($show, $status))
            ->assertForbidden();
    }

    public function test_update_returns_403_when_reassigning_to_another_users_show(): void
    {
        $user      = User::factory()->create();
        $other     = User::factory()->create();
        $myShow    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $theirShow = PodcastShow::factory()->create(['user_id' => $other->id]);
        $status    = PodcastEpisodeStatusLookup::factory()->create();
        $episode   = PodcastEpisode::factory()->create([
            'user_id'                          => $user->id,
            'podcast_show_id'                  => $myShow->id,
            'podcast_episode_status_lookup_id' => $status->id,
        ]);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $this->episodePayload($theirShow, $status))
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
                'podcast_episode_status_lookup_id',
                'title',
            ]);
    }

    // -------------------------------------------------------------------------
    // deleteConfirm
    // -------------------------------------------------------------------------

    public function test_delete_confirm_shows_page_to_the_episodes_owner(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes.delete.confirm', $episode))
            ->assertOk()
            ->assertSee($episode->title);
    }

    public function test_delete_confirm_returns_403_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($other);

        $this->actingAs($user)
            ->get(route('podcast_episodes.delete.confirm', $episode))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_the_episode_and_redirects(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->delete(route('podcast_episodes.destroy', $episode))
            ->assertRedirect(route('podcast_episodes.index'));

        $this->assertDatabaseMissing('podcast_episodes', ['id' => $episode->id]);
    }

    public function test_destroy_returns_403_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($other);

        $this->actingAs($user)
            ->delete(route('podcast_episodes.destroy', $episode))
            ->assertForbidden();

        $this->assertDatabaseHas('podcast_episodes', ['id' => $episode->id]);
    }

    public function test_destroy_returns_404_for_non_existent_episode(): void
    {
        $this->actingAs(User::factory()->create())
            ->delete(route('podcast_episodes.destroy', 99999))
            ->assertNotFound();
    }
}