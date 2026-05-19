<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Shows;

use App\Models\User;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PodcastShowControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Minimum valid payload for creating/updating a podcast show. */
    private function showPayload(array $overrides = []): array
    {
        return array_merge([
            'title'       => 'My Test Show',
            'description' => 'A show description.',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_shows_only_the_authenticated_users_shows(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        PodcastShow::factory()->create(['user_id' => $user->id,  'title' => 'My Show']);
        PodcastShow::factory()->create(['user_id' => $other->id, 'title' => 'Their Show']);

        $this->actingAs($user)
            ->get(route('podcast_shows.index'))
            ->assertOk()
            ->assertSee('Podcast Shows')
            ->assertDontSee('Their Show');
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_shows.index'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_create_shows_form_to_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_shows.create'))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_show_and_assigns_it_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('podcast_shows.store'), $this->showPayload())
            ->assertRedirect(route('podcast_shows.index'));

        $this->assertDatabaseHas('podcast_shows', [
            'user_id' => $user->id,
            'title'   => 'My Test Show',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_shows.store'), [])
            ->assertSessionHasErrors(['title', 'description']);
    }

    public function test_store_validates_url_fields(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_shows.store'), $this->showPayload([
                'rss_link'     => 'not-a-url',
                'itunes_image' => 'not-a-url',
            ]))
            ->assertSessionHasErrors(['rss_link', 'itunes_image']);
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_displays_the_users_own_show(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id, 'title' => 'My Show']);

        $this->actingAs($user)
            ->get(route('podcast_shows.show', $show))
            ->assertOk()
            ->assertSee('My Show');
    }

    public function test_show_returns_403_for_another_users_show(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $show  = PodcastShow::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->get(route('podcast_shows.show', $show))
            ->assertForbidden();
    }

    public function test_show_returns_404_for_non_existent_show(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_shows.show', 99999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    public function test_edit_shows_form_to_the_shows_owner(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('podcast_shows.edit', $show))
            ->assertOk();
    }

    public function test_edit_returns_403_for_another_users_show(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $show  = PodcastShow::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->get(route('podcast_shows.edit', $show))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_saves_changes_to_the_show(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id, 'title' => 'Old Title']);

        $this->actingAs($user)
            ->put(route('podcast_shows.update', $show), $this->showPayload(['title' => 'New Title']))
            ->assertRedirect(route('podcast_shows.show', $show));

        $this->assertDatabaseHas('podcast_shows', ['id' => $show->id, 'title' => 'New Title']);
    }

    public function test_update_returns_403_for_another_users_show(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $show  = PodcastShow::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->put(route('podcast_shows.update', $show), $this->showPayload())
            ->assertForbidden();
    }

    public function test_update_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->put(route('podcast_shows.update', $show), [])
            ->assertSessionHasErrors(['title', 'description']);
    }


    public function test_update_saves_intro_and_outro_templates(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->put(route('podcast_shows.update', $show), $this->showPayload([
                'intro_template' => 'Welcome to {{title}}, episode {{episode_number}}.',
                'outro_template' => 'Thanks for listening. See you next time.',
            ]))
            ->assertRedirect(route('podcast_shows.show', $show));

        $this->assertDatabaseHas('podcast_shows', [
            'id'             => $show->id,
            'intro_template' => 'Welcome to {{title}}, episode {{episode_number}}.',
            'outro_template' => 'Thanks for listening. See you next time.',
        ]);
    }

    public function test_update_allows_null_intro_and_outro_templates(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create([
            'user_id'        => $user->id,
            'intro_template' => 'Old intro.',
            'outro_template' => 'Old outro.',
        ]);

        $this->actingAs($user)
            ->put(route('podcast_shows.update', $show), $this->showPayload([
                'intro_template' => null,
                'outro_template' => null,
            ]))
            ->assertRedirect(route('podcast_shows.show', $show));

        $this->assertDatabaseHas('podcast_shows', [
            'id'             => $show->id,
            'intro_template' => null,
            'outro_template' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // deleteConfirm
    // -------------------------------------------------------------------------

    public function test_delete_confirm_shows_page_to_the_shows_owner(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('podcast_shows.delete.confirm', $show))
            ->assertOk()
            ->assertSee($show->title);
    }

    public function test_delete_confirm_returns_403_for_another_users_show(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $show  = PodcastShow::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->get(route('podcast_shows.delete.confirm', $show))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_the_show_and_redirects(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('podcast_shows.destroy', $show))
            ->assertRedirect(route('podcast_shows.index'));

        $this->assertDatabaseMissing('podcast_shows', ['id' => $show->id]);
    }

    public function test_destroy_returns_403_for_another_users_show(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $show  = PodcastShow::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->delete(route('podcast_shows.destroy', $show))
            ->assertForbidden();

        $this->assertDatabaseHas('podcast_shows', ['id' => $show->id]);
    }

    public function test_destroy_returns_404_for_non_existent_show(): void
    {
        $this->actingAs(User::factory()->create())
            ->delete(route('podcast_shows.destroy', 99999))
            ->assertNotFound();
    }

    public function test_destroy_cannot_delete_a_show_that_has_episodes(): void
    {
        $user    = User::factory()->create();
        $show    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $episode = PodcastEpisode::factory()->create(['podcast_show_id' => $show->id]);

        $this->actingAs($user)
            ->delete(route('podcast_shows.destroy', $show))
            ->assertRedirect(route('podcast_shows.delete.confirm', $show))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('podcast_shows', ['id' => $show->id]);
    }

}