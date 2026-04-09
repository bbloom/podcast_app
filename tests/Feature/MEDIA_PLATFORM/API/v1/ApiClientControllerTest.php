<?php

namespace Tests\Feature\MEDIA_PLATFORM\API\v1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use MediaPlatform\API\v1\Models\ApiClient;
use Tests\TestCase;

class ApiClientControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['email' => config('admin.admin_email')]);
    }

    private function nonAdmin(): User
    {
        return User::factory()->create(['email' => 'nonadmin@example.com']);
    }

    private function makeClient(array $overrides = []): ApiClient
    {
        return ApiClient::create(array_merge([
            'label'      => 'Test Client',
            'domain'     => 'example.com',
            'token_hash' => Hash::make('secret'),
            'is_active'  => true,
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'label'     => 'Cloudflare EmDash',
            'domain'    => 'mypodcast.com',
            'is_active' => '1',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Shared: non-admin redirects to dashboard with error
    // -------------------------------------------------------------------------

    private function assertNonAdminRedirect($response): void
    {
        $response->assertRedirect(route('dashboard'))
                 ->assertSessionHas('error');
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_admin_can_view_client_index(): void
    {
        $this->makeClient();

        $this->actingAs($this->admin())
            ->get(route('api_management.clients.index'))
            ->assertOk()
            ->assertSee('Test Client');
    }

    public function test_non_admin_cannot_view_client_index(): void
    {
        $this->assertNonAdminRedirect(
            $this->actingAs($this->nonAdmin())->get(route('api_management.clients.index'))
        );
    }

    public function test_unauthenticated_user_is_redirected_from_index(): void
    {
        $this->get(route('api_management.clients.index'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_admin_can_view_create_form(): void
    {
        $this->actingAs($this->admin())
            ->get(route('api_management.clients.create'))
            ->assertOk();
    }

    public function test_non_admin_cannot_view_create_form(): void
    {
        $this->assertNonAdminRedirect(
            $this->actingAs($this->nonAdmin())->get(route('api_management.clients.create'))
        );
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_admin_can_create_client(): void
    {
        $this->actingAs($this->admin())
            ->post(route('api_management.clients.store'), $this->payload())
            ->assertRedirect(route('api_management.clients.show', ApiClient::first()));

        $this->assertDatabaseHas('api_clients', ['domain' => 'mypodcast.com']);
    }

    public function test_store_generates_token_and_flashes_it_once(): void
    {
        $this->actingAs($this->admin())
            ->post(route('api_management.clients.store'), $this->payload())
            ->assertSessionHas('token');
    }

    public function test_store_token_hash_is_not_empty(): void
    {
        $this->actingAs($this->admin())
            ->post(route('api_management.clients.store'), $this->payload());

        $this->assertNotEmpty(ApiClient::first()->token_hash);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin())
            ->post(route('api_management.clients.store'), [])
            ->assertSessionHasErrors(['label', 'domain', 'is_active']);
    }

    public function test_store_validates_domain_uniqueness(): void
    {
        $this->makeClient(['domain' => 'mypodcast.com']);

        $this->actingAs($this->admin())
            ->post(route('api_management.clients.store'), $this->payload())
            ->assertSessionHasErrors(['domain']);
    }

    public function test_non_admin_cannot_create_client(): void
    {
        $this->assertNonAdminRedirect(
            $this->actingAs($this->nonAdmin())->post(route('api_management.clients.store'), $this->payload())
        );
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_admin_can_view_client(): void
    {
        $client = $this->makeClient();

        $this->actingAs($this->admin())
            ->get(route('api_management.clients.show', $client))
            ->assertOk()
            ->assertSee($client->label)
            ->assertSee($client->domain);
    }

    public function test_show_does_not_display_token_hash(): void
    {
        $client = $this->makeClient();

        $this->actingAs($this->admin())
            ->get(route('api_management.clients.show', $client))
            ->assertOk()
            ->assertDontSee($client->token_hash);
    }

    public function test_non_admin_cannot_view_client(): void
    {
        $client = $this->makeClient();

        $this->assertNonAdminRedirect(
            $this->actingAs($this->nonAdmin())->get(route('api_management.clients.show', $client))
        );
    }

    public function test_show_returns_404_for_non_existent_client(): void
    {
        $this->actingAs($this->admin())
            ->get(route('api_management.clients.show', 99999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    public function test_admin_can_view_edit_form(): void
    {
        $client = $this->makeClient();

        $this->actingAs($this->admin())
            ->get(route('api_management.clients.edit', $client))
            ->assertOk()
            ->assertSee($client->label);
    }

    public function test_non_admin_cannot_view_edit_form(): void
    {
        $client = $this->makeClient();

        $this->assertNonAdminRedirect(
            $this->actingAs($this->nonAdmin())->get(route('api_management.clients.edit', $client))
        );
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_admin_can_update_client(): void
    {
        $client = $this->makeClient();

        $this->actingAs($this->admin())
            ->put(route('api_management.clients.update', $client), $this->payload(['label' => 'Updated Label']))
            ->assertRedirect(route('api_management.clients.show', $client));

        $this->assertDatabaseHas('api_clients', ['id' => $client->id, 'label' => 'Updated Label']);
    }

    public function test_update_does_not_change_token_hash(): void
    {
        $client       = $this->makeClient();
        $originalHash = $client->token_hash;

        $this->actingAs($this->admin())
            ->put(route('api_management.clients.update', $client), $this->payload());

        $this->assertEquals($originalHash, $client->fresh()->token_hash);
    }

    public function test_update_allows_client_to_keep_its_own_domain(): void
    {
        $client = $this->makeClient(['domain' => 'mypodcast.com']);

        $this->actingAs($this->admin())
            ->put(route('api_management.clients.update', $client), $this->payload(['domain' => 'mypodcast.com']))
            ->assertRedirect(route('api_management.clients.show', $client));
    }

    public function test_non_admin_cannot_update_client(): void
    {
        $client = $this->makeClient();

        $this->assertNonAdminRedirect(
            $this->actingAs($this->nonAdmin())->put(route('api_management.clients.update', $client), $this->payload())
        );
    }

    // -------------------------------------------------------------------------
    // deleteConfirm
    // -------------------------------------------------------------------------

    public function test_admin_can_view_delete_confirm_page(): void
    {
        $client = $this->makeClient();

        $this->actingAs($this->admin())
            ->get(route('api_management.clients.delete.confirm', $client))
            ->assertOk()
            ->assertSee($client->label);
    }

    public function test_non_admin_cannot_view_delete_confirm_page(): void
    {
        $client = $this->makeClient();

        $this->assertNonAdminRedirect(
            $this->actingAs($this->nonAdmin())->get(route('api_management.clients.delete.confirm', $client))
        );
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_admin_can_delete_client(): void
    {
        $client = $this->makeClient();

        $this->actingAs($this->admin())
            ->delete(route('api_management.clients.destroy', $client))
            ->assertRedirect(route('api_management.clients.index'));

        $this->assertDatabaseMissing('api_clients', ['id' => $client->id]);
    }

    public function test_non_admin_cannot_delete_client(): void
    {
        $client = $this->makeClient();

        $this->assertNonAdminRedirect(
            $this->actingAs($this->nonAdmin())->delete(route('api_management.clients.destroy', $client))
        );

        $this->assertDatabaseHas('api_clients', ['id' => $client->id]);
    }

    public function test_destroy_returns_404_for_non_existent_client(): void
    {
        $this->actingAs($this->admin())
            ->delete(route('api_management.clients.destroy', 99999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // rotateToken
    // -------------------------------------------------------------------------

    public function test_admin_can_rotate_token(): void
    {
        $client       = $this->makeClient();
        $originalHash = $client->token_hash;

        $this->actingAs($this->admin())
            ->post(route('api_management.clients.rotate_token', $client))
            ->assertRedirect(route('api_management.clients.show', $client))
            ->assertSessionHas('token');

        $this->assertNotEquals($originalHash, $client->fresh()->token_hash);
    }

    public function test_rotated_token_is_valid_against_new_hash(): void
    {
        $client = $this->makeClient();

        $response   = $this->actingAs($this->admin())
            ->post(route('api_management.clients.rotate_token', $client));

        $plainToken = $response->getSession()->get('token');
        $this->assertTrue(Hash::check($plainToken, $client->fresh()->token_hash));
    }

    public function test_non_admin_cannot_rotate_token(): void
    {
        $client       = $this->makeClient();
        $originalHash = $client->token_hash;

        $this->assertNonAdminRedirect(
            $this->actingAs($this->nonAdmin())->post(route('api_management.clients.rotate_token', $client))
        );

        $this->assertEquals($originalHash, $client->fresh()->token_hash);
    }
}