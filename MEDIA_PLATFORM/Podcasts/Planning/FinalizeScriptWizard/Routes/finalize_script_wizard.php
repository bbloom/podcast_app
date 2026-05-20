<?php

use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step1Controller;
use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step2Controller;
use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step3Controller;
use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step4Controller;
use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step5Controller;
use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step6Controller;
use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step7Controller;

// -----------------------------------------------------------------------------
// Finalize Script Wizard
//
// Entry point (Step 1) takes the episode as a route model binding.
// Episode must have status ready_to_finalize_the_script.
// Session key: wizard.finalize_script.episode_id
// On completion, status is set to ready_to_record.
// -----------------------------------------------------------------------------

// Step 1 — entry point, takes episode as route model binding.
Route::get('/planning/finalize-script/{podcast_episode_planning}/start', [Step1Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step1');

Route::get('/planning/finalize-script/step2', [Step2Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step2');
Route::post('/planning/finalize-script/step2', [Step2Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step2.store');

Route::get('/planning/finalize-script/step3', [Step3Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step3');
Route::post('/planning/finalize-script/step3', [Step3Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step3.store');

// Step 4 — AI proofing. No store. Continue is a plain link to Step 5.
Route::get('/planning/finalize-script/step4', [Step4Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step4');

Route::get('/planning/finalize-script/step5', [Step5Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step5');
Route::post('/planning/finalize-script/step5', [Step5Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step5.store');

Route::get('/planning/finalize-script/step6', [Step6Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step6');
Route::post('/planning/finalize-script/step6', [Step6Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step6.store');

Route::get('/planning/finalize-script/step7', [Step7Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step7');
Route::post('/planning/finalize-script/step7', [Step7Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step7.store');