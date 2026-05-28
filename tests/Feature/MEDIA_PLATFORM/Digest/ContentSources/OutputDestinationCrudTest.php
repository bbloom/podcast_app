<?php

// tests/Feature/MEDIA_PLATFORM/Digest/ContentSources/OutputDestinationCrudTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\ContentSources;

use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OutputDestinationCrudTest extends TestCase
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
    // GROUP 1: index
    // =========================================================================

    #[Test]
    public function index_renders_for_authenticated_user(): void
    {
        OutputDestination::factory()->forUser($this->user)->count(2)->create();

        $this->get(route('output_destinations.index'))
             ->assertOk()
             ->assertViewIs('media_platform.digest.content_sources.output_destinations.index')
             ->assertViewHas('destinations');
    }

    #[Test]
    public function index_only_shows_the_current_users_destinations(): void
    {
        $otherUser = User::factory()->create();
        OutputDestination::factory()->forUser($this->user)->create(['name' => 'Mine']);
        OutputDestination::factory()->forUser($otherUser)->create(['name' => 'Theirs']);

        $destinations = $this->get(route('output_destinations.index'))->viewData('destinations');

        $this->assertCount(1, $destinations);
        $this->assertSame('Mine', $destinations->first()->name);
    }

    // =========================================================================
    // GROUP 2: edit
    // =========================================================================

    #[Test]
    public function edit_renders_for_owner(): void
    {
        $dest = OutputDestination::factory()->forUser($this->user)->create();

        $this->get(route('output_destinations.edit', $dest))
             ->assertOk()
             ->assertViewIs('media_platform.digest.content_sources.output_destinations.edit')
             ->assertViewHas('outputDestination');
    }

    #[Test]
    public function edit_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $dest      = OutputDestination::factory()->forUser($otherUser)->create();

        $this->get(route('output_destinations.edit', $dest))->assertForbidden();
    }

    // =========================================================================
    // GROUP 2b: show
    // =========================================================================

    #[Test]
    public function show_renders_for_owner(): void
    {
        $dest = OutputDestination::factory()->forUser($this->user)->create();

        $this->get(route('output_destinations.show', $dest))
             ->assertOk()
             ->assertViewIs('media_platform.digest.content_sources.output_destinations.show')
             ->assertViewHas('outputDestination')
             ->assertViewHas('lists');
    }

    #[Test]
    public function show_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $dest      = OutputDestination::factory()->forUser($otherUser)->create();

        $this->get(route('output_destinations.show', $dest))->assertForbidden();
    }

    #[Test]
    public function show_returns_404_for_missing_record(): void
    {
        $this->get(route('output_destinations.show', 99999))->assertNotFound();
    }

    // =========================================================================
    // GROUP 3: update — happy path (sftp)
    // =========================================================================

    #[Test]
    public function update_saves_sftp_destination_correctly(): void
    {
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
        $this->assertSame('New Name', $dest->name);
        $this->assertSame('new.example.com', $dest->host);
    }

    #[Test]
    public function update_preserves_existing_password_when_blank_is_submitted(): void
    {
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
            'password'  => '',
            'path'      => $dest->path,
        ]);

        $this->assertSame('original-secret', $dest->fresh()->password);
    }

    // =========================================================================
    // GROUP 4: update — validation
    // =========================================================================

    #[Test]
    public function update_rejects_missing_name(): void
    {
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
    }

    #[Test]
    public function update_rejects_invalid_port(): void
    {
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
    }

    // =========================================================================
    // GROUP 5: update — ownership
    // =========================================================================

    #[Test]
    public function update_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $dest      = OutputDestination::factory()->forUser($otherUser)->create();

        $this->put(route('output_destinations.update', $dest), [
            'name'    => 'Hacked',
            'enabled' => '1',
        ])->assertForbidden();
    }

    // =========================================================================
    // GROUP 6: confirmDelete
    // =========================================================================

    #[Test]
    public function confirmDelete_renders_for_owner(): void
    {
        $dest = OutputDestination::factory()->forUser($this->user)->create();

        $this->get(route('output_destinations.delete.confirm', $dest))
             ->assertOk()
             ->assertViewIs('media_platform.digest.content_sources.output_destinations.delete-confirm')
             ->assertViewHas('outputDestination');
    }

    #[Test]
    public function confirmDelete_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $dest      = OutputDestination::factory()->forUser($otherUser)->create();

        $this->get(route('output_destinations.delete.confirm', $dest))->assertForbidden();
    }

    // =========================================================================
    // GROUP 7: destroy
    // =========================================================================

    #[Test]
    public function destroy_deletes_unused_destination_and_redirects(): void
    {
        $dest = OutputDestination::factory()->forUser($this->user)->create();

        $this->delete(route('output_destinations.destroy', $dest))
             ->assertRedirect(route('output_destinations.index'))
             ->assertSessionHas('success');

        $this->assertDatabaseMissing('output_destinations', ['id' => $dest->id]);
    }

    #[Test]
    public function destroy_blocks_deletion_when_destination_is_used_by_a_list(): void
    {
        $dest = OutputDestination::factory()->forUser($this->user)->create();
        ListModel::factory()->forUser($this->user)->webpage($dest->id)->create();

        $this->delete(route('output_destinations.destroy', $dest))
             ->assertRedirect(route('output_destinations.index'))
             ->assertSessionHas('error');

        $this->assertDatabaseHas('output_destinations', ['id' => $dest->id]);
    }

    #[Test]
    public function destroy_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $dest      = OutputDestination::factory()->forUser($otherUser)->create();

        $this->delete(route('output_destinations.destroy', $dest))->assertForbidden();
    }
}