<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\Management;

use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisodeStatusLookup;
use Tests\TestCase;

class PodcastEpisodeStatusLookupControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Return a user whose email matches the admin gate. */
    private function adminUser(): User
    {
        return User::factory()->create(['email' => config('admin.admin_email')]);
    }

    /** Return a plain authenticated user (not admin). */
    private function regularUser(): User
    {
        return User::factory()->create(['email' => 'regular@example.com']);
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_shows_statuses_to_any_authenticated_user(): void
    {
        $user   = $this->regularUser();
        $status = PodcastEpisodeStatusLookup::factory()->create(['title' => 'Draft']);

        $this->actingAs($user)
            ->get(route('podcast_episode_status_lookup.index'))
            ->assertOk()
            ->assertSee('Draft');
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_episode_status_lookup.index'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_create_shows_form_to_admins(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('podcast_episode_status_lookup.create'))
            ->assertOk();
    }

    public function test_create_returns_403_for_non_admins(): void
    {
        $this->actingAs($this->regularUser())
            ->get(route('podcast_episode_status_lookup.create'))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_allows_admin_to_create_a_status(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('podcast_episode_status_lookup.store'), [
                'title'       => 'Published',
                'description' => 'The episode has been published.',
                'enabled'     => '1',
            ])
            ->assertRedirect(route('podcast_episode_status_lookup.index'));

        $this->assertDatabaseHas('podcast_episode_status_lookup', ['title' => 'Published']);
    }

    public function test_store_returns_403_for_non_admins(): void
    {
        $this->actingAs($this->regularUser())
            ->post(route('podcast_episode_status_lookup.store'), [
                'title'       => 'Published',
                'description' => 'Should not be created.',
                'enabled'     => '1',
            ])
            ->assertForbidden();
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('podcast_episode_status_lookup.store'), [])
            ->assertSessionHasErrors(['title', 'description', 'enabled']);
    }

    public function test_store_validates_title_uniqueness(): void
    {
        PodcastEpisodeStatusLookup::factory()->create(['title' => 'Draft']);

        $this->actingAs($this->adminUser())
            ->post(route('podcast_episode_status_lookup.store'), [
                'title'       => 'Draft',
                'description' => 'Duplicate.',
                'enabled'     => '1',
            ])
            ->assertSessionHasErrors(['title']);
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_displays_status_to_any_authenticated_user(): void
    {
        $status = PodcastEpisodeStatusLookup::factory()->create(['title' => 'Draft']);

        $this->actingAs($this->regularUser())
            ->get(route('podcast_episode_status_lookup.show', $status))
            ->assertOk()
            ->assertSee('Draft');
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $status = PodcastEpisodeStatusLookup::factory()->create();

        $this->get(route('podcast_episode_status_lookup.show', $status))
            ->assertRedirect(route('login'));
    }

    public function test_show_returns_404_for_non_existent_status(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('podcast_episode_status_lookup.show', 99999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    public function test_edit_shows_form_to_admins(): void
    {
        $status = PodcastEpisodeStatusLookup::factory()->create();

        $this->actingAs($this->adminUser())
            ->get(route('podcast_episode_status_lookup.edit', $status))
            ->assertOk()
            ->assertSee($status->title);
    }

    public function test_edit_returns_403_for_non_admins(): void
    {
        $status = PodcastEpisodeStatusLookup::factory()->create();

        $this->actingAs($this->regularUser())
            ->get(route('podcast_episode_status_lookup.edit', $status))
            ->assertForbidden();
    }

    public function test_edit_returns_404_for_non_existent_status(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('podcast_episode_status_lookup.edit', 99999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_allows_admin_to_update_a_status(): void
    {
        $status = PodcastEpisodeStatusLookup::factory()->create(['title' => 'Old Title']);

        $this->actingAs($this->adminUser())
            ->put(route('podcast_episode_status_lookup.update', $status), [
                'title'       => 'New Title',
                'description' => 'Updated description.',
                'enabled'     => '1',
            ])
            ->assertRedirect(route('podcast_episode_status_lookup.index'));

        $this->assertDatabaseHas('podcast_episode_status_lookup', ['title' => 'New Title']);
    }

    public function test_update_returns_403_for_non_admins(): void
    {
        $status = PodcastEpisodeStatusLookup::factory()->create();

        $this->actingAs($this->regularUser())
            ->put(route('podcast_episode_status_lookup.update', $status), [
                'title'       => 'Hacked',
                'description' => 'Should not update.',
                'enabled'     => '1',
            ])
            ->assertForbidden();
    }

    public function test_update_validates_required_fields(): void
    {
        $status = PodcastEpisodeStatusLookup::factory()->create();

        $this->actingAs($this->adminUser())
            ->put(route('podcast_episode_status_lookup.update', $status), [])
            ->assertSessionHasErrors(['title', 'description', 'enabled']);
    }

    public function test_update_allows_status_to_keep_its_own_title(): void
    {
        $status = PodcastEpisodeStatusLookup::factory()->create(['title' => 'Draft']);

        $this->actingAs($this->adminUser())
            ->put(route('podcast_episode_status_lookup.update', $status), [
                'title'       => 'Draft',
                'description' => 'Updated description.',
                'enabled'     => '1',
            ])
            ->assertRedirect(route('podcast_episode_status_lookup.index'));
    }

    // -------------------------------------------------------------------------
    // deleteConfirm
    // -------------------------------------------------------------------------

    public function test_delete_confirm_shows_page_to_admins(): void
    {
        $status = PodcastEpisodeStatusLookup::factory()->create();

        $this->actingAs($this->adminUser())
            ->get(route('podcast_episode_status_lookup.delete.confirm', $status))
            ->assertOk()
            ->assertSee($status->title);
    }

    public function test_delete_confirm_returns_403_for_non_admins(): void
    {
        $status = PodcastEpisodeStatusLookup::factory()->create();

        $this->actingAs($this->regularUser())
            ->get(route('podcast_episode_status_lookup.delete.confirm', $status))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_allows_admin_to_delete_a_status(): void
    {
        $status = PodcastEpisodeStatusLookup::factory()->create();

        $this->actingAs($this->adminUser())
            ->delete(route('podcast_episode_status_lookup.destroy', $status))
            ->assertRedirect(route('podcast_episode_status_lookup.index'));

        $this->assertDatabaseMissing('podcast_episode_status_lookup', ['id' => $status->id]);
    }

    public function test_destroy_returns_403_for_non_admins(): void
    {
        $status = PodcastEpisodeStatusLookup::factory()->create();

        $this->actingAs($this->regularUser())
            ->delete(route('podcast_episode_status_lookup.destroy', $status))
            ->assertForbidden();

        $this->assertDatabaseHas('podcast_episode_status_lookup', ['id' => $status->id]);
    }

    public function test_destroy_returns_404_for_non_existent_status(): void
    {
        $this->actingAs($this->adminUser())
            ->delete(route('podcast_episode_status_lookup.destroy', 99999))
            ->assertNotFound();
    }

    public function test_destroy_cannot_delete_a_status_that_is_in_use(): void
    {
        $status  = PodcastEpisodeStatusLookup::factory()->create();
        $episode = PodcastEpisode::factory()->create(['podcast_episode_status_lookup_id' => $status->id]);

        $this->actingAs($this->adminUser())
            ->delete(route('podcast_episode_status_lookup.destroy', $status))
            ->assertRedirect(route('podcast_episode_status_lookup.delete.confirm', $status))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('podcast_episode_status_lookup', ['id' => $status->id]);
    }
}