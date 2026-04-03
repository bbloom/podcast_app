<?php

use Illuminate\Support\Facades\Route;
use MediaPlatform\PodcastStudio\PostProduction\Dashboard\DashboardController;

// =============================================================================
// Post-Production — Dashboard
// =============================================================================

Route::get('/podcast-studio/post-production', [DashboardController::class, 'show'])
    ->middleware(['auth'])
    ->name('post_production.dashboard')
;