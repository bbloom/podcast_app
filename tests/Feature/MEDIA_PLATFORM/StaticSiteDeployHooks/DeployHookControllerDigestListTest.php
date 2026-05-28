<?php

// tests/Feature/MEDIA_PLATFORM/StaticSiteDeployHooks/DeployHookControllerDigestListTest.php

namespace Tests\Feature\MEDIA_PLATFORM\StaticSiteDeployHooks;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeployHookControllerDigestListTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeStaticSiteList(string $name = 'Test Digest'): ListModel
    {
        return ListModel::factory()->forUser($this->user)->staticSite()->create(['name' => $name]);
    }

    private function makeDigestHook(ListModel $list, array $overrides = []): DeployHook
    {
        return DeployHook::factory()->create(array_merge([
            'triggerable_type' => 'digest_list',
            'triggerable_id'   => $list->id,
            'label'            => 'Test Digest Hook',
            'provider'         => DeployHookProvider::cloudflare_pages,
            'enabled'          => true,
        ], $overrides));
    }

    // =========================================================================
    // GROUP 1: index
    // =========================================================================

    #[Test]
    public function displays_digest_list_hooks_on_the_index_page(): void
    {
        $list = $this->makeStaticSiteList();
        $this->makeDigestHook($list, ['label' => 'Morning Digest — CF Pages']);

        $this->get(route('deploy_hooks.index'))
             ->assertOk()
             ->assertSee('Morning Digest — CF Pages')
             ->assertSee('Digest List');
    }

    #[Test]
    public function does_not_display_hooks_for_lists_owned_by_other_users(): void
    {
        $otherUser = User::factory()->create();
        $list      = ListModel::factory()->forUser($otherUser)->staticSite()->create(['name' => 'Other User Digest']);
        $this->makeDigestHook($list, ['label' => 'Should Not Appear']);

        $this->get(route('deploy_hooks.index'))
             ->assertOk()
             ->assertDontSee('Should Not Appear');
    }

    // =========================================================================
    // GROUP 2: create
    // =========================================================================

    #[Test]
    public function shows_the_create_form_with_digest_list_dropdown(): void
    {
        $this->makeStaticSiteList('My Digest List');

        $this->get(route('deploy_hooks.create'))
             ->assertOk()
             ->assertSee('Digest List')
             ->assertSee('My Digest List');
    }

    #[Test]
    public function pre_fills_triggerable_from_query_params(): void
    {
        $list = $this->makeStaticSiteList('Prefilled List');

        $this->get(route('deploy_hooks.create', [
            'triggerable_type' => 'digest_list',
            'triggerable_id'   => $list->id,
        ]))->assertOk()
           ->assertSee('Prefilled List');
    }

    #[Test]
    public function only_shows_static_site_lists_in_the_dropdown_not_email_lists(): void
    {
        $this->makeStaticSiteList('Static Site List');
        ListModel::factory()->forUser($this->user)->create([
            'name'        => 'Email Only List',
            'output_type' => OutputType::Email,
        ]);

        $this->get(route('deploy_hooks.create'))
             ->assertOk()
             ->assertSee('Static Site List')
             ->assertDontSee('Email Only List');
    }

    // =========================================================================
    // GROUP 3: store
    // =========================================================================

    #[Test]
    public function creates_a_deploy_hook_for_a_digest_list(): void
    {
        $list = $this->makeStaticSiteList();

        $this->post(route('deploy_hooks.store'), [
            'triggerable_type' => 'digest_list',
            'triggerable_id'   => $list->id,
            'label'            => 'New Digest Hook',
            'provider'         => 'cloudflare_pages',
            'url'              => 'https://api.cloudflare.com/hook/test',
            'enabled'          => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('deploy_hooks', [
            'triggerable_type' => 'digest_list',
            'triggerable_id'   => $list->id,
            'label'            => 'New Digest Hook',
        ]);
    }

    #[Test]
    public function rejects_creation_for_a_list_not_owned_by_user(): void
    {
        $otherUser = User::factory()->create();
        $list      = ListModel::factory()->forUser($otherUser)->staticSite()->create();

        $this->post(route('deploy_hooks.store'), [
            'triggerable_type' => 'digest_list',
            'triggerable_id'   => $list->id,
            'label'            => 'Unauthorized Hook',
            'provider'         => 'cloudflare_pages',
            'url'              => 'https://api.cloudflare.com/hook/test',
            'enabled'          => '1',
        ])->assertRedirect(route('deploy_hooks.index'))
          ->assertSessionHas('error');

        $this->assertDatabaseMissing('deploy_hooks', ['label' => 'Unauthorized Hook']);
    }

    // =========================================================================
    // GROUP 4: show
    // =========================================================================

    #[Test]
    public function displays_a_digest_list_hook_correctly(): void
    {
        $list = $this->makeStaticSiteList('My Digest');
        $hook = $this->makeDigestHook($list);

        $this->get(route('deploy_hooks.show', $hook))
             ->assertOk()
             ->assertSee('My Digest')
             ->assertSee('Digest List')
             ->assertSee('Test Digest Hook');
    }

    // =========================================================================
    // GROUP 5: ownership
    // =========================================================================

    #[Test]
    public function returns_error_when_accessing_hook_for_another_users_list(): void
    {
        $otherUser = User::factory()->create();
        $list      = ListModel::factory()->forUser($otherUser)->staticSite()->create();
        $hook      = $this->makeDigestHook($list);

        $this->get(route('deploy_hooks.show', $hook))
             ->assertRedirect(route('deploy_hooks.index'))
             ->assertSessionHas('error');
    }

    #[Test]
    public function returns_error_when_editing_hook_for_another_users_list(): void
    {
        $otherUser = User::factory()->create();
        $list      = ListModel::factory()->forUser($otherUser)->staticSite()->create();
        $hook      = $this->makeDigestHook($list);

        $this->get(route('deploy_hooks.edit', $hook))
             ->assertRedirect(route('deploy_hooks.index'))
             ->assertSessionHas('error');
    }

    // =========================================================================
    // GROUP 6: redirect_to — wizard flow
    // =========================================================================

    #[Test]
    public function redirects_to_list_show_page_when_redirect_to_is_provided_on_store(): void
    {
        $list = $this->makeStaticSiteList();

        $this->post(route('deploy_hooks.store'), [
            'triggerable_type' => 'digest_list',
            'triggerable_id'   => $list->id,
            'label'            => 'Redirect Hook',
            'provider'         => 'cloudflare_pages',
            'url'              => 'https://api.cloudflare.com/hook/test',
            'enabled'          => '1',
            'redirect_to'      => 'lists.show',
        ])->assertRedirect(route('lists.show', $list))
          ->assertSessionHas('success');
    }
}