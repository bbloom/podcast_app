<?php

use MediaPlatform\Tools\PhpServerlessProjectSponsors\Controllers\PhpServerlessProjectSponsorController;

// -----------------------------------------------------------------------------
// PHPServerlessProject Sponsors routes
// Standalone CRUD — no ownership checks, no admin gate.
// All routes require authentication.
// -----------------------------------------------------------------------------

Route::get('/phpserverlessproject-sponsors', [PhpServerlessProjectSponsorController::class, 'index'])
    ->middleware(['auth', 'can:admin'])
    ->name('phpserverlessproject_sponsors.index');

Route::get('/phpserverlessproject-sponsors/create', [PhpServerlessProjectSponsorController::class, 'create'])
    ->middleware(['auth', 'can:admin'])
    ->name('phpserverlessproject_sponsors.create');

Route::post('/phpserverlessproject-sponsors', [PhpServerlessProjectSponsorController::class, 'store'])
    ->middleware(['auth', 'can:admin'])
    ->name('phpserverlessproject_sponsors.store');

Route::get('/phpserverlessproject-sponsors/{phpserverlessproject_sponsor}', [PhpServerlessProjectSponsorController::class, 'show'])
    ->middleware(['auth', 'can:admin'])
    ->name('phpserverlessproject_sponsors.show');

Route::get('/phpserverlessproject-sponsors/{phpserverlessproject_sponsor}/edit', [PhpServerlessProjectSponsorController::class, 'edit'])
    ->middleware(['auth', 'can:admin'])
    ->name('phpserverlessproject_sponsors.edit');

Route::put('/phpserverlessproject-sponsors/{phpserverlessproject_sponsor}', [PhpServerlessProjectSponsorController::class, 'update'])
    ->middleware(['auth', 'can:admin'])
    ->name('phpserverlessproject_sponsors.update');

Route::get('/phpserverlessproject-sponsors/{phpserverlessproject_sponsor}/delete', [PhpServerlessProjectSponsorController::class, 'deleteConfirm'])
    ->middleware(['auth', 'can:admin'])
    ->name('phpserverlessproject_sponsors.delete.confirm');

Route::delete('/phpserverlessproject-sponsors/{phpserverlessproject_sponsor}', [PhpServerlessProjectSponsorController::class, 'destroy'])
    ->middleware(['auth', 'can:admin'])
    ->name('phpserverlessproject_sponsors.destroy');