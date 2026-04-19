<?php

// tests/Feature/MEDIA_PLATFORM/StaticSiteDeployHooks/DeployHookControllerDigestListTest.php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * DeployHookControllerDigestListTest
 *
 * Tests the DeployHookController CRUD operations specifically for
 * digest_list triggerable type.
 *
 * TEST GROUPS
 * ───────────
 *   1. index — includes digest list hooks
 *   2. create — shows digest list dropdown, handles prefill
 *   3. store — creates hook for digest list
 *   4. show — displays digest list hook correctly
 *   5. ownership — rejects hooks for lists owned by other users
 *   6. redirect_to — supports wizard redirect flow
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// =============================================================================
// Helpers
// =============================================================================

function makeStaticSiteList(User $user, string $name = 'Test Digest'): ListModel
{
    return ListModel::factory()->forUser($user)->staticSite()->create(['name' => $name]);
}

function makeDigestHook(ListModel $list, array $overrides = []): DeployHook
{
    return DeployHook::factory()->create(array_merge([
        'triggerable_type' => 'digest_list',
        'triggerable_id'   => $list->id,
        'label'            => 'Test Digest Hook',
        'provider'         => DeployHookProvider::cloudflare_pages,
        'enabled'          => true,
    ], $overrides));
}

// =============================================================================
// GROUP 1: index
// =============================================================================

it('displays digest list hooks on the index page', function () {
    $list = makeStaticSiteList($this->user);
    $hook = makeDigestHook($list, ['label' => 'Morning Digest — CF Pages']);

    $this->get(route('deploy_hooks.index'))
        ->assertOk()
        ->assertSee('Morning Digest — CF Pages')
        ->assertSee('Digest List');
});

it('does not display hooks for lists owned by other users', function () {
    $otherUser = User::factory()->create();
    $list      = makeStaticSiteList($otherUser, 'Other User Digest');
    makeDigestHook($list, ['label' => 'Should Not Appear']);

    $this->get(route('deploy_hooks.index'))
        ->assertOk()
        ->assertDontSee('Should Not Appear');
});

// =============================================================================
// GROUP 2: create
// =============================================================================

it('shows the create form with digest list dropdown', function () {
    makeStaticSiteList($this->user, 'My Digest List');

    $this->get(route('deploy_hooks.create'))
        ->assertOk()
        ->assertSee('Digest List')
        ->assertSee('My Digest List');
});

it('pre-fills triggerable from query params', function () {
    $list = makeStaticSiteList($this->user, 'Prefilled List');

    $this->get(route('deploy_hooks.create', [
        'triggerable_type' => 'digest_list',
        'triggerable_id'   => $list->id,
    ]))
        ->assertOk()
        ->assertSee('Prefilled List');
});

it('only shows static site lists in the dropdown not email lists', function () {
    makeStaticSiteList($this->user, 'Static Site List');
    ListModel::factory()->forUser($this->user)->create([
        'name'        => 'Email Only List',
        'output_type' => OutputType::Email,
    ]);

    $this->get(route('deploy_hooks.create'))
        ->assertOk()
        ->assertSee('Static Site List')
        ->assertDontSee('Email Only List');
});

// =============================================================================
// GROUP 3: store
// =============================================================================

it('creates a deploy hook for a digest list', function () {
    $list = makeStaticSiteList($this->user);

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
});

it('rejects creation for a list not owned by user', function () {
    $otherUser = User::factory()->create();
    $list      = makeStaticSiteList($otherUser);

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
});

// =============================================================================
// GROUP 4: show
// =============================================================================

it('displays a digest list hook correctly', function () {
    $list = makeStaticSiteList($this->user, 'My Digest');
    $hook = makeDigestHook($list);

    $this->get(route('deploy_hooks.show', $hook))
        ->assertOk()
        ->assertSee('My Digest')
        ->assertSee('Digest List')
        ->assertSee('Test Digest Hook');
});

// =============================================================================
// GROUP 5: ownership
// =============================================================================

it('returns error when accessing hook for another users list', function () {
    $otherUser = User::factory()->create();
    $list      = makeStaticSiteList($otherUser);
    $hook      = makeDigestHook($list);

    $this->get(route('deploy_hooks.show', $hook))
        ->assertRedirect(route('deploy_hooks.index'))
        ->assertSessionHas('error');
});

it('returns error when editing hook for another users list', function () {
    $otherUser = User::factory()->create();
    $list      = makeStaticSiteList($otherUser);
    $hook      = makeDigestHook($list);

    $this->get(route('deploy_hooks.edit', $hook))
        ->assertRedirect(route('deploy_hooks.index'))
        ->assertSessionHas('error');
});

// =============================================================================
// GROUP 6: redirect_to — wizard flow
// =============================================================================

it('redirects to list show page when redirect_to is provided on store', function () {
    $list = makeStaticSiteList($this->user);

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
});