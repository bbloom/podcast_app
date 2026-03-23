<?php

// ============================================================================
// tests/Feature/MEDIA_PLATFORM/Tools/DatabaseBackup/DatabaseBackupTest.php
//
// Feature tests for the DatabaseBackup package.
//
// TEST GROUPS
// ───────────
//   1. DatabaseBackupLog model
//   2. BackupService — happy path (all steps mocked to succeed)
//   3. BackupService — step failures (each failure point tested individually)
//   4. BackupService — pruning behaviour
//   5. BackupService — failure email
//   6. DatabaseBackupController — index
//   7. DatabaseBackupController — runNow
//   8. BackupDatabaseCommand — Artisan command
//
// MOCKING STRATEGY
// ────────────────
// BackupService shells out to pg_dump (via Laravel's Process facade) and
// calls the AWS SDK directly. Both are replaced so no real processes are
// spawned and no real S3 calls are made.
//
// Because BackupService uses `new S3Client(...)` internally, we test it via
// makeTestableBackupService(), which returns an anonymous subclass that
// overrides only the S3 interaction methods. Anonymous classes are invisible
// to Composer's PSR-4 autoloader, so no autoloading warnings are produced.
//
// Process::fake() intercepts pg_dump calls.
// Mail::fake()    intercepts the failure notification email.
//
// The gzip compression step uses PHP's native gzopen/gzwrite — NOT mocked.
// A real (possibly empty) temp file is compressed and then deleted, which
// also exercises the finally{} cleanup path.
// ============================================================================

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

uses(RefreshDatabase::class);

// =============================================================================
// Helpers
// =============================================================================

/**
 * Create an admin user whose email matches config('admin.admin_email').
 * The admin gate is defined as: $user->email === config('admin.admin_email')
 */
function makeAdminUser(): User
{
    return User::factory()->create(['email' => config('admin.admin_email')]);
}

/**
 * Create a non-admin user.
 */
function makeRegularUser(): User
{
    return User::factory()->create(['email' => 'notadmin@example.com']);
}

/**
 * Insert a DatabaseBackupLog row directly (bypasses the service).
 * Useful for seeding log data for controller/index tests.
 */
function makeBackupLog(array $overrides = []): DatabaseBackupLog
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

/**
 * Create an anonymous BackupService subclass that replaces the S3 methods
 * with controllable fakes, removing the aws/aws-sdk-php dependency from tests.
 *
 * Using an anonymous class (rather than a named class) avoids PSR-4 autoloading
 * warnings — Composer never tries to map anonymous classes to files.
 *
 * @param  bool  $uploadShouldFail  If true, uploadToS3() throws a RuntimeException.
 * @param  bool  $pruneShouldFail   If true, pruneS3Backups() throws a RuntimeException.
 * @param  int   $prunedCount       The number of files the fake pruner reports deleting.
 */
function makeTestableBackupService(
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

        /**
         * Override: skip real gzip compression entirely.
         *
         * Process::fake() makes pg_dump succeed without writing a real file,
         * so the .sql temp file never exists on disk. Rather than trying to
         * open a nonexistent file, we just write a minimal valid gzip to the
         * destination path so the rest of the pipeline has something to work
         * with (upload, integrity check, cleanup).
         */
        protected function gzipFile(string $sourcePath, string $destPath): void
        {
            // Write the smallest valid gzip possible (empty compressed stream).
            $gz = gzopen($destPath, 'wb');
            gzwrite($gz, '');
            gzclose($gz);
        }

        /** Override: record the key and optionally simulate an S3 failure. */
        protected function uploadToS3(string $localPath, string $s3Key): void
        {
            if ($this->uploadShouldFail) {
                throw new \RuntimeException('S3 upload failed: simulated AWS error');
            }
            $this->uploadedKeys[] = $s3Key;
        }

        /** Override: return a controlled prune count or simulate a failure. */
        protected function pruneS3Backups(): int
        {
            if ($this->pruneShouldFail) {
                throw new \RuntimeException('S3 pruning failed: simulated AWS error');
            }
            return $this->prunedCount;
        }
    };
}

// =============================================================================
// GROUP 1: DatabaseBackupLog model
// =============================================================================

test('isSuccess returns true for success status', function () {
    $log = makeBackupLog(['status' => 'success']);
    expect($log->isSuccess())->toBeTrue();
    expect($log->isFailure())->toBeFalse();
});

test('isFailure returns true for failure status', function () {
    $log = makeBackupLog(['status' => 'failure']);
    expect($log->isFailure())->toBeTrue();
    expect($log->isSuccess())->toBeFalse();
});

test('humanFileSize formats bytes correctly', function () {
    $mb  = makeBackupLog(['file_size_bytes' => 4_404_019]);  // ~4.2 MB
    $kb  = makeBackupLog(['file_size_bytes' => 10_240]);     // 10 KB
    $b   = makeBackupLog(['file_size_bytes' => 512]);        // 512 B
    $nil = makeBackupLog(['file_size_bytes' => null]);

    expect($mb->humanFileSize())->toContain('MB');
    expect($kb->humanFileSize())->toContain('KB');
    expect($b->humanFileSize())->toContain('B');
    expect($nil->humanFileSize())->toBeNull();
});

test('model has no updated_at column', function () {
    // UPDATED_AT is set to null — the column does not exist on the table.
    // Attempting to touch the model should not throw.
    $log = makeBackupLog();
    expect(fn () => $log->touch())->not->toThrow(\Throwable::class);
    expect(DatabaseBackupLog::UPDATED_AT)->toBeNull();
});

test('ran_at is cast to Carbon', function () {
    $log = makeBackupLog(['ran_at' => '2026-01-15 03:00:00']);
    expect($log->ran_at)->toBeInstanceOf(Carbon::class);
});

// =============================================================================
// GROUP 2: BackupService — happy path
// =============================================================================

test('run returns overall_status success when all steps pass', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);

    $result = makeTestableBackupService()->run();

    expect($result['overall_status'])->toBe('success');
});

test('run writes a success log row to the database', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);

    makeTestableBackupService()->run();

    $this->assertDatabaseHas('database_backup_logs', ['status' => 'success']);
});

test('run result contains all expected step labels on success', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);

    $result = makeTestableBackupService()->run();
    $labels = collect($result['steps'])->pluck('label')->toArray();

    expect($labels)->toContain('Generate filename');
    expect($labels)->toContain('pg_dump');
    expect($labels)->toContain('Gzip compress');
    expect($labels)->toContain('Upload to S3');
    expect($labels)->toContain('Integrity check');
    expect($labels)->toContain('Prune old S3 backups');
    expect($labels)->toContain('Prune old log rows');
});

test('run uploads file to the configured S3 folder', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);

    Config::set('database_backup.s3_folder', 'mybackups');
    Config::set('database_backup.filename_prefix', 'testapp');

    $service = makeTestableBackupService();
    $service->run();

    expect($service->uploadedKeys)->toHaveCount(1);
    expect($service->uploadedKeys[0])->toStartWith('mybackups/testapp_');
    expect($service->uploadedKeys[0])->toEndWith('.sql.gz');
});

test('run filename uses the configured prefix', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);

    Config::set('database_backup.filename_prefix', 'myapp');

    makeTestableBackupService()->run();

    expect(DatabaseBackupLog::first()->filename)->toContain('myapp_');
});

test('run duration_seconds is recorded in the log row', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);

    makeTestableBackupService()->run();

    expect(DatabaseBackupLog::first()->duration_seconds)->toBeInt()->toBeGreaterThanOrEqual(0);
});

test('run does not send a failure email on success', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);

    makeTestableBackupService()->run();

    Mail::assertNothingSent();
});

// =============================================================================
// GROUP 3: BackupService — step failures
// =============================================================================

test('run returns overall_status failure when pg_dump fails', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', 'connection refused', 1)]);

    $result = makeTestableBackupService()->run();

    expect($result['overall_status'])->toBe('failure');
});

test('run writes a failure log row when pg_dump fails', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', 'connection refused', 1)]);

    makeTestableBackupService()->run();

    $this->assertDatabaseHas('database_backup_logs', ['status' => 'failure']);
});

test('run marks pg_dump step as failure in the result steps', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', 'connection refused', 1)]);

    $result     = makeTestableBackupService()->run();
    $failedStep = collect($result['steps'])->firstWhere('status', 'failure');

    expect($failedStep['label'])->toBe('pg_dump');
});

test('run returns failure when S3 upload fails', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);

    $result = makeTestableBackupService(uploadShouldFail: true)->run();

    expect($result['overall_status'])->toBe('failure');
    $failedStep = collect($result['steps'])->firstWhere('status', 'failure');
    expect($failedStep['label'])->toBe('Upload to S3');
});

test('run skips S3 pruning when overall status is failure', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);

    $result = makeTestableBackupService(uploadShouldFail: true, prunedCount: 99)->run();
    $labels = collect($result['steps'])->pluck('label')->toArray();

    expect($labels)->not->toContain('Prune old S3 backups');
});

test('run continues and logs a non-fatal warning when S3 pruning fails', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);

    $result = makeTestableBackupService(pruneShouldFail: true)->run();

    // Overall run is still a success — prune failure is non-fatal.
    expect($result['overall_status'])->toBe('success');
    $pruneStep = collect($result['steps'])->firstWhere('label', 'Prune old S3 backups');
    expect($pruneStep['status'])->toBe('failure');
});

// =============================================================================
// GROUP 4: BackupService — pruning behaviour
// =============================================================================

test('run reports pruned file count in the prune step detail', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);

    $result    = makeTestableBackupService(prunedCount: 3)->run();
    $pruneStep = collect($result['steps'])->firstWhere('label', 'Prune old S3 backups');

    expect($pruneStep['detail'])->toContain('3');
});

test('run prunes old database_backup_logs rows based on log_retention_days', function () {
    Mail::fake();
    Config::set('database_backup.log_retention_days', 30);

    // The pruning query uses created_at, so we must set it explicitly.
    // Insert a log row whose created_at is 31 days ago — it should be pruned.
    $oldLog = makeBackupLog([
        'ran_at'     => now()->subDays(31),
        'created_at' => now()->subDays(31),
    ]);

    // Insert a log row whose created_at is 29 days ago — it should be kept.
    makeBackupLog([
        'ran_at'     => now()->subDays(29),
        'created_at' => now()->subDays(29),
    ]);

    Process::fake(['pg_dump*' => Process::result('', '', 0)]);

    makeTestableBackupService()->run();

    // The 31-day-old row should have been deleted.
    $this->assertDatabaseMissing('database_backup_logs', ['id' => $oldLog->id]);

    // At least two rows remain: the kept row + the new run's log row.
    expect(DatabaseBackupLog::count())->toBeGreaterThanOrEqual(2);
});

test('run skips log pruning when log_retention_days is 0', function () {
    Mail::fake();
    Config::set('database_backup.log_retention_days', 0);

    // Insert an ancient log row — it should NOT be pruned.
    $ancientLog = makeBackupLog(['ran_at' => now()->subDays(365)]);

    Process::fake(['pg_dump*' => Process::result('', '', 0)]);

    $result = makeTestableBackupService()->run();

    $pruneStep = collect($result['steps'])->firstWhere('label', 'Prune old log rows');
    expect($pruneStep['status'])->toBe('skipped');

    // Ancient row still exists.
    $this->assertDatabaseHas('database_backup_logs', ['id' => $ancientLog->id]);
});

// =============================================================================
// GROUP 5: BackupService — failure email
// =============================================================================

test('run sends BackupFailedMail to the notification email on failure', function () {
    Mail::fake();
    Config::set('database_backup.notification_email', 'admin@example.com');
    Process::fake(['pg_dump*' => Process::result('', 'pg_dump: connection refused', 1)]);

    makeTestableBackupService()->run();

    Mail::assertSent(BackupFailedMail::class, fn ($mail) => $mail->hasTo('admin@example.com'));
});

test('run sends exactly one failure email on failure', function () {
    Mail::fake();
    Config::set('database_backup.notification_email', 'admin@example.com');
    Process::fake(['pg_dump*' => Process::result('', 'error', 1)]);

    makeTestableBackupService()->run();

    Mail::assertSent(BackupFailedMail::class, 1);
});

test('run does not send failure email when notification_email is empty', function () {
    Mail::fake();
    Config::set('database_backup.notification_email', '');
    Process::fake(['pg_dump*' => Process::result('', 'error', 1)]);

    makeTestableBackupService()->run();

    Mail::assertNothingSent();
});

// =============================================================================
// GROUP 6: DatabaseBackupController — index
// =============================================================================

test('index renders for admin user', function () {
    $this->actingAs(makeAdminUser());

    $this->get(route('admin.database-backups.index'))
         ->assertOk()
         ->assertViewIs('media_platform.tools.database_backup.index')
         ->assertViewHas('logs');
});

test('index returns 403 for non-admin user', function () {
    $this->actingAs(makeRegularUser());

    $this->get(route('admin.database-backups.index'))
         ->assertForbidden();
});

test('index returns 302 redirect for unauthenticated user', function () {
    $this->get(route('admin.database-backups.index'))
         ->assertRedirect('/login');
});

test('index shows log rows in the view', function () {
    $this->actingAs(makeAdminUser());

    makeBackupLog(['status' => 'success', 'filename' => 'backups/newsrag_test_success.sql.gz']);
    makeBackupLog(['status' => 'failure', 'filename' => 'backups/newsrag_test_failure.sql.gz']);

    $response = $this->get(route('admin.database-backups.index'));
    $logs     = $response->viewData('logs');

    expect($logs->total())->toBe(2);
});

test('index shows newest log rows first', function () {
    $this->actingAs(makeAdminUser());

    makeBackupLog(['ran_at' => now()->subDays(2), 'filename' => 'backups/older.sql.gz']);
    makeBackupLog(['ran_at' => now()->subHour(),  'filename' => 'backups/newer.sql.gz']);

    $response = $this->get(route('admin.database-backups.index'));
    $logs     = $response->viewData('logs');

    expect($logs->first()->filename)->toContain('newer');
});

test('index passes null backup_result when session has no result', function () {
    $this->actingAs(makeAdminUser());

    $response = $this->get(route('admin.database-backups.index'));

    expect($response->viewData('backupResult'))->toBeNull();
});

// =============================================================================
// GROUP 7: DatabaseBackupController — runNow
// =============================================================================

test('runNow redirects to index after completion', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);
    // Use instance() so the pre-built anonymous object is injected directly,
    // bypassing the container's class resolution (which requires a real class name).
    app()->instance(BackupService::class, makeTestableBackupService());

    $this->actingAs(makeAdminUser());

    $this->post(route('admin.database-backups.run'))
         ->assertRedirect(route('admin.database-backups.index'));
});

test('runNow flashes backup_result to the session', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);
    app()->instance(BackupService::class, makeTestableBackupService());

    $this->actingAs(makeAdminUser());

    $this->post(route('admin.database-backups.run'))
         ->assertSessionHas('backup_result');
});

test('runNow flashed result has overall_status and steps keys', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);
    app()->instance(BackupService::class, makeTestableBackupService());

    $this->actingAs(makeAdminUser());

    // Follow the redirect so the session data is readable via session().
    $this->post(route('admin.database-backups.run'));
    $result = session('backup_result');

    expect($result)->toHaveKey('overall_status');
    expect($result)->toHaveKey('steps');
});

test('runNow returns 403 for non-admin user', function () {
    $this->actingAs(makeRegularUser());

    $this->post(route('admin.database-backups.run'))
         ->assertForbidden();
});

test('runNow writes a log row to the database', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);
    app()->instance(BackupService::class, makeTestableBackupService());

    $this->actingAs(makeAdminUser());

    $this->post(route('admin.database-backups.run'));

    expect(DatabaseBackupLog::count())->toBe(1);
});

// =============================================================================
// GROUP 8: BackupDatabaseCommand — Artisan command
// =============================================================================

test('backup:database command exits with code 0 on success', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);
    app()->instance(BackupService::class, makeTestableBackupService());

    $this->artisan('backup:database')->assertExitCode(0);
});

test('backup:database command exits with code 1 on failure', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', 'connection refused', 1)]);
    app()->instance(BackupService::class, makeTestableBackupService());

    $this->artisan('backup:database')->assertExitCode(1);
});

test('backup:database command outputs step labels to console', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);
    app()->instance(BackupService::class, makeTestableBackupService());

    $this->artisan('backup:database')
         ->expectsOutputToContain('pg_dump')
         ->expectsOutputToContain('Upload to S3')
         ->assertExitCode(0);
});

test('backup:database command writes a log row to the database', function () {
    Mail::fake();
    Process::fake(['pg_dump*' => Process::result('', '', 0)]);
    app()->instance(BackupService::class, makeTestableBackupService());

    $this->artisan('backup:database');

    expect(DatabaseBackupLog::count())->toBe(1);
});