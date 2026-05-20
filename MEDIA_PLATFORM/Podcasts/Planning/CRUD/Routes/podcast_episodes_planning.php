<?php

use MediaPlatform\Podcasts\Planning\CRUD\Controllers\PodcastEpisodePlanningController;

// -----------------------------------------------------------------------------
// Planning Episodes — CRUD routes
//
// CREATE and STORE are intentionally absent — episode creation is handled
// exclusively by the Create Episode Wizard.
//
// All routes require authentication. Ownership (user_id) is enforced in the
// controller via authorizeOwnership().
// -----------------------------------------------------------------------------

Route::get('/planning-episodes', [PodcastEpisodePlanningController::class, 'index'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.index');

Route::get('/planning-episodes/{podcast_episode_planning}', [PodcastEpisodePlanningController::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.show');

Route::get('/planning-episodes/{podcast_episode_planning}/edit', [PodcastEpisodePlanningController::class, 'edit'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.edit');

Route::put('/planning-episodes/{podcast_episode_planning}', [PodcastEpisodePlanningController::class, 'update'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.update');

Route::get('/planning-episodes/{podcast_episode_planning}/delete', [PodcastEpisodePlanningController::class, 'deleteConfirm'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.delete.confirm');

Route::delete('/planning-episodes/{podcast_episode_planning}', [PodcastEpisodePlanningController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.destroy');