<?php

// =============================================================================
// HealthCheckControllerTest
//
// Feature tests for HealthCheckController.
//
// Covers:
//   1. index — renders the view with unresolved and resolved alerts
//   2. resolve — marks a Tier 3 alert as resolved
//   3. readme — renders the reference guide view
//   4. flushFailedJobsConfirm — shows confirmation page or redirects
//   5. flushFailedJobs — flushes failed_jobs and resolves the alert
//
// Admin gate: $user->email === config('admin.admin_email')
// =============================================================================

namespace Tests\Feature\MEDIA_PLATFORM\Tools\HealthChecks;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use Tests\TestCase;

class HealthCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create an admin user whose email matches config('admin.admin_email').
     */
    private function makeAdminUser(): User
    {
        return User::factory()->create(['email' => config('admin.admin_email')]);
    }

    /**
     * Create a non-admin user.
     */
    private function makeRegularUser(): User
    {
        return User::factory()->create(['email' => 'notadmin@example.com']);
    }

    /**
     * Insert a row into failed_jobs directly.
     */
    private function insertFailedJob(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid'       => \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue'      => 'default',
            'payload'    => '{}',
            'exception'  => 'RuntimeException: something went wrong',
            'failed_at'  => now(),
        ]);
    }

    /**
     * Create an unresolved AdminAlert for failed jobs.
     */
    private function makeFailedJobsAlert(): AdminAlert
    {
        return AdminAlert::create([
            'tier'        => 2,
            'category'    => 'queue',
            'title'       => 'Failed jobs detected',
            'message'     => '1 failed job in the queue.',
            'is_resolved' => false,
        ]);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  1. index                                                              ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_index_renders_for_admin(): void
    {
        $this->actingAs($this->makeAdminUser());

        $this->get(route('admin.health-checks.index'))
             ->assertOk()
             ->assertViewIs('media_platform.tools.health_checks.index');
    }

    public function test_index_passes_unresolved_and_resolved_alerts_to_view(): void
    {
        $this->actingAs($this->makeAdminUser());

        AdminAlert::create([
            'tier'        => 3,
            'category'    => 'queue',
            'title'       => 'Queue unreachable',
            'message'     => 'Cannot connect.',
            'is_resolved' => false,
        ]);

        AdminAlert::create([
            'tier'        => 2,
            'category'    => 'youtube',
            'title'       => 'YouTube quota exceeded',
            'message'     => 'Quota gone.',
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);

        $response = $this->get(route('admin.health-checks.index'));

        $this->assertCount(1, $response->viewData('unresolvedAlerts'));
        $this->assertCount(1, $response->viewData('resolvedAlerts'));
    }

    public function test_index_returns_403_for_non_admin(): void
    {
        $this->actingAs($this->makeRegularUser());

        $this->get(route('admin.health-checks.index'))
             ->assertForbidden();
    }

    public function test_index_redirects_unauthenticated_user_to_login(): void
    {
        $this->get(route('admin.health-checks.index'))
             ->assertRedirect('/login');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  2. resolve                                                            ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_resolve_marks_alert_as_resolved(): void
    {
        $this->actingAs($this->makeAdminUser());

        $alert = AdminAlert::create([
            'tier'        => 3,
            'category'    => 'queue',
            'title'       => 'Queue unreachable',
            'message'     => 'Cannot connect.',
            'is_resolved' => false,
        ]);

        $this->post(route('admin.health-checks.resolve', $alert));

        $this->assertDatabaseHas('admin_alerts', [
            'id'          => $alert->id,
            'is_resolved' => true,
        ]);
    }

    public function test_resolve_redirects_to_index_with_success_flash(): void
    {
        $this->actingAs($this->makeAdminUser());

        $alert = AdminAlert::create([
            'tier'        => 3,
            'category'    => 'queue',
            'title'       => 'Queue unreachable',
            'message'     => 'Cannot connect.',
            'is_resolved' => false,
        ]);

        $this->post(route('admin.health-checks.resolve', $alert))
             ->assertRedirect(route('admin.health-checks.index'))
             ->assertSessionHas('success');
    }

    public function test_resolve_returns_403_for_non_admin(): void
    {
        $this->actingAs($this->makeRegularUser());

        $alert = AdminAlert::create([
            'tier'        => 3,
            'category'    => 'queue',
            'title'       => 'Queue unreachable',
            'message'     => 'Cannot connect.',
            'is_resolved' => false,
        ]);

        $this->post(route('admin.health-checks.resolve', $alert))
             ->assertForbidden();
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  3. readme                                                             ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_readme_renders_for_admin(): void
    {
        $this->actingAs($this->makeAdminUser());

        $this->get(route('admin.health-checks.readme'))
             ->assertOk()
             ->assertViewIs('media_platform.tools.health_checks.readme');
    }

    public function test_readme_returns_403_for_non_admin(): void
    {
        $this->actingAs($this->makeRegularUser());

        $this->get(route('admin.health-checks.readme'))
             ->assertForbidden();
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  4. flushFailedJobsConfirm                                             ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_flush_confirm_renders_confirmation_view_when_failed_jobs_exist(): void
    {
        $this->actingAs($this->makeAdminUser());
        $this->insertFailedJob();

        $this->get(route('admin.health-checks.failed-jobs.flush.confirm'))
             ->assertOk()
             ->assertViewIs('media_platform.tools.health_checks.flush_failed_jobs_confirm')
             ->assertViewHas('count', 1);
    }

    public function test_flush_confirm_redirects_to_index_when_no_failed_jobs(): void
    {
        $this->actingAs($this->makeAdminUser());

        $this->get(route('admin.health-checks.failed-jobs.flush.confirm'))
             ->assertRedirect(route('admin.health-checks.index'))
             ->assertSessionHas('success');
    }

    public function test_flush_confirm_passes_correct_count_to_view(): void
    {
        $this->actingAs($this->makeAdminUser());

        $this->insertFailedJob();
        $this->insertFailedJob();
        $this->insertFailedJob();

        $response = $this->get(route('admin.health-checks.failed-jobs.flush.confirm'));

        $this->assertEquals(3, $response->viewData('count'));
    }

    public function test_flush_confirm_returns_403_for_non_admin(): void
    {
        $this->actingAs($this->makeRegularUser());

        $this->get(route('admin.health-checks.failed-jobs.flush.confirm'))
             ->assertForbidden();
    }

    public function test_flush_confirm_redirects_unauthenticated_user_to_login(): void
    {
        $this->get(route('admin.health-checks.failed-jobs.flush.confirm'))
             ->assertRedirect('/login');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  5. flushFailedJobs                                                    ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    public function test_flush_clears_failed_jobs_table(): void
    {
        $this->actingAs($this->makeAdminUser());
        $this->insertFailedJob();
        $this->insertFailedJob();

        $this->post(route('admin.health-checks.failed-jobs.flush'));

        $this->assertDatabaseCount('failed_jobs', 0);
    }

    public function test_flush_resolves_failed_jobs_alert_immediately(): void
    {
        $this->actingAs($this->makeAdminUser());
        $alert = $this->makeFailedJobsAlert();

        $this->post(route('admin.health-checks.failed-jobs.flush'));

        $this->assertDatabaseHas('admin_alerts', [
            'id'          => $alert->id,
            'is_resolved' => true,
        ]);
    }

    public function test_flush_redirects_to_index_with_success_flash(): void
    {
        $this->actingAs($this->makeAdminUser());
        $this->insertFailedJob();

        $this->post(route('admin.health-checks.failed-jobs.flush'))
             ->assertRedirect(route('admin.health-checks.index'))
             ->assertSessionHas('success');
    }

    public function test_flush_succeeds_even_when_no_alert_exists(): void
    {
        // The alert may not exist yet if flush is triggered before the
        // health check has had a chance to run and raise one.
        $this->actingAs($this->makeAdminUser());
        $this->insertFailedJob();

        $this->post(route('admin.health-checks.failed-jobs.flush'))
             ->assertRedirect(route('admin.health-checks.index'));

        $this->assertDatabaseCount('failed_jobs', 0);
    }

    public function test_flush_returns_403_for_non_admin(): void
    {
        $this->actingAs($this->makeRegularUser());

        $this->post(route('admin.health-checks.failed-jobs.flush'))
             ->assertForbidden();
    }

    public function test_flush_redirects_unauthenticated_user_to_login(): void
    {
        $this->post(route('admin.health-checks.failed-jobs.flush'))
             ->assertRedirect('/login');
    }
}