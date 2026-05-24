<?php

use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step1Controller;
use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step2Controller;
use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step3Controller;
use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step4Controller;
use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step5Controller;
use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step6Controller;
use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step7Controller;
use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step8Controller;
use MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers\Step9Controller;

// -----------------------------------------------------------------------------
// Finalize Script Wizard (9 steps)
//
// Entry point (Step 1) takes the episode as a route model binding.
// Episode must have status ready_to_finalize_the_script.
// Session key: wizard.finalize_script.episode_id
// On completion (Step 9 store), status is set to ready_to_record
// and script_scratch is cleared.
//
// Step 1  — Introduction
// Step 2  — Confirm episode number
// Step 3  — Confirm episode title
// Step 4  — AI proofing (dual textarea: script + scratch pad)
// Step 5  — Intro template review/create (updates podcast_show)
// Step 6  — Prepend resolved intro to script
// Step 7  — Outro template review/create (updates podcast_show)
// Step 8  — Append resolved outro to script
// Step 9  — Final confirmation → ready_to_record
// -----------------------------------------------------------------------------

// Step 1 — entry point, route model binding.
Route::get('/planning/finalize-script/{podcast_episode_planning}/start', [Step1Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step1');

// Step 2 — confirm episode number.
Route::get('/planning/finalize-script/step2', [Step2Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step2');
Route::post('/planning/finalize-script/step2', [Step2Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step2.store');

// Step 3 — confirm episode title.
Route::get('/planning/finalize-script/step3', [Step3Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step3');
Route::post('/planning/finalize-script/step3', [Step3Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step3.store');

// Step 4 — AI proofing (dual textarea).
// saveScratch is a separate PATCH endpoint — called by Alpine.js fetch().
// The canonical script is saved via the existing podcast_episodes_planning.script.save route.
Route::get('/planning/finalize-script/step4', [Step4Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step4');
Route::patch('/planning/finalize-script/step4/save-scratch', [Step4Controller::class, 'saveScratch'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step4.save_scratch');

// Step 5 — intro template review / create.
Route::get('/planning/finalize-script/step5', [Step5Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step5');
Route::post('/planning/finalize-script/step5', [Step5Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step5.store');

// Step 6 — prepend resolved intro to script.
Route::get('/planning/finalize-script/step6', [Step6Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step6');
Route::post('/planning/finalize-script/step6', [Step6Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step6.store');

// Step 7 — outro template review / create.
Route::get('/planning/finalize-script/step7', [Step7Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step7');
Route::post('/planning/finalize-script/step7', [Step7Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step7.store');

// Step 8 — append resolved outro to script.
Route::get('/planning/finalize-script/step8', [Step8Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step8');
Route::post('/planning/finalize-script/step8', [Step8Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step8.store');

// Step 9 — final confirmation → sets ready_to_record, clears script_scratch.
Route::get('/planning/finalize-script/step9', [Step9Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step9');
Route::post('/planning/finalize-script/step9', [Step9Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.wizard.finalize.step9.store');