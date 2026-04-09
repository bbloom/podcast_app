<?php

namespace Tests\Feature\MEDIA_PLATFORM\API\v1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\API\v1\Models\ApiControl;
use Tests\TestCase;

class ApiControlControllerTest extends TestCase
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
    // Enable
    // -------------------------------------------------------------------------

    public function test_admin_can_enable_api(): void
    {
        $this->actingAs($this->admin())
            ->post(route('api_management.control.enable'))
            ->assertRedirect(route('api_management.dashboard'));

        $this->assertTrue(ApiControl::instance()->is_enabled);
    }

    public function test_enable_sets_enabled_at_timestamp(): void
    {
        $this->actingAs($this->admin())
            ->post(route('api_management.control.enable'));

        $this->assertNotNull(ApiControl::instance()->enabled_at);
    }

    public function test_non_admin_cannot_enable_api(): void
    {
        $this->actingAs($this->nonAdmin())
            ->post(route('api_management.control.enable'))
            ->assertRedirect(route('dashboard'));

        $this->assertFalse(ApiControl::instance()->is_enabled);
    }

    public function test_non_admin_enable_redirects_with_error_message(): void
    {
        $this->actingAs($this->nonAdmin())
            ->post(route('api_management.control.enable'))
            ->assertSessionHas('error');
    }

    public function test_unauthenticated_user_cannot_enable_api(): void
    {
        $this->post(route('api_management.control.enable'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Disable
    // -------------------------------------------------------------------------

    public function test_admin_can_disable_api(): void
    {
        ApiControl::instance()->enable();

        $this->actingAs($this->admin())
            ->post(route('api_management.control.disable'))
            ->assertRedirect(route('api_management.dashboard'));

        $this->assertFalse(ApiControl::instance()->is_enabled);
    }

    public function test_disable_sets_disabled_at_timestamp(): void
    {
        ApiControl::instance()->enable();

        $this->actingAs($this->admin())
            ->post(route('api_management.control.disable'));

        $this->assertNotNull(ApiControl::instance()->disabled_at);
    }

    public function test_non_admin_cannot_disable_api(): void
    {
        ApiControl::instance()->enable();

        $this->actingAs($this->nonAdmin())
            ->post(route('api_management.control.disable'))
            ->assertRedirect(route('dashboard'));

        $this->assertTrue(ApiControl::instance()->is_enabled);
    }

    public function test_non_admin_disable_redirects_with_error_message(): void
    {
        $this->actingAs($this->nonAdmin())
            ->post(route('api_management.control.disable'))
            ->assertSessionHas('error');
    }

    public function test_unauthenticated_user_cannot_disable_api(): void
    {
        $this->post(route('api_management.control.disable'))
            ->assertRedirect(route('login'));
    }
}