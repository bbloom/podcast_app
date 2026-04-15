<?php

/**
 * Add these routes to your routes/web.php file.
 * Protected by auth + admin gate middleware.
 */

use MediaPlatform\Tools\HealthChecks\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:admin'])->prefix('admin')->group(function () { 
    Route::get('/health-checks', [HealthCheckController::class, 'index'])
        ->name('admin.health-checks.index');

    Route::post('/health-checks/{alert}/resolve', [HealthCheckController::class, 'resolve'])
        ->name('admin.health-checks.resolve');

    Route::get('/health-checks/failed-jobs/flush', [HealthCheckController::class, 'flushFailedJobsConfirm'])
        ->name('admin.health-checks.failed-jobs.flush.confirm');

    Route::post('/health-checks/failed-jobs/flush', [HealthCheckController::class, 'flushFailedJobs'])
        ->name('admin.health-checks.failed-jobs.flush');

    Route::get('/health-checks/readme', [HealthCheckController::class, 'readme'])
        ->name('admin.health-checks.readme');    
});
