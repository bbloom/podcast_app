<?php 

use MediaPlatform\Podcasts\Planning\EditScriptField\Controllers\EditScriptFieldController;

// -----------------------------------------------------------------------------
// EditScriptField
//
// show()        — renders the script editor page
// save()        — PATCH, returns JSON (Alpine.js "Save and Continue")
// saveAndExit() — PATCH, returns redirect ("Save and Exit")
// -----------------------------------------------------------------------------

Route::get('/planning-episodes/{podcast_episode_planning}/edit-script', [EditScriptFieldController::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.script.show');

Route::patch('/planning-episodes/{podcast_episode_planning}/edit-script/save', [EditScriptFieldController::class, 'save'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.script.save');

Route::patch('/planning-episodes/{podcast_episode_planning}/edit-script/exit', [EditScriptFieldController::class, 'saveAndExit'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.script.save_exit');