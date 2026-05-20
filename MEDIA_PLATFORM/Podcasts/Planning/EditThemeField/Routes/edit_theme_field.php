
<?php

use MediaPlatform\Podcasts\Planning\EditThemeField\Controllers\EditThemeFieldController;

// -----------------------------------------------------------------------------
// EditThemeField
//
// show()        — renders the theme editor page
// save()        — PATCH, returns JSON (Alpine.js "Save and Continue")
// saveAndExit() — PATCH, returns redirect ("Save and Exit")
// -----------------------------------------------------------------------------

Route::get('/planning-episodes/{podcast_episode_planning}/edit-theme', [EditThemeFieldController::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.theme.show');

Route::patch('/planning-episodes/{podcast_episode_planning}/edit-theme/save', [EditThemeFieldController::class, 'save'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.theme.save');

Route::patch('/planning-episodes/{podcast_episode_planning}/edit-theme/exit', [EditThemeFieldController::class, 'saveAndExit'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.theme.save_exit');