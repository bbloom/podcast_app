<?php

use MediaPlatform\Videos\Controllers\CreateVideoStep1Controller;
use MediaPlatform\Videos\Controllers\CreateVideoStep2Controller;
use MediaPlatform\Videos\Controllers\VideoController;

// -----------------------------------------------------------------------------
// Video routes
// All routes require authentication. Ownership (user_id) is enforced in the
// controller via graceful redirects.
// -----------------------------------------------------------------------------

// ── Create Video Wizard ──────────────────────────────────────────────────────

Route::get('/videos/create', [CreateVideoStep1Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('videos.create.step1');

Route::post('/videos/create/step1', [CreateVideoStep1Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('videos.create.step1.store');

Route::get('/videos/create/step2', [CreateVideoStep2Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('videos.create.step2');

// ── CRUD ─────────────────────────────────────────────────────────────────────

Route::get('/videos', [VideoController::class, 'index'])
    ->middleware(['auth'])
    ->name('videos.index');

Route::get('/videos/{video}', [VideoController::class, 'show'])
    ->middleware(['auth'])
    ->name('videos.show');

Route::get('/videos/{video}/edit', [VideoController::class, 'edit'])
    ->middleware(['auth'])
    ->name('videos.edit');

Route::put('/videos/{video}', [VideoController::class, 'update'])
    ->middleware(['auth'])
    ->name('videos.update');

Route::get('/videos/{video}/delete', [VideoController::class, 'deleteConfirm'])
    ->middleware(['auth'])
    ->name('videos.delete.confirm');

Route::delete('/videos/{video}', [VideoController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('videos.destroy');