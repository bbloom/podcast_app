<?php

use MediaPlatform\Podcasts\Planning\CreateEpisodeWizard\Controllers\Step1Controller;
use MediaPlatform\Podcasts\Planning\CreateEpisodeWizard\Controllers\Step2Controller;
use MediaPlatform\Podcasts\Planning\CreateEpisodeWizard\Controllers\Step3Controller;
use MediaPlatform\Podcasts\Planning\CreateEpisodeWizard\Controllers\Step4Controller;

// -----------------------------------------------------------------------------
// Create Episode Wizard
//
// A 4-step wizard for creating a new podcast_episodes_planning record.
// There is no "create/store" on the CRUD controller — this wizard is the
// sole entry point for creating planning episodes.
//
// Session key: wizard.create_episode_planning.podcast_show_id
// -----------------------------------------------------------------------------

Route::get('/planning/create-episode/step1', [Step1Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.create.step1');

Route::get('/planning/create-episode/step2', [Step2Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.create.step2');

Route::post('/planning/create-episode/step2', [Step2Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.create.step2.store');

Route::get('/planning/create-episode/step3', [Step3Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.create.step3');

Route::post('/planning/create-episode/step3', [Step3Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.create.step3.store');

// Step 4 takes the newly created episode as a route model binding.
Route::get('/planning/create-episode/step4/{podcast_episode_planning}', [Step4Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.create.step4');