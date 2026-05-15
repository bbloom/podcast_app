<?php

// =============================================================================
// Routes: Draft Pre-Production Wizard
//
// Walks an existing draft through finalization: title, episode number, date,
// draft/script, and website content. Sets status to pre_production_complete.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/PreProduction/Routes/
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\PreProduction\Controllers\Step1Controller;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\PreProduction\Controllers\Step2Controller;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\PreProduction\Controllers\Step3Controller;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\PreProduction\Controllers\Step4Controller;

// Step 1 — Select show and draft
Route::get('/draft-pre-production', [Step1Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('draft_pre_production.step1');

Route::post('/draft-pre-production/step1', [Step1Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('draft_pre_production.step1.store');

// Step 2 — Finalize title, episode number, date
Route::get('/draft-pre-production/step2', [Step2Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('draft_pre_production.step2');

Route::post('/draft-pre-production/step2', [Step2Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('draft_pre_production.step2.store');

// Step 3 — Finalize draft/script
Route::get('/draft-pre-production/step3', [Step3Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('draft_pre_production.step3');

Route::post('/draft-pre-production/step3', [Step3Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('draft_pre_production.step3.store');

// Step 4 — Finalize website content → mark pre-production complete
Route::get('/draft-pre-production/step4', [Step4Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('draft_pre_production.step4');

Route::post('/draft-pre-production/step4', [Step4Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('draft_pre_production.step4.store');