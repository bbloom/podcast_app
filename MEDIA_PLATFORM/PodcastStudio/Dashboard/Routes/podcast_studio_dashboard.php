<?php

// =============================================================================
// Route: Podcast Studio Dashboard
//
// Path: MEDIA_PLATFORM/PodcastStudio/Dashboard/Routes/
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\PodcastStudio\Dashboard\Controllers\PodcastStudioDashboardController;

Route::get('/podcast-studio', [PodcastStudioDashboardController::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_studio.dashboard');