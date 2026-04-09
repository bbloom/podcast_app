<?php

namespace Tests\Feature\MEDIA_PLATFORM\API\v1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use MediaPlatform\API\v1\Models\ApiClient;
use MediaPlatform\API\v1\Models\ApiControl;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
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

    // -------------------------------------------------------------------------
    // Dashboard access
    // -------------------------------------------------------------------------

    public function test_admin_can_view_dashboard(): void
    {
        $this->actingAs($this->admin())
            ->get(route('api_management.dashboard'))
            ->assertOk();
    }

    public function test_non_admin_cannot_view_dashboard(): void
    {
        $this->actingAs($this->nonAdmin())
            ->get(route('api_management.dashboard'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get(route('api_management.dashboard'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Dashboard content
    // -------------------------------------------------------------------------

    public function test_dashboard_shows_api_disabled_status_by_default(): void
    {
        $this->actingAs($this->admin())
            ->get(route('api_management.dashboard'))
            ->assertOk()
            ->assertSee('Disabled');
    }

    public function test_dashboard_shows_api_enabled_status_when_enabled(): void
    {
        ApiControl::instance()->enable();

        $this->actingAs($this->admin())
            ->get(route('api_management.dashboard'))
            ->assertOk()
            ->assertSee('Enabled');
    }

    public function test_dashboard_lists_api_clients(): void
    {
        ApiClient::create([
            'label'      => 'Test Client',
            'domain'     => 'example.com',
            'token_hash' => Hash::make('secret'),
            'is_active'  => true,
        ]);

        $this->actingAs($this->admin())
            ->get(route('api_management.dashboard'))
            ->assertOk()
            ->assertSee('Test Client');
    }
}