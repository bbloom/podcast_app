<?php

// =============================================================================
// Route: Podcast Studio Dashboard
//
// Path: MEDIA_PLATFORM/Podcasts/Dashboard/Routes/
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\Podcasts\Dashboard\Controllers\PodcastsDashboardController;

Route::get('/podcasts', [PodcastsDashboardController::class, 'show'])
    ->middleware(['auth'])
    ->name('podcasts.dashboard');