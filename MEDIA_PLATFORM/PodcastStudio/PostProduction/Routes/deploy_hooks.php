<?php

// =============================================================================
// Deploy Hook Routes
//
// CRUD routes for managing deploy hooks.
// All routes require authentication. Ownership is enforced in the controller
// via abort_if().
//
// Loaded via require in routes/web.php.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\PodcastStudio\PostProduction\DeployHooks\Controllers\DeployHookController;

Route::get('/deploy-hooks', [DeployHookController::class, 'index'])
    ->middleware(['auth'])
    ->name('deploy_hooks.index');

Route::get('/deploy-hooks/create', [DeployHookController::class, 'create'])
    ->middleware(['auth'])
    ->name('deploy_hooks.create');

Route::post('/deploy-hooks', [DeployHookController::class, 'store'])
    ->middleware(['auth'])
    ->name('deploy_hooks.store');

Route::get('/deploy-hooks/{deploy_hook}', [DeployHookController::class, 'show'])
    ->middleware(['auth'])
    ->name('deploy_hooks.show');

Route::get('/deploy-hooks/{deploy_hook}/edit', [DeployHookController::class, 'edit'])
    ->middleware(['auth'])
    ->name('deploy_hooks.edit');

Route::put('/deploy-hooks/{deploy_hook}', [DeployHookController::class, 'update'])
    ->middleware(['auth'])
    ->name('deploy_hooks.update');

Route::get('/deploy-hooks/{deploy_hook}/delete', [DeployHookController::class, 'deleteConfirm'])
    ->middleware(['auth'])
    ->name('deploy_hooks.delete.confirm');

Route::delete('/deploy-hooks/{deploy_hook}', [DeployHookController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('deploy_hooks.destroy');