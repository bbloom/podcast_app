<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Links;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Links\Models\PodcastLink;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class PodcastLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Minimum valid payload for creating/updating a podcast link. */
    private function linkPayload(array $overrides = []): array
    {
        return array_merge([
            'link'    => 'https://example.com/resource',
            'enabled' => '1',
        ], $overrides);
    }

    /** Create a PodcastEpisode with the required show relationship. */
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

    public function test_index_shows_links_to_authenticated_users(): void
    {
        $link = PodcastLink::factory()->create(['title' => 'My Resource']);

        $this->actingAs(User::factory()->create())
            ->get(route('podcast_links.index'))
            ->assertOk()
            ->assertSee('My Resource');
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_links.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_defaults_to_id_descending(): void
    {
        PodcastLink::factory()->create(['title' => 'Alpha']);
        PodcastLink::factory()->create(['title' => 'Beta']);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('podcast_links.index'))
            ->assertOk();

        // Beta was created second (higher ID) — it should appear first (lower position in HTML).
        $this->assertLessThan(
            strpos($response->getContent(), 'Alpha'),
            strpos($response->getContent(), 'Beta')
        );
    }

    public function test_index_sorts_by_title_ascending(): void
    {
        PodcastLink::factory()->create(['title' => 'Zebra']);
        PodcastLink::factory()->create(['title' => 'Apple']);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('podcast_links.index', ['sort' => 'title', 'direction' => 'asc']))
            ->assertOk();

        // Apple should appear before Zebra.
        $this->assertLessThan(
            strpos($response->getContent(), 'Zebra'),
            strpos($response->getContent(), 'Apple')
        );
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_create_shows_form_to_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_links.create'))
            ->assertOk();
    }

    public function test_create_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_links.create'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_redirects_back_to_create_when_url_already_exists(): void
    {
        PodcastLink::factory()->create([
            'link'  => 'https://example.com/article',
            'title' => 'Existing Article',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('podcast_links.store'), ['link' => 'https://example.com/article'])
            ->assertRedirect(route('podcast_links.create'))
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('podcast_links', 1);
    }    

    public function test_store_redirects_unauthenticated_users(): void
    {
        $this->post(route('podcast_links.store'), ['link' => 'https://example.com/article'])
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_displays_link_to_authenticated_users(): void
    {
        $link = PodcastLink::factory()->create(['title' => 'My Resource']);

        $this->actingAs(User::factory()->create())
            ->get(route('podcast_links.show', $link))
            ->assertOk()
            ->assertSee('My Resource');
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $link = PodcastLink::factory()->create();

        $this->get(route('podcast_links.show', $link))
            ->assertRedirect(route('login'));
    }

    public function test_show_returns_404_for_non_existent_link(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_links.show', 99999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    public function test_edit_shows_form_to_authenticated_users(): void
    {
        $link = PodcastLink::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('podcast_links.edit', $link))
            ->assertOk();
    }

    public function test_edit_redirects_unauthenticated_users(): void
    {
        $link = PodcastLink::factory()->create();

        $this->get(route('podcast_links.edit', $link))
            ->assertRedirect(route('login'));
    }

    public function test_edit_returns_404_for_non_existent_link(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_links.edit', 99999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_saves_changes_and_redirects(): void
    {
        $link = PodcastLink::factory()->create(['link' => 'https://old.com']);

        $this->actingAs(User::factory()->create())
            ->put(route('podcast_links.update', $link), $this->linkPayload(['link' => 'https://new.com']))
            ->assertRedirect(route('podcast_links.index'));

        $this->assertDatabaseHas('podcast_links', ['id' => $link->id, 'link' => 'https://new.com']);
    }

    public function test_update_redirects_unauthenticated_users(): void
    {
        $link = PodcastLink::factory()->create();

        $this->put(route('podcast_links.update', $link), $this->linkPayload())
            ->assertRedirect(route('login'));
    }

    public function test_update_validates_required_fields(): void
    {
        $link = PodcastLink::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('podcast_links.update', $link), [])
            ->assertSessionHasErrors(['link']);
    }

    public function test_update_returns_404_for_non_existent_link(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('podcast_links.update', 99999), $this->linkPayload())
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // deleteConfirm
    // -------------------------------------------------------------------------

    public function test_delete_confirm_shows_page_to_authenticated_users(): void
    {
        $link = PodcastLink::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('podcast_links.delete.confirm', $link))
            ->assertOk();
    }

    public function test_delete_confirm_redirects_unauthenticated_users(): void
    {
        $link = PodcastLink::factory()->create();

        $this->get(route('podcast_links.delete.confirm', $link))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_link_and_redirects(): void
    {
        $link = PodcastLink::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('podcast_links.destroy', $link))
            ->assertRedirect(route('podcast_links.index'));

        $this->assertDatabaseMissing('podcast_links', ['id' => $link->id]);
    }

    public function test_destroy_redirects_unauthenticated_users(): void
    {
        $link = PodcastLink::factory()->create();

        $this->delete(route('podcast_links.destroy', $link))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('podcast_links', ['id' => $link->id]);
    }

    public function test_destroy_returns_404_for_non_existent_link(): void
    {
        $this->actingAs(User::factory()->create())
            ->delete(route('podcast_links.destroy', 99999))
            ->assertNotFound();
    }

    public function test_destroy_blocks_deletion_when_link_is_attached_to_an_episode(): void
    {
        $user    = User::factory()->create();
        $link    = PodcastLink::factory()->create();
        $episode = $this->makeEpisode($user);

        $episode->links()->attach($link->id);

        $this->actingAs($user)
            ->delete(route('podcast_links.destroy', $link))
            ->assertRedirect(route('podcast_links.delete.confirm', $link))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('podcast_links', ['id' => $link->id]);
    }

    // -------------------------------------------------------------------------
    // attachIndex
    // -------------------------------------------------------------------------

    public function test_attach_index_shows_enabled_unattached_links(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $available = PodcastLink::factory()->create(['title' => 'Available Link', 'enabled' => true]);
        $disabled  = PodcastLink::factory()->create(['title' => 'Disabled Link',  'enabled' => false]);
        $attached  = PodcastLink::factory()->create(['title' => 'Attached Link',  'enabled' => true]);

        $episode->links()->attach($attached->id);

        $this->actingAs($user)
            ->get(route('podcast_links.attach.index', $episode))
            ->assertOk()
            ->assertSee('Available Link')
            ->assertDontSee('Disabled Link')
            ->assertDontSee('Attached Link');
    }

    public function test_attach_index_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->get(route('podcast_links.attach.index', $episode))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // attach
    // -------------------------------------------------------------------------

    public function test_attach_links_a_link_to_an_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $link    = PodcastLink::factory()->create();

        $this->actingAs($user)
            ->post(route('podcast_links.attach', [$episode, $link]))
            ->assertRedirect(route('podcast_episodes.show', $episode));

        $this->assertDatabaseHas('podcast_link_episode', [
            'podcast_episode_id' => $episode->id,
            'podcast_link_id'    => $link->id,
        ]);
    }

    public function test_attach_is_idempotent_and_does_not_create_duplicates(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $link    = PodcastLink::factory()->create();

        $this->actingAs($user)
            ->post(route('podcast_links.attach', [$episode, $link]));

        $this->actingAs($user)
            ->post(route('podcast_links.attach', [$episode, $link]));

        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('podcast_link_episode')
            ->where('podcast_episode_id', $episode->id)
            ->where('podcast_link_id', $link->id)
            ->count());
    }

    public function test_attach_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $link    = PodcastLink::factory()->create();

        $this->post(route('podcast_links.attach', [$episode, $link]))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // detach
    // -------------------------------------------------------------------------

    public function test_detach_removes_link_from_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $link    = PodcastLink::factory()->create();

        $episode->links()->attach($link->id);

        $this->actingAs($user)
            ->delete(route('podcast_links.detach', [$episode, $link]))
            ->assertRedirect(route('podcast_episodes.show', $episode));

        $this->assertDatabaseMissing('podcast_link_episode', [
            'podcast_episode_id' => $episode->id,
            'podcast_link_id'    => $link->id,
        ]);
    }

    public function test_detach_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $link    = PodcastLink::factory()->create();

        $episode->links()->attach($link->id);

        $this->delete(route('podcast_links.detach', [$episode, $link]))
            ->assertRedirect(route('login'));
    }
}