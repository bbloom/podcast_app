<?php

// tests/Feature/MEDIA_PLATFORM/Digest/ContentSources/ListCrudTest.php

use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * ListCrudTest
 *
 * Feature tests for ListWizardController CRUD methods:
 * index, edit, update, confirmDelete, destroy.
 *
 * TEST GROUPS
 * ───────────
 *   1. index
 *   2. edit
 *   2b. show
 *   3. update — happy paths (email, webpage, static_site)
 *   4. update — validation
 *   5. update — ownership / authorization
 *   6. confirmDelete
 *   7. destroy
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// =============================================================================
// Helpers
// =============================================================================

function basePayload(array $overrides = []): array
{
    return array_merge([
        'name'                  => 'My List',
        'description'           => null,
        'timezone'              => 'America/Toronto',
        'enabled'               => '1',
        'schedule_frequency'    => 'daily',
        'schedule_day'          => null,
        'schedule_time'         => '08:00',
        'output_type'           => 'email',
        'output_destination_id' => null,
        'notify_by_email'       => '0',
    ], $overrides);
}

// =============================================================================
// GROUP 1: index
// =============================================================================

test('index renders for authenticated user', function () {
    ListModel::factory()->forUser($this->user)->count(3)->create();

    $this->get(route('lists.index'))
        ->assertOk()
        ->assertViewIs('media_platform.digest.content_sources.lists.index')
        ->assertViewHas('lists');
});

test('index only shows the current users lists', function () {
    $otherUser = User::factory()->create();
    ListModel::factory()->forUser($this->user)->create(['name' => 'My List']);
    ListModel::factory()->forUser($otherUser)->create(['name' => 'Their List']);

    $response = $this->get(route('lists.index'));
    $lists    = $response->viewData('lists');

    expect($lists)->toHaveCount(1);
    expect($lists->first()->name)->toBe('My List');
});

// =============================================================================
// GROUP 2: edit
// =============================================================================

test('edit renders for owner', function () {
    $list = ListModel::factory()->forUser($this->user)->create();

    $this->get(route('lists.edit', $list))
        ->assertOk()
        ->assertViewIs('media_platform.digest.content_sources.lists.edit')
        ->assertViewHas('list')
        ->assertViewHas('destinations');
});

test('edit returns 403 for non-owner', function () {
    $otherUser = User::factory()->create();
    $list      = ListModel::factory()->forUser($otherUser)->create();

    $this->get(route('lists.edit', $list))->assertForbidden();
});

// =============================================================================
// GROUP 2b: show
// =============================================================================

test('show renders for owner', function () {
    $list = ListModel::factory()->forUser($this->user)->create();

    $this->get(route('lists.show', $list))
        ->assertOk()
        ->assertViewIs('media_platform.digest.content_sources.lists.show')
        ->assertViewHas('list')
        ->assertViewHas('sources')
        ->assertViewHas('tracking');
});

test('show renders static site sections for static site list', function () {
    $list = ListModel::factory()->forUser($this->user)->staticSite()->create();

    $this->get(route('lists.show', $list))
        ->assertOk()
        ->assertViewHas('deployHooks')
        ->assertViewHas('publishedDigests');
});

test('show returns 403 for non-owner', function () {
    $otherUser = User::factory()->create();
    $list      = ListModel::factory()->forUser($otherUser)->create();

    $this->get(route('lists.show', $list))
        ->assertForbidden();
});

test('show returns 404 for missing record', function () {
    $this->get(route('lists.show', 99999))
        ->assertNotFound();
});

// =============================================================================
// GROUP 3: update — happy paths
// =============================================================================

test('update saves email list correctly', function () {
    $list = ListModel::factory()->forUser($this->user)->create();

    $this->put(route('lists.update', $list), basePayload([
        'name'        => 'Updated Name',
        'output_type' => 'email',
    ]))->assertRedirect(route('lists.index'))
       ->assertSessionHas('success');

    $list->refresh();
    expect($list->name)->toBe('Updated Name');
    expect($list->output_type)->toBe(OutputType::Email);
    expect($list->output_destination_id)->toBeNull();
    expect($list->notify_by_email)->toBeFalse();
});

test('update saves webpage list with destination', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->create();
    $list = ListModel::factory()->forUser($this->user)->create();

    $this->put(route('lists.update', $list), basePayload([
        'output_type'           => 'webpage',
        'output_destination_id' => $dest->id,
        'notify_by_email'       => '1',
    ]))->assertRedirect(route('lists.index'));

    $list->refresh();
    expect($list->output_type)->toBe(OutputType::Webpage);
    expect($list->output_destination_id)->toBe($dest->id);
    expect($list->notify_by_email)->toBeTrue();
});

test('update saves static site list correctly', function () {
    $list = ListModel::factory()->forUser($this->user)->create();

    $this->put(route('lists.update', $list), basePayload([
        'output_type'     => 'static_site',
        'notify_by_email' => '1',
        'retention_count' => 15,
    ]))->assertRedirect(route('lists.index'))
       ->assertSessionHas('success');

    $list->refresh();
    expect($list->output_type)->toBe(OutputType::StaticSite);
    expect($list->output_destination_id)->toBeNull();
    expect($list->notify_by_email)->toBeTrue();
    expect($list->retention_count)->toBe(15);
});

test('update clears destination and notify_by_email when switching to email', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->create();
    $list = ListModel::factory()->forUser($this->user)->webpage($dest->id)->create([
        'notify_by_email' => true,
    ]);

    $this->put(route('lists.update', $list), basePayload([
        'output_type' => 'email',
    ]));

    $list->refresh();
    expect($list->output_destination_id)->toBeNull();
    expect($list->notify_by_email)->toBeFalse();
});

test('update clears destination when switching to static_site', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->create();
    $list = ListModel::factory()->forUser($this->user)->webpage($dest->id)->create();

    $this->put(route('lists.update', $list), basePayload([
        'output_type'     => 'static_site',
        'notify_by_email' => '0',
        'retention_count' => 5,
    ]));

    $list->refresh();
    expect($list->output_type)->toBe(OutputType::StaticSite);
    expect($list->output_destination_id)->toBeNull();
    expect($list->retention_count)->toBe(5);
});

// =============================================================================
// GROUP 4: update — validation
// =============================================================================

test('update rejects missing name', function () {
    $list = ListModel::factory()->forUser($this->user)->create();

    $this->put(route('lists.update', $list), basePayload(['name' => '']))
        ->assertSessionHasErrors('name');
});

test('update rejects invalid output_type', function () {
    $list = ListModel::factory()->forUser($this->user)->create();

    $this->put(route('lists.update', $list), basePayload(['output_type' => 'wordpress']))
        ->assertSessionHasErrors('output_type');
});

test('update accepts static_site as valid output_type', function () {
    $list = ListModel::factory()->forUser($this->user)->create();

    $this->put(route('lists.update', $list), basePayload(['output_type' => 'static_site']))
        ->assertSessionDoesntHaveErrors('output_type');
});

test('update rejects invalid schedule_frequency', function () {
    $list = ListModel::factory()->forUser($this->user)->create();

    $this->put(route('lists.update', $list), basePayload(['schedule_frequency' => 'hourly']))
        ->assertSessionHasErrors('schedule_frequency');
});

test('update rejects invalid schedule_time format', function () {
    $list = ListModel::factory()->forUser($this->user)->create();

    $this->put(route('lists.update', $list), basePayload(['schedule_time' => '8am']))
        ->assertSessionHasErrors('schedule_time');
});

test('update returns 403 when destination belongs to another user', function () {
    $otherUser = User::factory()->create();
    $dest      = OutputDestination::factory()->forUser($otherUser)->create();
    $list      = ListModel::factory()->forUser($this->user)->create();

    $this->put(route('lists.update', $list), basePayload([
        'output_type'           => 'webpage',
        'output_destination_id' => $dest->id,
    ]))->assertForbidden();
});

test('update rejects retention_count below 1', function () {
    $list = ListModel::factory()->forUser($this->user)->create();

    $this->put(route('lists.update', $list), basePayload([
        'output_type'     => 'static_site',
        'retention_count' => 0,
    ]))->assertSessionHasErrors('retention_count');
});

test('update rejects retention_count above 100', function () {
    $list = ListModel::factory()->forUser($this->user)->create();

    $this->put(route('lists.update', $list), basePayload([
        'output_type'     => 'static_site',
        'retention_count' => 101,
    ]))->assertSessionHasErrors('retention_count');
});

// =============================================================================
// GROUP 5: update — ownership
// =============================================================================

test('update returns 403 for non-owner', function () {
    $otherUser = User::factory()->create();
    $list      = ListModel::factory()->forUser($otherUser)->create();

    $this->put(route('lists.update', $list), basePayload())
        ->assertForbidden();
});

// =============================================================================
// GROUP 6: confirmDelete
// =============================================================================

test('confirmDelete renders for owner', function () {
    $list = ListModel::factory()->forUser($this->user)->create();

    $this->get(route('lists.delete.confirm', $list))
        ->assertOk()
        ->assertViewIs('media_platform.digest.content_sources.lists.delete-confirm')
        ->assertViewHas('list');
});

test('confirmDelete returns 403 for non-owner', function () {
    $otherUser = User::factory()->create();
    $list      = ListModel::factory()->forUser($otherUser)->create();

    $this->get(route('lists.delete.confirm', $list))->assertForbidden();
});

// =============================================================================
// GROUP 7: destroy
// =============================================================================

test('destroy deletes list and redirects', function () {
    $list = ListModel::factory()->forUser($this->user)->create();

    $this->delete(route('lists.destroy', $list))
        ->assertRedirect(route('lists.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('lists', ['id' => $list->id]);
});

test('destroy returns 403 for non-owner', function () {
    $otherUser = User::factory()->create();
    $list      = ListModel::factory()->forUser($otherUser)->create();

    $this->delete(route('lists.destroy', $list))->assertForbidden();
});