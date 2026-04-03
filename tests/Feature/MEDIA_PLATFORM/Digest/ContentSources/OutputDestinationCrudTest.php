<?php

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
 *   3. update — happy paths (sftp, wordpress)
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

    $response = $this->get(route('output_destinations.index'));
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
// GROUP 3: update — happy paths
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

test('update saves wordpress destination correctly', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->wordpress()->create();

    $this->put(route('output_destinations.update', $dest), [
        'name'                   => 'My WP Site',
        'enabled'                => '1',
        'wordpress_url'          => 'https://mynewsite.com',
        'wordpress_username'     => 'admin',
        'wordpress_app_password' => '',   // blank — should preserve existing
        'wordpress_post_status'  => 'draft',
        'wordpress_category_ids' => '1,2',
        'wordpress_tag_ids'      => '',
    ])->assertRedirect(route('output_destinations.index'));

    $dest->refresh();
    expect($dest->name)->toBe('My WP Site');
    expect($dest->wordpress_url)->toBe('https://mynewsite.com');
    expect($dest->wordpress_post_status)->toBe('draft');
});

test('update preserves wordpress app password when blank is submitted', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->wordpress()->create([
        'wordpress_app_password' => 'original-secret',
    ]);

    $this->put(route('output_destinations.update', $dest), [
        'name'                   => $dest->name,
        'enabled'                => '1',
        'wordpress_url'          => $dest->wordpress_url,
        'wordpress_username'     => $dest->wordpress_username,
        'wordpress_app_password' => '',   // blank
        'wordpress_post_status'  => 'publish',
        'wordpress_category_ids' => '',
        'wordpress_tag_ids'      => '',
    ]);

    $dest->refresh();
    expect($dest->wordpress_app_password)->toBe('original-secret');
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

test('update rejects invalid wordpress post status', function () {
    $dest = OutputDestination::factory()->forUser($this->user)->wordpress()->create();

    $this->put(route('output_destinations.update', $dest), [
        'name'                   => 'WP',
        'enabled'                => '1',
        'wordpress_url'          => 'https://example.com',
        'wordpress_username'     => 'admin',
        'wordpress_app_password' => '',
        'wordpress_post_status'  => 'invalid_status',
        'wordpress_category_ids' => '',
        'wordpress_tag_ids'      => '',
    ])->assertSessionHasErrors('wordpress_post_status');
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