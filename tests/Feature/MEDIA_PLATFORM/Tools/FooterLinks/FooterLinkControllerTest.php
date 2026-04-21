<?php

// =============================================================================
// FooterLinkControllerTest
//
// Feature tests for FooterLinkController.
//
// Covers:
//   1.  index          — list, empty state, unauthenticated
//   2.  create         — renders form, pre-selects show, unauthenticated
//   3.  store          — happy path, validation, wrong-owner show
//   4.  show           — renders view, 403 wrong owner, 404
//   5.  edit           — renders view, 403 wrong owner
//   6.  update         — happy path, validation, 403
//   7.  deleteConfirm  — renders view, 403 wrong owner
//   8.  destroy        — deletes, redirects, 403 wrong owner
// =============================================================================

namespace Tests\Feature\MEDIA_PLATFORM\Tools\FooterLinks;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\Tools\FooterLinks\Models\FooterLink;
use Tests\TestCase;

class FooterLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeShowForUser(?User $user = null): PodcastShow
    {
        return PodcastShow::factory()->create(['user_id' => ($user ?? $this->user)->id]);
    }

    private function makeLinkForShow(PodcastShow $show, array $overrides = []): FooterLink
    {
        return FooterLink::factory()->forShow($show)->create($overrides);
    }

    private function validPayload(PodcastShow $show): array
    {
        return [
            'podcast_show_id' => $show->id,
            'link_name'       => 'Privacy Policy',
            'link_url'        => 'https://example.com/privacy',
            'link_order'      => 1,
        ];
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  1. index                                                              ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_index_renders_for_authenticated_user(): void
    {
        $this->get(route('footer_links.index'))
            ->assertOk()
            ->assertViewIs('media_platform.tools.footer_links.index');
    }

    public function test_index_shows_only_links_belonging_to_authenticated_user(): void
    {
        $show = $this->makeShowForUser();
        $myLink = $this->makeLinkForShow($show, ['link_name' => 'My Link']);

        $otherUser = User::factory()->create();
        $otherShow = $this->makeShowForUser($otherUser);
        $otherLink = $this->makeLinkForShow($otherShow);

        $response = $this->get(route('footer_links.index'));
        $response->assertSee('My Link');
        $response->assertDontSee($otherLink->link_name);
    }

    public function test_index_shows_empty_state_when_no_links(): void
    {
        $this->get(route('footer_links.index'))
            ->assertSee('No footer links yet');
    }

    public function test_index_redirects_unauthenticated_user(): void
    {
        auth()->logout();

        $this->get(route('footer_links.index'))
            ->assertRedirect();
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  2. create                                                             ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_create_renders_for_authenticated_user(): void
    {
        $this->get(route('footer_links.create'))
            ->assertOk()
            ->assertViewIs('media_platform.tools.footer_links.create');
    }

    public function test_create_pre_selects_show_from_query_string(): void
    {
        $show = $this->makeShowForUser();

        $this->get(route('footer_links.create', ['podcast_show_id' => $show->id]))
            ->assertOk()
            ->assertViewHas('selectedShowId', (string) $show->id);
    }

    public function test_create_redirects_unauthenticated_user(): void
    {
        auth()->logout();

        $this->get(route('footer_links.create'))
            ->assertRedirect();
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  3. store                                                              ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_store_creates_footer_link_and_redirects(): void
    {
        $show = $this->makeShowForUser();

        $this->post(route('footer_links.store'), $this->validPayload($show))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('footer_links', [
            'podcast_show_id' => $show->id,
            'user_id'         => $this->user->id,
            'link_name'       => 'Privacy Policy',
            'link_url'        => 'https://example.com/privacy',
            'link_order'      => 1,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->post(route('footer_links.store'), [])
            ->assertSessionHasErrors(['podcast_show_id', 'link_name', 'link_url', 'link_order']);
    }

    public function test_store_validates_link_url_is_valid_url(): void
    {
        $show = $this->makeShowForUser();
        $payload = $this->validPayload($show);
        $payload['link_url'] = 'not-a-url';

        $this->post(route('footer_links.store'), $payload)
            ->assertSessionHasErrors('link_url');
    }

    public function test_store_validates_link_order_is_non_negative(): void
    {
        $show = $this->makeShowForUser();
        $payload = $this->validPayload($show);
        $payload['link_order'] = -1;

        $this->post(route('footer_links.store'), $payload)
            ->assertSessionHasErrors('link_order');
    }

    public function test_store_rejects_show_owned_by_another_user(): void
    {
        $otherUser = User::factory()->create();
        $otherShow = $this->makeShowForUser($otherUser);

        $this->post(route('footer_links.store'), $this->validPayload($otherShow))
            ->assertRedirect(route('footer_links.create'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('footer_links', [
            'podcast_show_id' => $otherShow->id,
        ]);
    }

    public function test_store_validates_podcast_show_exists(): void
    {
        $payload = [
            'podcast_show_id' => 99999,
            'link_name'       => 'Test',
            'link_url'        => 'https://example.com',
            'link_order'      => 0,
        ];

        $this->post(route('footer_links.store'), $payload)
            ->assertSessionHasErrors('podcast_show_id');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  4. show                                                               ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_show_renders_for_owner(): void
    {
        $show = $this->makeShowForUser();
        $link = $this->makeLinkForShow($show);

        $this->get(route('footer_links.show', $link))
            ->assertOk()
            ->assertViewIs('media_platform.tools.footer_links.show')
            ->assertViewHas('footer_link');
    }

    public function test_show_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $otherShow = $this->makeShowForUser($otherUser);
        $link = $this->makeLinkForShow($otherShow);

        $this->get(route('footer_links.show', $link))
            ->assertForbidden();
    }

    public function test_show_returns_404_for_missing_record(): void
    {
        $this->get(route('footer_links.show', 99999))
            ->assertNotFound();
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  5. edit                                                               ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_edit_renders_for_owner(): void
    {
        $show = $this->makeShowForUser();
        $link = $this->makeLinkForShow($show);

        $this->get(route('footer_links.edit', $link))
            ->assertOk()
            ->assertViewIs('media_platform.tools.footer_links.edit')
            ->assertViewHas('footer_link')
            ->assertViewHas('shows');
    }

    public function test_edit_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $otherShow = $this->makeShowForUser($otherUser);
        $link = $this->makeLinkForShow($otherShow);

        $this->get(route('footer_links.edit', $link))
            ->assertForbidden();
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  6. update                                                             ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_update_persists_changes_and_redirects(): void
    {
        $show = $this->makeShowForUser();
        $link = $this->makeLinkForShow($show);

        $payload = [
            'podcast_show_id' => $show->id,
            'link_name'       => 'Updated Name',
            'link_url'        => 'https://example.com/updated',
            'link_order'      => 5,
        ];

        $this->put(route('footer_links.update', $link), $payload)
            ->assertRedirect(route('footer_links.show', $link))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('footer_links', [
            'id'        => $link->id,
            'link_name' => 'Updated Name',
            'link_url'  => 'https://example.com/updated',
            'link_order' => 5,
        ]);
    }

    public function test_update_validates_required_fields(): void
    {
        $show = $this->makeShowForUser();
        $link = $this->makeLinkForShow($show);

        $this->put(route('footer_links.update', $link), [])
            ->assertSessionHasErrors(['podcast_show_id', 'link_name', 'link_url', 'link_order']);
    }

    public function test_update_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $otherShow = $this->makeShowForUser($otherUser);
        $link = $this->makeLinkForShow($otherShow);

        $this->put(route('footer_links.update', $link), $this->validPayload($otherShow))
            ->assertForbidden();
    }

    public function test_update_rejects_reassignment_to_show_owned_by_another_user(): void
    {
        $show = $this->makeShowForUser();
        $link = $this->makeLinkForShow($show);

        $otherUser = User::factory()->create();
        $otherShow = $this->makeShowForUser($otherUser);

        $payload = $this->validPayload($otherShow);

        $this->put(route('footer_links.update', $link), $payload)
            ->assertRedirect(route('footer_links.edit', $link))
            ->assertSessionHas('error');

        // Confirm it was not reassigned.
        $this->assertDatabaseHas('footer_links', [
            'id'              => $link->id,
            'podcast_show_id' => $show->id,
        ]);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  7. deleteConfirm                                                      ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_delete_confirm_renders_for_owner(): void
    {
        $show = $this->makeShowForUser();
        $link = $this->makeLinkForShow($show);

        $this->get(route('footer_links.delete.confirm', $link))
            ->assertOk()
            ->assertViewIs('media_platform.tools.footer_links.delete_confirm')
            ->assertSee($link->link_name);
    }

    public function test_delete_confirm_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $otherShow = $this->makeShowForUser($otherUser);
        $link = $this->makeLinkForShow($otherShow);

        $this->get(route('footer_links.delete.confirm', $link))
            ->assertForbidden();
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  8. destroy                                                            ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_destroy_deletes_and_redirects(): void
    {
        $show = $this->makeShowForUser();
        $link = $this->makeLinkForShow($show);

        $this->delete(route('footer_links.destroy', $link))
            ->assertRedirect(route('footer_links.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('footer_links', ['id' => $link->id]);
    }

    public function test_destroy_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $otherShow = $this->makeShowForUser($otherUser);
        $link = $this->makeLinkForShow($otherShow);

        $this->delete(route('footer_links.destroy', $link))
            ->assertForbidden();

        $this->assertDatabaseHas('footer_links', ['id' => $link->id]);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  9. podcast show view integration                                      ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_podcast_show_view_displays_footer_links(): void
    {
        $show = $this->makeShowForUser();
        $link = $this->makeLinkForShow($show, ['link_name' => 'Terms of Service', 'link_order' => 1]);

        $this->get(route('podcast_shows.show', $show))
            ->assertOk()
            ->assertViewHas('footerLinks')
            ->assertSee('Terms of Service');
    }

    public function test_podcast_show_view_shows_empty_state_without_links(): void
    {
        $show = $this->makeShowForUser();

        $this->get(route('podcast_shows.show', $show))
            ->assertOk()
            ->assertViewHas('footerLinks')
            ->assertSee('No footer links for this show');
    }

    public function test_podcast_show_view_footer_links_sorted_by_link_order(): void
    {
        $show = $this->makeShowForUser();
        $this->makeLinkForShow($show, ['link_name' => 'Second', 'link_order' => 2]);
        $this->makeLinkForShow($show, ['link_name' => 'First',  'link_order' => 1]);
        $this->makeLinkForShow($show, ['link_name' => 'Third',  'link_order' => 3]);

        $response = $this->get(route('podcast_shows.show', $show));
        $footerLinks = $response->viewData('footerLinks');

        $this->assertEquals(['First', 'Second', 'Third'], $footerLinks->pluck('link_name')->toArray());
    }
}