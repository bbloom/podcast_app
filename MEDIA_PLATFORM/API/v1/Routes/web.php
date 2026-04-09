<?php

// =============================================================================
// Routes: API Management
//
// Admin-only routes for managing the public API on/off switch and API clients.
// All routes are protected by the 'admin' gate.
//
// Loaded via routes/web.php.
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\API\v1\Dashboard\DashboardController;
use MediaPlatform\API\v1\Controllers\ApiControlController;
use MediaPlatform\API\v1\Controllers\ApiClientController;

// -----------------------------------------------------------------------------
// Dashboard
// -----------------------------------------------------------------------------
Route::get('/api-management', DashboardController::class)
    ->middleware(['auth'])
    ->name('api_management.dashboard')
;

// -----------------------------------------------------------------------------
// API Control — enable / disable
// -----------------------------------------------------------------------------
Route::post('/api-management/control/enable', [ApiControlController::class, 'enable'])
    ->middleware(['auth'])
    ->name('api_management.control.enable')
;

Route::post('/api-management/control/disable', [ApiControlController::class, 'disable'])
    ->middleware(['auth'])
    ->name('api_management.control.disable')
;

// -----------------------------------------------------------------------------
// API Clients — full CRUD + token rotation
// -----------------------------------------------------------------------------
Route::get('/api-management/clients', [ApiClientController::class, 'index'])
    ->middleware(['auth'])
    ->name('api_management.clients.index')
;

Route::get('/api-management/clients/create', [ApiClientController::class, 'create'])
    ->middleware(['auth'])
    ->name('api_management.clients.create')
;

Route::post('/api-management/clients', [ApiClientController::class, 'store'])
    ->middleware(['auth'])
    ->name('api_management.clients.store')
;

Route::get('/api-management/clients/{api_client}', [ApiClientController::class, 'show'])
    ->middleware(['auth'])
    ->name('api_management.clients.show')
;

Route::get('/api-management/clients/{api_client}/edit', [ApiClientController::class, 'edit'])
    ->middleware(['auth'])
    ->name('api_management.clients.edit')
;

Route::put('/api-management/clients/{api_client}', [ApiClientController::class, 'update'])
    ->middleware(['auth'])
    ->name('api_management.clients.update')
;

Route::get('/api-management/clients/{api_client}/delete-confirm', [ApiClientController::class, 'deleteConfirm'])
    ->middleware(['auth'])
    ->name('api_management.clients.delete.confirm')
;

Route::delete('/api-management/clients/{api_client}', [ApiClientController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('api_management.clients.destroy')
;

Route::post('/api-management/clients/{api_client}/rotate-token', [ApiClientController::class, 'rotateToken'])
    ->middleware(['auth'])
    ->name('api_management.clients.rotate_token')
;