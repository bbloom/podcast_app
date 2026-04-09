<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\Management;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastGuest;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use Tests\TestCase;

class PodcastGuestControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function guestPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name'     => 'Jane Smith',
            'email_address' => 'jane@example.com',
            'profile_full'  => 'Jane is a long-time contributor to the PHP community.',
            'enabled'       => '1',
        ], $overrides);
    }

    private function makeEpisode(User $user): PodcastEpisode
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);
        return PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_shows_guests_to_authenticated_users(): void
    {
        PodcastGuest::factory()->create(['full_name' => 'Alice Example']);

        $this->actingAs(User::factory()->create())
            ->get(route('podcast_guests.index'))
            ->assertOk()
            ->assertSee('Alice Example');
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_guests.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_defaults_to_id_descending(): void
    {
        PodcastGuest::factory()->create(['full_name' => 'Alpha Guest']);
        PodcastGuest::factory()->create(['full_name' => 'Beta Guest']);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('podcast_guests.index'))
            ->assertOk();

        $this->assertLessThan(
            strpos($response->getContent(), 'Alpha Guest'),
            strpos($response->getContent(), 'Beta Guest')
        );
    }

    public function test_index_sorts_by_full_name_ascending(): void
    {
        PodcastGuest::factory()->create(['full_name' => 'Zebra Guest']);
        PodcastGuest::factory()->create(['full_name' => 'Apple Guest']);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('podcast_guests.index', ['sort' => 'full_name', 'direction' => 'asc']))
            ->assertOk();

        $this->assertLessThan(
            strpos($response->getContent(), 'Zebra Guest'),
            strpos($response->getContent(), 'Apple Guest')
        );
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_create_shows_form_to_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_guests.create'))
            ->assertOk();
    }

    public function test_create_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_guests.create'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_guest_and_redirects_to_show(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_guests.store'), $this->guestPayload())
            ->assertRedirect(route('podcast_guests.show', PodcastGuest::first()));

        $this->assertDatabaseHas('podcast_guests', ['full_name' => 'Jane Smith']);
    }

    public function test_store_auto_generates_slug_from_full_name(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_guests.store'), $this->guestPayload(['full_name' => 'Jane Smith']));

        $this->assertDatabaseHas('podcast_guests', [
            'full_name' => 'Jane Smith',
            'slug'      => 'jane-smith',
        ]);
    }

    public function test_store_redirects_unauthenticated_users(): void
    {
        $this->post(route('podcast_guests.store'), $this->guestPayload())
            ->assertRedirect(route('login'));
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_guests.store'), [])
            ->assertSessionHasErrors(['full_name', 'email_address', 'profile_full', 'enabled']);
    }

    public function test_store_validates_full_name_uniqueness(): void
    {
        PodcastGuest::factory()->create(['full_name' => 'Jane Smith']);

        $this->actingAs(User::factory()->create())
            ->post(route('podcast_guests.store'), $this->guestPayload())
            ->assertSessionHasErrors(['full_name']);
    }

    public function test_store_validates_url_fields(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_guests.store'), $this->guestPayload([
                'image_url'             => 'not-a-url',
                'image_thumbnail_url'   => 'not-a-url',
                'link_to_guest_website' => 'not-a-url',
            ]))
            ->assertSessionHasErrors(['image_url', 'image_thumbnail_url', 'link_to_guest_website']);
    }

    public function test_store_validates_email_field(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_guests.store'), $this->guestPayload([
                'email_address' => 'not-an-email',
            ]))
            ->assertSessionHasErrors(['email_address']);
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_displays_guest_to_authenticated_users(): void
    {
        $guest = PodcastGuest::factory()->create(['full_name' => 'Jane Smith']);

        $this->actingAs(User::factory()->create())
            ->get(route('podcast_guests.show', $guest))
            ->assertOk()
            ->assertSee('Jane Smith')
            ->assertSee($guest->slug);
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->get(route('podcast_guests.show', $guest))
            ->assertRedirect(route('login'));
    }

    public function test_show_returns_404_for_non_existent_guest(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_guests.show', 99999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    public function test_edit_shows_form_to_authenticated_users(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('podcast_guests.edit', $guest))
            ->assertOk()
            ->assertSee($guest->full_name);
    }

    public function test_edit_redirects_unauthenticated_users(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->get(route('podcast_guests.edit', $guest))
            ->assertRedirect(route('login'));
    }

    public function test_edit_returns_404_for_non_existent_guest(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_guests.edit', 99999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_saves_changes_and_redirects_to_show(): void
    {
        $guest = PodcastGuest::factory()->create(['full_name' => 'Old Name']);

        $this->actingAs(User::factory()->create())
            ->put(route('podcast_guests.update', $guest), $this->guestPayload(['full_name' => 'New Name']))
            ->assertRedirect(route('podcast_guests.show', $guest));

        $this->assertDatabaseHas('podcast_guests', ['id' => $guest->id, 'full_name' => 'New Name']);
    }

    public function test_update_regenerates_slug_when_full_name_changes(): void
    {
        $guest = PodcastGuest::factory()->create(['full_name' => 'Old Name']);

        $this->actingAs(User::factory()->create())
            ->put(route('podcast_guests.update', $guest), $this->guestPayload(['full_name' => 'New Name']));

        $this->assertDatabaseHas('podcast_guests', [
            'id'   => $guest->id,
            'slug' => 'new-name',
        ]);
    }

    public function test_update_does_not_change_slug_when_full_name_unchanged(): void
    {
        $guest = PodcastGuest::factory()->create(['full_name' => 'Jane Smith']);
        $originalSlug = $guest->slug;

        $this->actingAs(User::factory()->create())
            ->put(route('podcast_guests.update', $guest), $this->guestPayload([
                'full_name'    => 'Jane Smith',
                'profile_short' => 'Updated tagline',
            ]));

        $this->assertDatabaseHas('podcast_guests', [
            'id'   => $guest->id,
            'slug' => $originalSlug,
        ]);
    }

    public function test_update_redirects_unauthenticated_users(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->put(route('podcast_guests.update', $guest), $this->guestPayload())
            ->assertRedirect(route('login'));
    }

    public function test_update_validates_required_fields(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('podcast_guests.update', $guest), [])
            ->assertSessionHasErrors(['full_name', 'email_address', 'profile_full', 'enabled']);
    }

    public function test_update_allows_guest_to_keep_its_own_full_name(): void
    {
        $guest = PodcastGuest::factory()->create(['full_name' => 'Jane Smith']);

        $this->actingAs(User::factory()->create())
            ->put(route('podcast_guests.update', $guest), $this->guestPayload(['full_name' => 'Jane Smith']))
            ->assertRedirect(route('podcast_guests.show', $guest));
    }

    public function test_update_returns_404_for_non_existent_guest(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('podcast_guests.update', 99999), $this->guestPayload())
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // deleteConfirm
    // -------------------------------------------------------------------------

    public function test_delete_confirm_shows_page_to_authenticated_users(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('podcast_guests.delete.confirm', $guest))
            ->assertOk()
            ->assertSee($guest->full_name);
    }

    public function test_delete_confirm_redirects_unauthenticated_users(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->get(route('podcast_guests.delete.confirm', $guest))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_guest_and_redirects(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('podcast_guests.destroy', $guest))
            ->assertRedirect(route('podcast_guests.index'));

        $this->assertDatabaseMissing('podcast_guests', ['id' => $guest->id]);
    }

    public function test_destroy_redirects_unauthenticated_users(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->delete(route('podcast_guests.destroy', $guest))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('podcast_guests', ['id' => $guest->id]);
    }

    public function test_destroy_returns_404_for_non_existent_guest(): void
    {
        $this->actingAs(User::factory()->create())
            ->delete(route('podcast_guests.destroy', 99999))
            ->assertNotFound();
    }

    public function test_destroy_blocks_deletion_when_guest_is_attached_to_an_episode(): void
    {
        $user    = User::factory()->create();
        $guest   = PodcastGuest::factory()->create();
        $episode = $this->makeEpisode($user);

        $episode->guests()->attach($guest->id);

        $this->actingAs($user)
            ->delete(route('podcast_guests.destroy', $guest))
            ->assertRedirect(route('podcast_guests.delete.confirm', $guest))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('podcast_guests', ['id' => $guest->id]);
    }

    // -------------------------------------------------------------------------
    // attachEpisodeIndex (from guest show view)
    // -------------------------------------------------------------------------

    public function test_attach_episode_index_shows_unattached_episodes(): void
    {
        $user      = User::factory()->create();
        $guest     = PodcastGuest::factory()->create();
        $attached  = $this->makeEpisode($user);
        $available = $this->makeEpisode($user);

        $guest->episodes()->attach($attached->id);

        $this->actingAs($user)
            ->get(route('podcast_guests.attach.episode.index', $guest))
            ->assertOk()
            ->assertSee($available->title)
            ->assertDontSee($attached->title);
    }

    public function test_attach_episode_index_redirects_unauthenticated_users(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->get(route('podcast_guests.attach.episode.index', $guest))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // attachEpisode (from guest show view)
    // -------------------------------------------------------------------------

    public function test_attach_episode_links_episode_to_guest(): void
    {
        $user    = User::factory()->create();
        $guest   = PodcastGuest::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->post(route('podcast_guests.attach.episode', [$guest, $episode]))
            ->assertRedirect(route('podcast_guests.show', $guest));

        $this->assertDatabaseHas('podcast_guest_episode', [
            'podcast_guest_id'   => $guest->id,
            'podcast_episode_id' => $episode->id,
        ]);
    }

    public function test_attach_episode_is_idempotent(): void
    {
        $user    = User::factory()->create();
        $guest   = PodcastGuest::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)->post(route('podcast_guests.attach.episode', [$guest, $episode]));
        $this->actingAs($user)->post(route('podcast_guests.attach.episode', [$guest, $episode]));

        $this->assertSame(1, DB::table('podcast_guest_episode')
            ->where('podcast_guest_id', $guest->id)
            ->where('podcast_episode_id', $episode->id)
            ->count());
    }

    public function test_attach_episode_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $guest   = PodcastGuest::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->post(route('podcast_guests.attach.episode', [$guest, $episode]))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // detachEpisode (from guest show view)
    // -------------------------------------------------------------------------

    public function test_detach_episode_removes_episode_from_guest(): void
    {
        $user    = User::factory()->create();
        $guest   = PodcastGuest::factory()->create();
        $episode = $this->makeEpisode($user);

        $guest->episodes()->attach($episode->id);

        $this->actingAs($user)
            ->delete(route('podcast_guests.detach.episode', [$guest, $episode]))
            ->assertRedirect(route('podcast_guests.show', $guest));

        $this->assertDatabaseMissing('podcast_guest_episode', [
            'podcast_guest_id'   => $guest->id,
            'podcast_episode_id' => $episode->id,
        ]);
    }

    public function test_detach_episode_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $guest   = PodcastGuest::factory()->create();
        $episode = $this->makeEpisode($user);

        $guest->episodes()->attach($episode->id);

        $this->delete(route('podcast_guests.detach.episode', [$guest, $episode]))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // attachGuestIndex (from episode show view)
    // -------------------------------------------------------------------------

    public function test_attach_guest_index_shows_enabled_unattached_guests(): void
    {
        $user      = User::factory()->create();
        $episode   = $this->makeEpisode($user);
        $available = PodcastGuest::factory()->create(['full_name' => 'Available Guest', 'enabled' => true]);
        $disabled  = PodcastGuest::factory()->create(['full_name' => 'Disabled Guest',  'enabled' => false]);
        $attached  = PodcastGuest::factory()->create(['full_name' => 'Attached Guest',  'enabled' => true]);

        $episode->guests()->attach($attached->id);

        $this->actingAs($user)
            ->get(route('podcast_guests.attach.guest.index', $episode))
            ->assertOk()
            ->assertSee('Available Guest')
            ->assertDontSee('Disabled Guest')
            ->assertDontSee('Attached Guest');
    }

    public function test_attach_guest_index_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->get(route('podcast_guests.attach.guest.index', $episode))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // attachGuest (from episode show view)
    // -------------------------------------------------------------------------

    public function test_attach_guest_links_guest_to_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $guest   = PodcastGuest::factory()->create();

        $this->actingAs($user)
            ->post(route('podcast_guests.attach.guest', [$episode, $guest]))
            ->assertRedirect(route('podcast_episodes.show', $episode));

        $this->assertDatabaseHas('podcast_guest_episode', [
            'podcast_guest_id'   => $guest->id,
            'podcast_episode_id' => $episode->id,
        ]);
    }

    public function test_attach_guest_is_idempotent(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $guest   = PodcastGuest::factory()->create();

        $this->actingAs($user)->post(route('podcast_guests.attach.guest', [$episode, $guest]));
        $this->actingAs($user)->post(route('podcast_guests.attach.guest', [$episode, $guest]));

        $this->assertSame(1, DB::table('podcast_guest_episode')
            ->where('podcast_guest_id', $guest->id)
            ->where('podcast_episode_id', $episode->id)
            ->count());
    }

    public function test_attach_guest_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $guest   = PodcastGuest::factory()->create();

        $this->post(route('podcast_guests.attach.guest', [$episode, $guest]))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // detachGuest (from episode show view)
    // -------------------------------------------------------------------------

    public function test_detach_guest_removes_guest_from_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $guest   = PodcastGuest::factory()->create();

        $episode->guests()->attach($guest->id);

        $this->actingAs($user)
            ->delete(route('podcast_guests.detach.guest', [$episode, $guest]))
            ->assertRedirect(route('podcast_episodes.show', $episode));

        $this->assertDatabaseMissing('podcast_guest_episode', [
            'podcast_guest_id'   => $guest->id,
            'podcast_episode_id' => $episode->id,
        ]);
    }

    public function test_detach_guest_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $guest   = PodcastGuest::factory()->create();

        $episode->guests()->attach($guest->id);

        $this->delete(route('podcast_guests.detach.guest', [$episode, $guest]))
            ->assertRedirect(route('login'));
    }
}