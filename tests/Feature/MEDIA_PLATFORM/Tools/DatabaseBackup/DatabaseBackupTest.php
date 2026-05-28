<?php

// tests/Feature/MEDIA_PLATFORM/Tools/DatabaseBackup/DatabaseBackupTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Tools\DatabaseBackup;

use MediaPlatform\Tools\DatabaseBackup\Commands\BackupDatabaseCommand;
use MediaPlatform\Tools\DatabaseBackup\Mails\BackupFailedMail;
use MediaPlatform\Tools\DatabaseBackup\Models\DatabaseBackupLog;
use MediaPlatform\Tools\DatabaseBackup\Services\BackupService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeAdminUser(): User
    {
        return User::factory()->create(['email' => config('admin.admin_email')]);
    }

    private function makeRegularUser(): User
    {
        return User::factory()->create(['email' => 'notadmin@example.com']);
    }

    private function makeBackupLog(array $overrides = []): DatabaseBackupLog
    {
        return DatabaseBackupLog::create(array_merge([
            'status'           => 'success',
            'filename'         => 'backups/newsrag_2026-01-01_03-00-00.sql.gz',
            'file_size_bytes'  => 1_048_576,
            'duration_seconds' => 12,
            'message'          => 'Backup completed successfully.',
            'ran_at'           => now()->subHour(),
        ], $overrides));
    }

    private function makeTestableBackupService(
        bool $uploadShouldFail = false,
        bool $pruneShouldFail  = false,
        int  $prunedCount      = 0,
    ): BackupService {
        return new class(
            $uploadShouldFail,
            $pruneShouldFail,
            $prunedCount,
        ) extends BackupService {
            public array $uploadedKeys = [];

            public function __construct(
                private readonly bool $uploadShouldFail,
                private readonly bool $pruneShouldFail,
                private readonly int  $prunedCount,
            ) {}

            protected function gzipFile(string $sourcePath, string $destPath): void
            {
                $gz = gzopen($destPath, 'wb');
                gzwrite($gz, '');
                gzclose($gz);
            }

            protected function uploadToS3(string $localPath, string $s3Key): void
            {
                if ($this->uploadShouldFail) {
                    throw new \RuntimeException('S3 upload failed: simulated AWS error');
                }
                $this->uploadedKeys[] = $s3Key;
            }

            protected function pruneS3Backups(): int
            {
                if ($this->pruneShouldFail) {
                    throw new \RuntimeException('S3 pruning failed: simulated AWS error');
                }
                return $this->prunedCount;
            }
        };
    }

    // =========================================================================
    // GROUP 1: DatabaseBackupLog model
    // =========================================================================

    #[Test]
    public function isSuccess_returns_true_for_success_status(): void
    {
        $log = $this->makeBackupLog(['status' => 'success']);
        $this->assertTrue($log->isSuccess());
        $this->assertFalse($log->isFailure());
    }

    #[Test]
    public function isFailure_returns_true_for_failure_status(): void
    {
        $log = $this->makeBackupLog(['status' => 'failure']);
        $this->assertTrue($log->isFailure());
        $this->assertFalse($log->isSuccess());
    }

    #[Test]
    public function humanFileSize_formats_bytes_correctly(): void
    {
        $mb  = $this->makeBackupLog(['file_size_bytes' => 4_404_019]);
        $kb  = $this->makeBackupLog(['file_size_bytes' => 10_240]);
        $b   = $this->makeBackupLog(['file_size_bytes' => 512]);
        $nil = $this->makeBackupLog(['file_size_bytes' => null]);

        $this->assertStringContainsString('MB', $mb->humanFileSize());
        $this->assertStringContainsString('KB', $kb->humanFileSize());
        $this->assertStringContainsString('B', $b->humanFileSize());
        $this->assertNull($nil->humanFileSize());
    }

    #[Test]
    public function model_has_no_updated_at_column(): void
    {
        $log = $this->makeBackupLog();
        $log->touch(); // throws if UPDATED_AT column unexpectedly exists and fails
        $this->assertNull(DatabaseBackupLog::UPDATED_AT);
    }

    #[Test]
    public function ran_at_is_cast_to_Carbon(): void
    {
        $log = $this->makeBackupLog(['ran_at' => '2026-01-15 03:00:00']);
        $this->assertInstanceOf(Carbon::class, $log->ran_at);
    }

    // =========================================================================
    // GROUP 2: BackupService — happy path
    // =========================================================================

    #[Test]
    public function run_returns_overall_status_success_when_all_steps_pass(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);

        $result = $this->makeTestableBackupService()->run();

        $this->assertSame('success', $result['overall_status']);
    }

    #[Test]
    public function run_writes_a_success_log_row_to_the_database(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);

        $this->makeTestableBackupService()->run();

        $this->assertDatabaseHas('database_backup_logs', ['status' => 'success']);
    }

    #[Test]
    public function run_result_contains_all_expected_step_labels_on_success(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);

        $result = $this->makeTestableBackupService()->run();
        $labels = collect($result['steps'])->pluck('label')->toArray();

        $this->assertContains('Generate filename',      $labels);
        $this->assertContains('pg_dump',                $labels);
        $this->assertContains('Gzip compress',          $labels);
        $this->assertContains('Upload to S3',           $labels);
        $this->assertContains('Integrity check',        $labels);
        $this->assertContains('Prune old S3 backups',   $labels);
        $this->assertContains('Prune old log rows',     $labels);
    }

    #[Test]
    public function run_uploads_file_to_the_configured_S3_folder(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);

        Config::set('database_backup.s3_folder', 'mybackups');
        Config::set('database_backup.filename_prefix', 'testapp');

        $svc = $this->makeTestableBackupService();
        $svc->run();

        $this->assertCount(1, $svc->uploadedKeys);
        $this->assertStringStartsWith('mybackups/testapp_', $svc->uploadedKeys[0]);
        $this->assertStringEndsWith('.sql.gz', $svc->uploadedKeys[0]);
    }

    #[Test]
    public function run_filename_uses_the_configured_prefix(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);

        Config::set('database_backup.filename_prefix', 'myapp');

        $this->makeTestableBackupService()->run();

        $this->assertStringContainsString('myapp_', DatabaseBackupLog::first()->filename);
    }

    #[Test]
    public function run_duration_seconds_is_recorded_in_the_log_row(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);

        $this->makeTestableBackupService()->run();

        $this->assertIsInt(DatabaseBackupLog::first()->duration_seconds);
        $this->assertGreaterThanOrEqual(0, DatabaseBackupLog::first()->duration_seconds);
    }

    #[Test]
    public function run_does_not_send_a_failure_email_on_success(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);

        $this->makeTestableBackupService()->run();

        Mail::assertNothingSent();
    }

    // =========================================================================
    // GROUP 3: BackupService — step failures
    // =========================================================================

    #[Test]
    public function run_returns_overall_status_failure_when_pg_dump_fails(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', 'connection refused', 1)]);

        $result = $this->makeTestableBackupService()->run();

        $this->assertSame('failure', $result['overall_status']);
    }

    #[Test]
    public function run_writes_a_failure_log_row_when_pg_dump_fails(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', 'connection refused', 1)]);

        $this->makeTestableBackupService()->run();

        $this->assertDatabaseHas('database_backup_logs', ['status' => 'failure']);
    }

    #[Test]
    public function run_marks_pg_dump_step_as_failure_in_the_result_steps(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', 'connection refused', 1)]);

        $result     = $this->makeTestableBackupService()->run();
        $failedStep = collect($result['steps'])->firstWhere('status', 'failure');

        $this->assertSame('pg_dump', $failedStep['label']);
    }

    #[Test]
    public function run_returns_failure_when_S3_upload_fails(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);

        $result     = $this->makeTestableBackupService(uploadShouldFail: true)->run();
        $failedStep = collect($result['steps'])->firstWhere('status', 'failure');

        $this->assertSame('failure', $result['overall_status']);
        $this->assertSame('Upload to S3', $failedStep['label']);
    }

    #[Test]
    public function run_skips_S3_pruning_when_overall_status_is_failure(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);

        $result = $this->makeTestableBackupService(uploadShouldFail: true, prunedCount: 99)->run();
        $labels = collect($result['steps'])->pluck('label')->toArray();

        $this->assertNotContains('Prune old S3 backups', $labels);
    }

    #[Test]
    public function run_continues_and_logs_a_non_fatal_warning_when_S3_pruning_fails(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);

        $result    = $this->makeTestableBackupService(pruneShouldFail: true)->run();
        $pruneStep = collect($result['steps'])->firstWhere('label', 'Prune old S3 backups');

        $this->assertSame('success', $result['overall_status']);
        $this->assertSame('failure', $pruneStep['status']);
    }

    // =========================================================================
    // GROUP 4: BackupService — pruning behaviour
    // =========================================================================

    #[Test]
    public function run_reports_pruned_file_count_in_the_prune_step_detail(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);

        $result    = $this->makeTestableBackupService(prunedCount: 3)->run();
        $pruneStep = collect($result['steps'])->firstWhere('label', 'Prune old S3 backups');

        $this->assertStringContainsString('3', $pruneStep['detail']);
    }

    #[Test]
    public function run_prunes_old_database_backup_log_rows_based_on_log_retention_days(): void
    {
        Mail::fake();
        Config::set('database_backup.log_retention_days', 30);

        $oldLog = $this->makeBackupLog([
            'ran_at'     => now()->subDays(31),
            'created_at' => now()->subDays(31),
        ]);
        $this->makeBackupLog([
            'ran_at'     => now()->subDays(29),
            'created_at' => now()->subDays(29),
        ]);

        Process::fake(['pg_dump*' => Process::result('', '', 0)]);

        $this->makeTestableBackupService()->run();

        $this->assertDatabaseMissing('database_backup_logs', ['id' => $oldLog->id]);
        $this->assertGreaterThanOrEqual(2, DatabaseBackupLog::count());
    }

    #[Test]
    public function run_skips_log_pruning_when_log_retention_days_is_0(): void
    {
        Mail::fake();
        Config::set('database_backup.log_retention_days', 0);

        $ancientLog = $this->makeBackupLog(['ran_at' => now()->subDays(365)]);

        Process::fake(['pg_dump*' => Process::result('', '', 0)]);

        $result    = $this->makeTestableBackupService()->run();
        $pruneStep = collect($result['steps'])->firstWhere('label', 'Prune old log rows');

        $this->assertSame('skipped', $pruneStep['status']);
        $this->assertDatabaseHas('database_backup_logs', ['id' => $ancientLog->id]);
    }

    // =========================================================================
    // GROUP 5: BackupService — failure email
    // =========================================================================

    #[Test]
    public function run_sends_BackupFailedMail_to_the_notification_email_on_failure(): void
    {
        Mail::fake();
        Config::set('database_backup.notification_email', 'admin@example.com');
        Process::fake(['pg_dump*' => Process::result('', 'pg_dump: connection refused', 1)]);

        $this->makeTestableBackupService()->run();

        Mail::assertSent(BackupFailedMail::class, fn ($m) => $m->hasTo('admin@example.com'));
    }

    #[Test]
    public function run_sends_exactly_one_failure_email_on_failure(): void
    {
        Mail::fake();
        Config::set('database_backup.notification_email', 'admin@example.com');
        Process::fake(['pg_dump*' => Process::result('', 'error', 1)]);

        $this->makeTestableBackupService()->run();

        Mail::assertSent(BackupFailedMail::class, 1);
    }

    #[Test]
    public function run_does_not_send_failure_email_when_notification_email_is_empty(): void
    {
        Mail::fake();
        Config::set('database_backup.notification_email', '');
        Process::fake(['pg_dump*' => Process::result('', 'error', 1)]);

        $this->makeTestableBackupService()->run();

        Mail::assertNothingSent();
    }

    // =========================================================================
    // GROUP 6: DatabaseBackupController — index
    // =========================================================================

    #[Test]
    public function index_renders_for_admin_user(): void
    {
        $this->actingAs($this->makeAdminUser());

        $this->get(route('admin.database-backups.index'))
             ->assertOk()
             ->assertViewIs('media_platform.tools.database_backup.index')
             ->assertViewHas('logs');
    }

    #[Test]
    public function index_returns_403_for_non_admin_user(): void
    {
        $this->actingAs($this->makeRegularUser());

        $this->get(route('admin.database-backups.index'))
             ->assertForbidden();
    }

    #[Test]
    public function index_returns_302_redirect_for_unauthenticated_user(): void
    {
        $this->get(route('admin.database-backups.index'))
             ->assertRedirect('/login');
    }

    #[Test]
    public function index_shows_log_rows_in_the_view(): void
    {
        $this->actingAs($this->makeAdminUser());

        $this->makeBackupLog(['status' => 'success', 'filename' => 'backups/newsrag_test_success.sql.gz']);
        $this->makeBackupLog(['status' => 'failure', 'filename' => 'backups/newsrag_test_failure.sql.gz']);

        $logs = $this->get(route('admin.database-backups.index'))->viewData('logs');

        $this->assertSame(2, $logs->total());
    }

    #[Test]
    public function index_shows_newest_log_rows_first(): void
    {
        $this->actingAs($this->makeAdminUser());

        $this->makeBackupLog(['ran_at' => now()->subDays(2), 'filename' => 'backups/older.sql.gz']);
        $this->makeBackupLog(['ran_at' => now()->subHour(),  'filename' => 'backups/newer.sql.gz']);

        $logs = $this->get(route('admin.database-backups.index'))->viewData('logs');

        $this->assertStringContainsString('newer', $logs->first()->filename);
    }

    #[Test]
    public function index_passes_null_backup_result_when_session_has_no_result(): void
    {
        $this->actingAs($this->makeAdminUser());

        $response = $this->get(route('admin.database-backups.index'));

        $this->assertNull($response->viewData('backupResult'));
    }

    // =========================================================================
    // GROUP 7: DatabaseBackupController — runNow
    // =========================================================================

    #[Test]
    public function runNow_redirects_to_index_after_completion(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);
        app()->instance(BackupService::class, $this->makeTestableBackupService());

        $this->actingAs($this->makeAdminUser());

        $this->post(route('admin.database-backups.run'))
             ->assertRedirect(route('admin.database-backups.index'));
    }

    #[Test]
    public function runNow_flashes_backup_result_to_the_session(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);
        app()->instance(BackupService::class, $this->makeTestableBackupService());

        $this->actingAs($this->makeAdminUser());

        $this->post(route('admin.database-backups.run'))
             ->assertSessionHas('backup_result');
    }

    #[Test]
    public function runNow_flashed_result_has_overall_status_and_steps_keys(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);
        app()->instance(BackupService::class, $this->makeTestableBackupService());

        $this->actingAs($this->makeAdminUser());

        $this->post(route('admin.database-backups.run'));
        $result = session('backup_result');

        $this->assertArrayHasKey('overall_status', $result);
        $this->assertArrayHasKey('steps', $result);
    }

    #[Test]
    public function runNow_returns_403_for_non_admin_user(): void
    {
        $this->actingAs($this->makeRegularUser());

        $this->post(route('admin.database-backups.run'))
             ->assertForbidden();
    }

    #[Test]
    public function runNow_writes_a_log_row_to_the_database(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);
        app()->instance(BackupService::class, $this->makeTestableBackupService());

        $this->actingAs($this->makeAdminUser());

        $this->post(route('admin.database-backups.run'));

        $this->assertSame(1, DatabaseBackupLog::count());
    }

    // =========================================================================
    // GROUP 8: BackupDatabaseCommand — Artisan command
    // =========================================================================

    #[Test]
    public function backup_database_command_exits_with_code_0_on_success(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);
        app()->instance(BackupService::class, $this->makeTestableBackupService());

        $this->artisan('backup:database')->assertExitCode(0);
    }

    #[Test]
    public function backup_database_command_exits_with_code_1_on_failure(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', 'connection refused', 1)]);
        app()->instance(BackupService::class, $this->makeTestableBackupService());

        $this->artisan('backup:database')->assertExitCode(1);
    }

    #[Test]
    public function backup_database_command_outputs_step_labels_to_console(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);
        app()->instance(BackupService::class, $this->makeTestableBackupService());

        $this->artisan('backup:database')
             ->expectsOutputToContain('pg_dump')
             ->expectsOutputToContain('Upload to S3')
             ->assertExitCode(0);
    }

    #[Test]
    public function backup_database_command_writes_a_log_row_to_the_database(): void
    {
        Mail::fake();
        Process::fake(['pg_dump*' => Process::result('', '', 0)]);
        app()->instance(BackupService::class, $this->makeTestableBackupService());

        $this->artisan('backup:database');

        $this->assertSame(1, DatabaseBackupLog::count());
    }
}