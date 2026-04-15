<?php

// =============================================================================
// HealthCheckServiceTest
//
// Unit tests for the HealthCheckService.
//
// iniToBytes() is public intentionally — it is a pure conversion helper with
// no side effects, and making it public allows it to be tested directly
// without needing to invoke runAll() and mock all external dependencies.
//
// Full integration tests for runAll() (mocking AlertService, LlmService,
// external APIs, etc.) are deferred to a future HealthCheckServiceIntegrationTest.
//
// Path: tests/Feature/MEDIA_PLATFORM/Tools/HealthChecks/
// =============================================================================

namespace Tests\Feature\MEDIA_PLATFORM\Tools\HealthChecks;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Tools\HealthChecks\Services\AlertService;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Tools\HealthChecks\Services\HealthCheckService;
use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use Tests\TestCase;

class HealthCheckServiceTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Build a HealthCheckService with mocked dependencies.
    // We are only testing iniToBytes() here — AlertService and LlmService
    // are never called, but the constructor requires them.
    // -------------------------------------------------------------------------

    private function makeService(): HealthCheckService
    {
        return new HealthCheckService(
            $this->createMock(AlertService::class),
            $this->createMock(LlmService::class),
        );
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  iniToBytes()                                                          ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    // ── Gigabytes ─────────────────────────────────────────────────────────────

    public function test_ini_to_bytes_converts_gigabytes(): void
    {
        $service = $this->makeService();

        $this->assertSame(1_073_741_824, $service->iniToBytes('1G'));
        $this->assertSame(2_147_483_648, $service->iniToBytes('2G'));
    }

    public function test_ini_to_bytes_converts_gigabytes_lowercase(): void
    {
        $service = $this->makeService();

        $this->assertSame(1_073_741_824, $service->iniToBytes('1g'));
    }

    // ── Megabytes ─────────────────────────────────────────────────────────────

    public function test_ini_to_bytes_converts_megabytes(): void
    {
        $service = $this->makeService();

        $this->assertSame(524_288_000, $service->iniToBytes('500M'));
        $this->assertSame(2_097_152,   $service->iniToBytes('2M'));
        $this->assertSame(8_388_608,   $service->iniToBytes('8M'));
        $this->assertSame(629_145_600, $service->iniToBytes('600M'));
    }

    public function test_ini_to_bytes_converts_megabytes_lowercase(): void
    {
        $service = $this->makeService();

        $this->assertSame(524_288_000, $service->iniToBytes('500m'));
    }

    // ── Kilobytes ─────────────────────────────────────────────────────────────

    public function test_ini_to_bytes_converts_kilobytes(): void
    {
        $service = $this->makeService();

        $this->assertSame(524_288, $service->iniToBytes('512K'));
        $this->assertSame(1_024,   $service->iniToBytes('1K'));
    }

    public function test_ini_to_bytes_converts_kilobytes_lowercase(): void
    {
        $service = $this->makeService();

        $this->assertSame(524_288, $service->iniToBytes('512k'));
    }

    // ── Plain integers ────────────────────────────────────────────────────────

    public function test_ini_to_bytes_handles_plain_integer_string(): void
    {
        $service = $this->makeService();

        $this->assertSame(300,         $service->iniToBytes('300'));
        $this->assertSame(0,           $service->iniToBytes('0'));
        $this->assertSame(1_073_741_824, $service->iniToBytes('1073741824'));
    }

    // ── Unlimited / special values ────────────────────────────────────────────

    public function test_ini_to_bytes_returns_negative_one_for_unlimited(): void
    {
        $service = $this->makeService();

        $this->assertSame(-1, $service->iniToBytes('-1'));
    }

    public function test_ini_to_bytes_handles_value_with_whitespace(): void
    {
        // ini_get() can return values with surrounding whitespace in some
        // environments — trim() in iniToBytes() should handle this cleanly.
        $service = $this->makeService();

        $this->assertSame(524_288_000, $service->iniToBytes(' 500M '));
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  checkFailedJobs()                                                     ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    // ── Pass — empty table ────────────────────────────────────────────────────

    /**
     * When the failed_jobs table is empty, no alert should be raised.
     */
    public function test_check_failed_jobs_passes_when_table_is_empty(): void
    {
        $service = $this->makeService();
        $service->checkFailedJobs();

        $this->assertDatabaseMissing('admin_alerts', [
            'title' => 'Failed jobs detected',
        ]);
    }

    /**
     * When the table is empty, any existing resolved alert for failed jobs
     * should be auto-resolved — i.e. no new unresolved alert is created.
     */
    public function test_check_failed_jobs_does_not_create_alert_when_table_is_empty(): void
    {
        $service = $this->makeService();
        $service->checkFailedJobs();

        $this->assertDatabaseCount('admin_alerts', 0);
    }

    
    // ── Fail — failed jobs present ────────────────────────────────────────────

    /**
     * When the failed_jobs table has rows, a Tier 2 alert should be raised.
     */
    public function test_check_failed_jobs_raises_tier_2_alert_when_jobs_exist(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid'       => \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue'      => 'default',
            'payload'    => '{}',
            'exception'  => 'RuntimeException: something went wrong',
            'failed_at'  => now(),
        ]);

        $service = $this->makeService();
        $service->checkFailedJobs();

        $this->assertDatabaseHas('admin_alerts', [
            'title'       => 'Failed jobs detected',
            'tier'        => 2,
            'category'    => 'queue',
            'is_resolved' => false,
        ]);
    }

    /**
     * The alert message should include the count of failed jobs.
     */
    public function test_check_failed_jobs_alert_message_includes_count(): void
    {
        DB::table('failed_jobs')->insert([
            [
                'uuid'       => \Illuminate\Support\Str::uuid(),
                'connection' => 'database',
                'queue'      => 'default',
                'payload'    => '{}',
                'exception'  => 'Exception: first failure',
                'failed_at'  => now()->subMinutes(10),
            ],
            [
                'uuid'       => \Illuminate\Support\Str::uuid(),
                'connection' => 'database',
                'queue'      => 'default',
                'payload'    => '{}',
                'exception'  => 'Exception: second failure',
                'failed_at'  => now()->subMinutes(5),
            ],
        ]);

        $service = $this->makeService();
        $service->checkFailedJobs();

        $alert = AdminAlert::where('title', 'Failed jobs detected')->first();

        $this->assertNotNull($alert);
        $this->assertStringContainsString('2', $alert->message);
    }

    /**
     * raiseIfNew() should not create a duplicate alert if one already exists.
     */
    public function test_check_failed_jobs_does_not_duplicate_existing_unresolved_alert(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid'       => \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue'      => 'default',
            'payload'    => '{}',
            'exception'  => 'RuntimeException: something went wrong',
            'failed_at'  => now(),
        ]);

        $service = $this->makeService();

        // Call twice — should still only produce one alert.
        $service->checkFailedJobs();
        $service->checkFailedJobs();

        $this->assertDatabaseCount('admin_alerts', 1);
    }
}