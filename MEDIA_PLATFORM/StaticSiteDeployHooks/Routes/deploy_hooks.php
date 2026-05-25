<?php

// =============================================================================
// Deploy Hook Routes
//
// CRUD routes for managing static site deploy hooks.
// All routes require authentication. Ownership is enforced in the controller
// via resolveAndAuthorizeTriggerable().
//
// Loaded via require in routes/web.php.
//
// Path: MEDIA_PLATFORM/StaticSiteDeployHooks/Routes/
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\StaticSiteDeployHooks\Controllers\DeployHookController;
use MediaPlatform\StaticSiteDeployHooks\Controllers\BuildStatusController;


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

Route::get('/deploy-hooks/{deploy_hook}/trigger', [DeployHookController::class, 'confirmTrigger'])
    ->middleware(['auth'])
    ->name('deploy_hooks.trigger.confirm');

Route::post('/deploy-hooks/{deploy_hook}/trigger', [DeployHookController::class, 'executeTrigger'])
    ->middleware(['auth'])
    ->name('deploy_hooks.trigger.execute');

Route::get('/deploy-hooks/{deploy_hook}/trigger/result', [DeployHookController::class, 'triggerResult'])
    ->middleware(['auth'])
    ->name('deploy_hooks.trigger.result');


    
// ── Build Status (Cloudflare Pages) ──────────────────────────────────────────
//
// JSON endpoint polled by Alpine.js on the deploy hook show page and the
// BuildConfirmation pipeline step. Returns the current deployment status
// for the hook's last triggered build.

Route::get('/deploy-hooks/{deploy_hook}/build-status', BuildStatusController::class)
    ->middleware(['auth'])
    ->name('deploy_hooks.build_status');    