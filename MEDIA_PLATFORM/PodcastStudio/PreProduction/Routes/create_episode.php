<?php

use MediaPlatform\PodcastStudio\PreProduction\CreateEpisode\Controllers\Step1Controller;
use MediaPlatform\PodcastStudio\PreProduction\CreateEpisode\Controllers\Step2Controller;
use MediaPlatform\PodcastStudio\PreProduction\CreateEpisode\Controllers\Step3Controller;

// -----------------------------------------------------------------------------
// Create Episode Wizard routes
// All routes require authentication.
// -----------------------------------------------------------------------------

// Step 1 — Select a podcast show
Route::get('/pre-production/create-podcast-episode', [Step1Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('pre_production_create_podcast_episode.step1');

Route::post('/pre-production/create-podcast-episode/step1', [Step1Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('pre_production_create_podcast_episode.step1.store');

// Step 2 — Episode details
Route::get('/pre-production/create-podcast-episode/step2', [Step2Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('pre_production_create_podcast_episode.step2');

Route::post('/pre-production/create-podcast-episode/step2', [Step3Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('pre_production_create_podcast_episode.step2.store');