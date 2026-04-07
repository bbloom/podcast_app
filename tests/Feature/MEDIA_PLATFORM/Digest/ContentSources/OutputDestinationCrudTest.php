<?php

// tests/Feature/MEDIA_PLATFORM/Digest/ContentSources/OutputDestinationCrudTest.php

use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * OutputDestinationCrudTest
 *
 * Feature tests for OutputDestinationWizardController CRUD methods:
 * index, edit, update, confirmDelete, destroy.
 *
 * TEST GROUPS
 * ───────────
 *   1. index
 *   2. edit
 *   3. update — happy path (sftp)
 *   4. update — validation
 *   5. update — ownership
 *   6. confirmDelete
 *   7. destroy — happy path and in-use guard
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// =============================================================================
// GROUP 1: index
// =============================================================================

test('index renders for authenticated user', function () {
    OutputDestination::factory()->forUser($this->user)->count(2)->create();

    $this->get(route('output_destinations.index'))
        ->assertOk()
        ->assertViewIs('media_platform.digest.content_sources.output_destinations.index')
        ->assertViewHas('destinations');
});

test('index only shows the current users destinations', function () {
    $otherUser = User::factory()->create();
    OutputDestination::factory()->forUser($this->user)->create(['name' => 'Mine']);
    OutputDestination::factory()->forUser($otherUser)->create(['name' => 'Theirs']);

    $response     = $this->get(route('output_destinations.index'));
    $destinations = $response->viewData('destinations');

    expect($destinations)->toHaveCount(1);
    expect($destinations->first()->name)->toBe('Mine');
});

// =============================================================================
// GROUP 2: edit
// =============================================================================

test('edit renders for owner', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->create();

    $this->get(route('output_destinations.edit', $dest))
        ->assertOk()
        ->assertViewIs('media_platform.digest.content_sources.output_destinations.edit')
        ->assertViewHas('outputDestination');
});

test('edit returns 403 for non-owner', function () {
    $otherUser = User::factory()->create();
    $dest      = OutputDestination::factory()->forUser($otherUser)->create();

    $this->get(route('output_destinations.edit', $dest))->assertForbidden();
});

// =============================================================================
// GROUP 2b: show
// =============================================================================

test('show renders for owner', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->create();

    $this->get(route('output_destinations.show', $dest))
        ->assertOk()
        ->assertViewIs('media_platform.digest.content_sources.output_destinations.show')
        ->assertViewHas('outputDestination')
        ->assertViewHas('lists');
});

test('show returns 403 for non-owner', function () {
    $otherUser = User::factory()->create();
    $dest      = OutputDestination::factory()->forUser($otherUser)->create();

    $this->get(route('output_destinations.show', $dest))
        ->assertForbidden();
});

test('show returns 404 for missing record', function () {
    $this->get(route('output_destinations.show', 99999))
        ->assertNotFound();
});

// =============================================================================
// GROUP 3: update — happy path (sftp)
// =============================================================================

test('update saves sftp destination correctly', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->create([
        'name' => 'Old Name',
        'host' => 'old.example.com',
    ]);

    $this->put(route('output_destinations.update', $dest), [
        'name'      => 'New Name',
        'enabled'   => '1',
        'host'      => 'new.example.com',
        'port'      => 22,
        'username'  => 'deploy',
        'auth_type' => 'password',
        'path'      => '/var/www',
        'base_url'  => 'https://example.com',
    ])->assertRedirect(route('output_destinations.index'))
      ->assertSessionHas('success');

    $dest->refresh();
    expect($dest->name)->toBe('New Name');
    expect($dest->host)->toBe('new.example.com');
});

test('update preserves existing password when blank is submitted', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->create([
        'auth_type' => 'password',
        'password'  => 'original-secret',
    ]);

    $this->put(route('output_destinations.update', $dest), [
        'name'      => $dest->name,
        'enabled'   => '1',
        'host'      => $dest->host,
        'port'      => $dest->port,
        'username'  => $dest->username,
        'auth_type' => 'password',
        'password'  => '',   // blank — should preserve existing
        'path'      => $dest->path,
    ]);

    $dest->refresh();
    expect($dest->password)->toBe('original-secret');
});

// =============================================================================
// GROUP 4: update — validation
// =============================================================================

test('update rejects missing name', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->create();

    $this->put(route('output_destinations.update', $dest), [
        'name'      => '',
        'enabled'   => '1',
        'host'      => 'example.com',
        'port'      => 22,
        'username'  => 'deploy',
        'auth_type' => 'password',
        'path'      => '/var/www',
    ])->assertSessionHasErrors('name');
});

test('update rejects invalid port', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->create();

    $this->put(route('output_destinations.update', $dest), [
        'name'      => 'Test',
        'enabled'   => '1',
        'host'      => 'example.com',
        'port'      => 99999,
        'username'  => 'deploy',
        'auth_type' => 'password',
        'path'      => '/var/www',
    ])->assertSessionHasErrors('port');
});

// =============================================================================
// GROUP 5: update — ownership
// =============================================================================

test('update returns 403 for non-owner', function () {
    $otherUser = User::factory()->create();
    $dest      = OutputDestination::factory()->forUser($otherUser)->create();

    $this->put(route('output_destinations.update', $dest), [
        'name'    => 'Hacked',
        'enabled' => '1',
    ])->assertForbidden();
});

// =============================================================================
// GROUP 6: confirmDelete
// =============================================================================

test('confirmDelete renders for owner', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->create();

    $this->get(route('output_destinations.delete.confirm', $dest))
        ->assertOk()
        ->assertViewIs('media_platform.digest.content_sources.output_destinations.delete-confirm')
        ->assertViewHas('outputDestination');
});

test('confirmDelete returns 403 for non-owner', function () {
    $otherUser = User::factory()->create();
    $dest      = OutputDestination::factory()->forUser($otherUser)->create();

    $this->get(route('output_destinations.delete.confirm', $dest))->assertForbidden();
});

// =============================================================================
// GROUP 7: destroy
// =============================================================================

test('destroy deletes unused destination and redirects', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->create();

    $this->delete(route('output_destinations.destroy', $dest))
        ->assertRedirect(route('output_destinations.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('output_destinations', ['id' => $dest->id]);
});

test('destroy blocks deletion when destination is used by a list', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->create();
    ListModel::factory()->forUser($this->user)->webpage($dest->id)->create();

    $this->delete(route('output_destinations.destroy', $dest))
        ->assertRedirect(route('output_destinations.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('output_destinations', ['id' => $dest->id]);
});

test('destroy returns 403 for non-owner', function () {
    $otherUser = User::factory()->create();
    $dest      = OutputDestination::factory()->forUser($otherUser)->create();

    $this->delete(route('output_destinations.destroy', $dest))->assertForbidden();
});