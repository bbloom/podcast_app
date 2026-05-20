<?php

use MediaPlatform\Podcasts\Planning\PrepareForPublishingWizard\Controllers\Step1Controller;
use MediaPlatform\Podcasts\Planning\PrepareForPublishingWizard\Controllers\Step2Controller;
use MediaPlatform\Podcasts\Planning\PrepareForPublishingWizard\Controllers\Step3Controller;

// -----------------------------------------------------------------------------
// Prepare For Publishing Wizard
//
// Entry point (Step 1) takes the episode as a route model binding.
// Episode must have status ready_for_publishing.
// Session key: wizard.prepare_for_publishing.episode_id
//
// On completion, the planning record is hard-deleted and the user is
// redirected to the new podcast_episodes_published show page.
// -----------------------------------------------------------------------------

Route::get('/planning/prepare-for-publishing/{podcast_episode_planning}/start', [Step1Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.publish.step1');

Route::get('/planning/prepare-for-publishing/step2', [Step2Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.publish.step2');

Route::post('/planning/prepare-for-publishing/step2', [Step2Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.publish.step2.store');

Route::get('/planning/prepare-for-publishing/step3', [Step3Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.publish.step3');

Route::post('/planning/prepare-for-publishing/step3', [Step3Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.publish.step3.store');