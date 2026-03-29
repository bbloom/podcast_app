<?php

namespace Tests\Feature\MEDIA_PLATFORM\Tools\PhpServerlessProjectSponsors;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Tools\PhpServerlessProjectSponsors\Models\PhpServerlessProjectSponsor;
use Tests\TestCase;

class PhpServerlessProjectSponsorControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Return a user whose email matches the admin gate. */
    private function adminUser(): User
    {
        return User::factory()->create(['email' => config('admin.admin_email')]);
    }

    /** Minimum valid payload for creating/updating a sponsor. */
    private function sponsorPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name'        => 'Jane Smith',
            'email_address'    => 'jane@example.com',
            'profile_full'     => 'Jane is a long-time supporter of the PHP community.',
            'umbrella_sponsor' => '1',
            'basecamp_sponsor' => '0',
            'restream_sponsor' => '0',
            'former_sponsor'   => '0',
            'enabled'          => '1',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_shows_sponsors_to_admin_users(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create(['full_name' => 'John Doe']);

        $this->actingAs($this->adminUser())
            ->get(route('phpserverlessproject_sponsors.index'))
            ->assertOk()
            ->assertSee('John Doe');
    }

    public function test_index_returns_403_for_non_admin_users(): void
    {
        $this->actingAs(User::factory()->create(['email' => 'regular@example.com']))
            ->get(route('phpserverlessproject_sponsors.index'))
            ->assertForbidden();
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('phpserverlessproject_sponsors.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_shows_empty_state_when_no_sponsors_exist(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('phpserverlessproject_sponsors.index'))
            ->assertOk()
            ->assertSee('No sponsors yet');
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_create_shows_form_to_admin_users(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('phpserverlessproject_sponsors.create'))
            ->assertOk();
    }

    public function test_create_returns_403_for_non_admin_users(): void
    {
        $this->actingAs(User::factory()->create(['email' => 'regular@example.com']))
            ->get(route('phpserverlessproject_sponsors.create'))
            ->assertForbidden();
    }

    public function test_create_redirects_unauthenticated_users(): void
    {
        $this->get(route('phpserverlessproject_sponsors.create'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_sponsor_and_redirects(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('phpserverlessproject_sponsors.store'), $this->sponsorPayload())
            ->assertRedirect(route('phpserverlessproject_sponsors.index'));

        $this->assertDatabaseHas('phpserverlessproject_sponsors', ['full_name' => 'Jane Smith']);
    }

    public function test_store_returns_403_for_non_admin_users(): void
    {
        $this->actingAs(User::factory()->create(['email' => 'regular@example.com']))
            ->post(route('phpserverlessproject_sponsors.store'), $this->sponsorPayload())
            ->assertForbidden();
    }

    public function test_store_redirects_unauthenticated_users(): void
    {
        $this->post(route('phpserverlessproject_sponsors.store'), $this->sponsorPayload())
            ->assertRedirect(route('login'));
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('phpserverlessproject_sponsors.store'), [])
            ->assertSessionHasErrors(['full_name', 'email_address', 'profile_full', 'enabled']);
    }

    public function test_store_validates_full_name_uniqueness(): void
    {
        PhpServerlessProjectSponsor::factory()->create(['full_name' => 'Jane Smith']);

        $this->actingAs($this->adminUser())
            ->post(route('phpserverlessproject_sponsors.store'), $this->sponsorPayload())
            ->assertSessionHasErrors(['full_name']);
    }

    public function test_store_validates_url_fields(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('phpserverlessproject_sponsors.store'), $this->sponsorPayload([
                'image_url'               => 'not-a-url',
                'image_thumbnail_url'     => 'not-a-url',
                'link_to_sponsor_website' => 'not-a-url',
            ]))
            ->assertSessionHasErrors(['image_url', 'image_thumbnail_url', 'link_to_sponsor_website']);
    }

    public function test_store_validates_email_field(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('phpserverlessproject_sponsors.store'), $this->sponsorPayload([
                'email_address' => 'not-an-email',
            ]))
            ->assertSessionHasErrors(['email_address']);
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_displays_sponsor_to_admin_users(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create(['full_name' => 'Jane Smith']);

        $this->actingAs($this->adminUser())
            ->get(route('phpserverlessproject_sponsors.show', $sponsor))
            ->assertOk()
            ->assertSee('Jane Smith');
    }

    public function test_show_returns_403_for_non_admin_users(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create();

        $this->actingAs(User::factory()->create(['email' => 'regular@example.com']))
            ->get(route('phpserverlessproject_sponsors.show', $sponsor))
            ->assertForbidden();
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create();

        $this->get(route('phpserverlessproject_sponsors.show', $sponsor))
            ->assertRedirect(route('login'));
    }

    public function test_show_returns_404_for_non_existent_sponsor(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('phpserverlessproject_sponsors.show', 99999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    public function test_edit_shows_form_to_admin_users(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create();

        $this->actingAs($this->adminUser())
            ->get(route('phpserverlessproject_sponsors.edit', $sponsor))
            ->assertOk()
            ->assertSee($sponsor->full_name);
    }

    public function test_edit_returns_403_for_non_admin_users(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create();

        $this->actingAs(User::factory()->create(['email' => 'regular@example.com']))
            ->get(route('phpserverlessproject_sponsors.edit', $sponsor))
            ->assertForbidden();
    }

    public function test_edit_redirects_unauthenticated_users(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create();

        $this->get(route('phpserverlessproject_sponsors.edit', $sponsor))
            ->assertRedirect(route('login'));
    }

    public function test_edit_returns_404_for_non_existent_sponsor(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('phpserverlessproject_sponsors.edit', 99999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_saves_changes_and_redirects(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create(['full_name' => 'Old Name']);

        $this->actingAs($this->adminUser())
            ->put(route('phpserverlessproject_sponsors.update', $sponsor), $this->sponsorPayload(['full_name' => 'New Name']))
            ->assertRedirect(route('phpserverlessproject_sponsors.index'));

        $this->assertDatabaseHas('phpserverlessproject_sponsors', ['id' => $sponsor->id, 'full_name' => 'New Name']);
    }

    public function test_update_returns_403_for_non_admin_users(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create();

        $this->actingAs(User::factory()->create(['email' => 'regular@example.com']))
            ->put(route('phpserverlessproject_sponsors.update', $sponsor), $this->sponsorPayload())
            ->assertForbidden();
    }

    public function test_update_redirects_unauthenticated_users(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create();

        $this->put(route('phpserverlessproject_sponsors.update', $sponsor), $this->sponsorPayload())
            ->assertRedirect(route('login'));
    }

    public function test_update_validates_required_fields(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create();

        $this->actingAs($this->adminUser())
            ->put(route('phpserverlessproject_sponsors.update', $sponsor), [])
            ->assertSessionHasErrors(['full_name', 'email_address', 'profile_full', 'enabled']);
    }

    public function test_update_allows_sponsor_to_keep_its_own_full_name(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create(['full_name' => 'Jane Smith']);

        $this->actingAs($this->adminUser())
            ->put(route('phpserverlessproject_sponsors.update', $sponsor), $this->sponsorPayload(['full_name' => 'Jane Smith']))
            ->assertRedirect(route('phpserverlessproject_sponsors.index'));
    }

    public function test_update_returns_404_for_non_existent_sponsor(): void
    {
        $this->actingAs($this->adminUser())
            ->put(route('phpserverlessproject_sponsors.update', 99999), $this->sponsorPayload())
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // deleteConfirm
    // -------------------------------------------------------------------------

    public function test_delete_confirm_shows_page_to_admin_users(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create();

        $this->actingAs($this->adminUser())
            ->get(route('phpserverlessproject_sponsors.delete.confirm', $sponsor))
            ->assertOk()
            ->assertSee($sponsor->full_name);
    }

    public function test_delete_confirm_returns_403_for_non_admin_users(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create();

        $this->actingAs(User::factory()->create(['email' => 'regular@example.com']))
            ->get(route('phpserverlessproject_sponsors.delete.confirm', $sponsor))
            ->assertForbidden();
    }

    public function test_delete_confirm_redirects_unauthenticated_users(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create();

        $this->get(route('phpserverlessproject_sponsors.delete.confirm', $sponsor))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_sponsor_and_redirects(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create();

        $this->actingAs($this->adminUser())
            ->delete(route('phpserverlessproject_sponsors.destroy', $sponsor))
            ->assertRedirect(route('phpserverlessproject_sponsors.index'));

        $this->assertDatabaseMissing('phpserverlessproject_sponsors', ['id' => $sponsor->id]);
    }

    public function test_destroy_returns_403_for_non_admin_users(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create();

        $this->actingAs(User::factory()->create(['email' => 'regular@example.com']))
            ->delete(route('phpserverlessproject_sponsors.destroy', $sponsor))
            ->assertForbidden();

        $this->assertDatabaseHas('phpserverlessproject_sponsors', ['id' => $sponsor->id]);
    }

    public function test_destroy_redirects_unauthenticated_users(): void
    {
        $sponsor = PhpServerlessProjectSponsor::factory()->create();

        $this->delete(route('phpserverlessproject_sponsors.destroy', $sponsor))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('phpserverlessproject_sponsors', ['id' => $sponsor->id]);
    }

    public function test_destroy_returns_404_for_non_existent_sponsor(): void
    {
        $this->actingAs($this->adminUser())
            ->delete(route('phpserverlessproject_sponsors.destroy', 99999))
            ->assertNotFound();
    }
}