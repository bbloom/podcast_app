<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

abstract class TestCase extends BaseTestCase
{
    /**
     * CSRF protection exists to prevent cross-site request forgery — malicious
     * third-party websites tricking a logged-in user's browser into submitting
     * a form to your app. It works by requiring a token that only your own
     * forms possess.
     *
     * In tests, HTTP requests are made programmatically by PHPUnit/Pest —
     * there is no browser, no cross-site risk, and no real session cookie flow.
     * Laravel's own test helpers ($this->post(), $this->put() etc.) were never
     * designed to carry CSRF tokens. Disabling PreventRequestForgery in the
     * test environment is the standard Laravel practice and is even how
     * Laravel's own starter kits ship.
     *
     * Why Laravel 13 made this visible:
     * The new PreventRequestForgery middleware added origin verification via
     * the Sec-Fetch-Site header on top of the token check. Test HTTP clients
     * don't send that header, so even tests that previously passed the token
     * check now fail the origin check. This is what exposed the issue — not
     * a regression in your app.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    /**
     * Authenticate as an admin user.
     *
     * The admin gate is defined as:
     *   $user->email === config('admin.admin_email')
     *
     * This helper creates a user with that email so all admin-protected routes
     * return 200 rather than 403 in tests.
     *
     * Usage:
     *   protected function setUp(): void
     *   {
     *       parent::setUp();
     *       $this->actingAsAdmin();
     *   }
     */
    protected function actingAsAdmin(): static
    {
        $admin = User::factory()->create([
            'email' => config('admin.admin_email'),
        ]);

        return $this->actingAs($admin);
    }
}