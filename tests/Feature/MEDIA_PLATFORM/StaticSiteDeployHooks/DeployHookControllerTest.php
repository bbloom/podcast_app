<?php

// =============================================================================
// DeployHookControllerTest
//
// Feature tests for DeployHookController.
//
// Covers:
//   1.  index          — list, empty state, unauthenticated
//   2.  create         — renders form, unauthenticated
//   3.  store          — happy path, validation, wrong-owner show
//   4.  show           — renders view, 403 wrong owner, unauthenticated
//   5.  edit           — renders view, 403 wrong owner
//   6.  update         — happy path, blank URL preserved, validation, 403
//   7.  deleteConfirm  — renders view, 403 wrong owner
//   8.  destroy        — deletes, redirects, 403 wrong owner
//
// Ownership model:
//   A deploy hook is polymorphic. For PodcastShow hooks, ownership is
//   confirmed by comparing show->user_id to auth()->id(). Tests cover
//   both the owning user and a different user.
// =============================================================================

namespace Tests\Feature\MEDIA_PLATFORM\StaticSiteDeployHooks;

use App\Models\User;
use Database\Factories\Media_platform\StaticSiteDeployHooks\DeployHookFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use Tests\TestCase;

class DeployHookControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a user who owns a show and a deploy hook on that show.
     * Returns [$user, $show, $hook].
     */
    private function makeOwnerWithHook(): array
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);
        $hook = DeployHook::factory()->create([
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $show->id,
        ]);

        return [$user, $show, $hook];
    }

    /**
     * Create a second user with their own show — used to verify 403 responses.
     */
    private function makeOtherUser(): User
    {
        return User::factory()->create();
    }

    /**
     * Valid payload for store/update requests.
     */
    private function validPayload(PodcastShow $show): array
    {
        return [
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $show->id,
            'label'            => 'My Show — Cloudflare Pages (Live)',
            'provider'         => DeployHookProvider::cloudflare_pages->value,
            'url'              => 'https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/abc123',
            'enabled'          => '1',
        ];
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  1. index                                                              ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_index_renders_for_authenticated_user(): void
    {
        [$user] = $this->makeOwnerWithHook();

        $this->actingAs($user)
             ->get(route('deploy_hooks.index'))
             ->assertOk()
             ->assertViewIs('media_platform.static_site_deploy_hooks.index');
    }

    public function test_index_shows_only_hooks_belonging_to_authenticated_user(): void
    {
        [$user, , $hook] = $this->makeOwnerWithHook();

        // Another user's hook — should not appear.
        $other     = $this->makeOtherUser();
        $otherShow = PodcastShow::factory()->create(['user_id' => $other->id]);
        DeployHook::factory()->create([
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $otherShow->id,
            'label'            => 'Other User Hook',
        ]);

        $response = $this->actingAs($user)->get(route('deploy_hooks.index'));
        $hooks    = $response->viewData('hooks');

        $this->assertCount(1, $hooks);
        $this->assertEquals($hook->id, $hooks->first()->id);
    }

    public function test_index_redirects_unauthenticated_user(): void
    {
        $this->get(route('deploy_hooks.index'))
             ->assertRedirect('/login');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  2. create                                                             ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_create_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->get(route('deploy_hooks.create'))
             ->assertOk()
             ->assertViewIs('media_platform.static_site_deploy_hooks.create')
             ->assertViewHas('shows')
             ->assertViewHas('providers');
    }

    public function test_create_redirects_unauthenticated_user(): void
    {
        $this->get(route('deploy_hooks.create'))
             ->assertRedirect('/login');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  3. store                                                              ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_store_creates_hook_and_redirects_to_show(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
             ->post(route('deploy_hooks.store'), $this->validPayload($show))
             ->assertRedirect();

        $this->assertDatabaseHas('deploy_hooks', [
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $show->id,
            'label'            => 'My Show — Cloudflare Pages (Live)',
            'provider'         => DeployHookProvider::cloudflare_pages->value,
            'enabled'          => true,
        ]);
    }

    public function test_store_redirects_to_show_page_on_success(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                         ->post(route('deploy_hooks.store'), $this->validPayload($show));

        $hook = DeployHook::where('triggerable_id', $show->id)
                          ->where('triggerable_type', 'podcast_show')
                          ->firstOrFail();

        $response->assertRedirect(route('deploy_hooks.show', $hook));
    }

    public function test_store_flashes_success_message(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
             ->post(route('deploy_hooks.store'), $this->validPayload($show))
             ->assertSessionHas('success');
    }

    public function test_store_returns_403_when_show_belongs_to_another_user(): void
    {
        $user      = User::factory()->create();
        $otherUser = $this->makeOtherUser();
        $otherShow = PodcastShow::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($user)
             ->post(route('deploy_hooks.store'), $this->validPayload($otherShow))
             ->assertRedirect()
             ->assertSessionHas('error');
    }

    public function test_store_fails_validation_when_label_is_missing(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $payload = $this->validPayload($show);
        unset($payload['label']);

        $this->actingAs($user)
             ->post(route('deploy_hooks.store'), $payload)
             ->assertSessionHasErrors('label');
    }

    public function test_store_fails_validation_when_url_is_missing(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $payload = $this->validPayload($show);
        unset($payload['url']);

        $this->actingAs($user)
             ->post(route('deploy_hooks.store'), $payload)
             ->assertSessionHasErrors('url');
    }

    public function test_store_fails_validation_when_url_is_not_a_valid_url(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $payload        = $this->validPayload($show);
        $payload['url'] = 'not-a-url';

        $this->actingAs($user)
             ->post(route('deploy_hooks.store'), $payload)
             ->assertSessionHasErrors('url');
    }

    public function test_store_fails_validation_when_provider_is_invalid(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $payload             = $this->validPayload($show);
        $payload['provider'] = 'unknown_provider';

        $this->actingAs($user)
             ->post(route('deploy_hooks.store'), $payload)
             ->assertSessionHasErrors('provider');
    }

    public function test_store_redirects_unauthenticated_user(): void
    {
        $this->post(route('deploy_hooks.store'), [])
             ->assertRedirect('/login');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  4. show                                                               ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_show_renders_for_owning_user(): void
    {
        [$user, , $hook] = $this->makeOwnerWithHook();

        $this->actingAs($user)
             ->get(route('deploy_hooks.show', $hook))
             ->assertOk()
             ->assertViewIs('media_platform.static_site_deploy_hooks.show')
             ->assertViewHas('hook');
    }

    public function test_show_returns_403_for_non_owning_user(): void
    {
        [, , $hook] = $this->makeOwnerWithHook();
        $other      = $this->makeOtherUser();

        $this->actingAs($other)
             ->get(route('deploy_hooks.show', $hook))
             ->assertRedirect()
             ->assertSessionHas('error');
    }

    public function test_show_redirects_unauthenticated_user(): void
    {
        [, , $hook] = $this->makeOwnerWithHook();

        $this->get(route('deploy_hooks.show', $hook))
             ->assertRedirect('/login');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  5. edit                                                               ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_edit_renders_for_owning_user(): void
    {
        [$user, , $hook] = $this->makeOwnerWithHook();

        $this->actingAs($user)
             ->get(route('deploy_hooks.edit', $hook))
             ->assertOk()
             ->assertViewIs('media_platform.static_site_deploy_hooks.edit')
             ->assertViewHas('hook')
             ->assertViewHas('shows')
             ->assertViewHas('providers');
    }

    public function test_edit_returns_403_for_non_owning_user(): void
    {
        [, , $hook] = $this->makeOwnerWithHook();
        $other      = $this->makeOtherUser();

        $this->actingAs($other)
             ->get(route('deploy_hooks.edit', $hook))
             ->assertRedirect()
             ->assertSessionHas('error');
    }

    public function test_edit_redirects_unauthenticated_user(): void
    {
        [, , $hook] = $this->makeOwnerWithHook();

        $this->get(route('deploy_hooks.edit', $hook))
             ->assertRedirect('/login');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  6. update                                                             ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_update_persists_changes_and_redirects_to_show(): void
    {
        [$user, $show, $hook] = $this->makeOwnerWithHook();

        $payload          = $this->validPayload($show);
        $payload['label'] = 'Updated Label';

        $this->actingAs($user)
             ->put(route('deploy_hooks.update', $hook), $payload)
             ->assertRedirect(route('deploy_hooks.show', $hook));

        $this->assertDatabaseHas('deploy_hooks', [
            'id'    => $hook->id,
            'label' => 'Updated Label',
        ]);
    }

    public function test_update_flashes_success_message(): void
    {
        [$user, $show, $hook] = $this->makeOwnerWithHook();

        $this->actingAs($user)
             ->put(route('deploy_hooks.update', $hook), $this->validPayload($show))
             ->assertSessionHas('success');
    }

    public function test_update_preserves_existing_url_when_url_is_blank(): void
    {
        [$user, $show, $hook] = $this->makeOwnerWithHook();

        $originalEncryptedUrl = $hook->getAttributes()['url'];

        $payload        = $this->validPayload($show);
        $payload['url'] = '';

        $this->actingAs($user)
             ->put(route('deploy_hooks.update', $hook), $payload);

        $this->assertSame($originalEncryptedUrl, $hook->fresh()->getAttributes()['url']);
    }

    public function test_update_replaces_url_when_new_url_is_provided(): void
    {
        [$user, $show, $hook] = $this->makeOwnerWithHook();

        $newUrl         = 'https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/new-uuid-here';
        $payload        = $this->validPayload($show);
        $payload['url'] = $newUrl;

        $this->actingAs($user)
             ->put(route('deploy_hooks.update', $hook), $payload);

        $this->assertSame($newUrl, $hook->fresh()->url);
    }

    public function test_update_returns_403_for_non_owning_user(): void
    {
        [$owner, $show, $hook] = $this->makeOwnerWithHook();
        $other                 = $this->makeOtherUser();

        $this->actingAs($other)
             ->put(route('deploy_hooks.update', $hook), $this->validPayload($show))
             ->assertRedirect()
             ->assertSessionHas('error');
    }

    public function test_update_returns_403_when_new_show_belongs_to_another_user(): void
    {
        [$user, , $hook] = $this->makeOwnerWithHook();
        $otherUser       = $this->makeOtherUser();
        $otherShow       = PodcastShow::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($user)
             ->put(route('deploy_hooks.update', $hook), $this->validPayload($otherShow))
             ->assertRedirect()
             ->assertSessionHas('error');
    }

    public function test_update_fails_validation_when_label_is_missing(): void
    {
        [$user, $show, $hook] = $this->makeOwnerWithHook();

        $payload = $this->validPayload($show);
        unset($payload['label']);

        $this->actingAs($user)
             ->put(route('deploy_hooks.update', $hook), $payload)
             ->assertSessionHasErrors('label');
    }

    public function test_update_redirects_unauthenticated_user(): void
    {
        [, , $hook] = $this->makeOwnerWithHook();

        $this->put(route('deploy_hooks.update', $hook), [])
             ->assertRedirect('/login');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  7. deleteConfirm                                                      ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_delete_confirm_renders_for_owning_user(): void
    {
        [$user, , $hook] = $this->makeOwnerWithHook();

        $this->actingAs($user)
             ->get(route('deploy_hooks.delete.confirm', $hook))
             ->assertOk()
             ->assertViewIs('media_platform.static_site_deploy_hooks.delete_confirm')
             ->assertViewHas('hook');
    }

    public function test_delete_confirm_returns_403_for_non_owning_user(): void
    {
        [, , $hook] = $this->makeOwnerWithHook();
        $other      = $this->makeOtherUser();

        $this->actingAs($other)
             ->get(route('deploy_hooks.delete.confirm', $hook))
             ->assertRedirect()
             ->assertSessionHas('error');
    }

    public function test_delete_confirm_redirects_unauthenticated_user(): void
    {
        [, , $hook] = $this->makeOwnerWithHook();

        $this->get(route('deploy_hooks.delete.confirm', $hook))
             ->assertRedirect('/login');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  8. destroy                                                            ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_destroy_deletes_hook_from_database(): void
    {
        [$user, , $hook] = $this->makeOwnerWithHook();

        $this->actingAs($user)
             ->delete(route('deploy_hooks.destroy', $hook));

        $this->assertDatabaseMissing('deploy_hooks', ['id' => $hook->id]);
    }

    public function test_destroy_redirects_to_index_with_success_flash(): void
    {
        [$user, , $hook] = $this->makeOwnerWithHook();

        $this->actingAs($user)
             ->delete(route('deploy_hooks.destroy', $hook))
             ->assertRedirect(route('deploy_hooks.index'))
             ->assertSessionHas('success');
    }

    public function test_destroy_returns_403_for_non_owning_user(): void
    {
        [, , $hook] = $this->makeOwnerWithHook();
        $other      = $this->makeOtherUser();

        $this->actingAs($other)
             ->delete(route('deploy_hooks.destroy', $hook))
             ->assertRedirect()
             ->assertSessionHas('error');

        $this->assertDatabaseHas('deploy_hooks', ['id' => $hook->id]);
    }

    public function test_destroy_redirects_unauthenticated_user(): void
    {
        [, , $hook] = $this->makeOwnerWithHook();

        $this->delete(route('deploy_hooks.destroy', $hook))
             ->assertRedirect('/login');
    }
}