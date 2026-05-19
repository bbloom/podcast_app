<?php

use MediaPlatform\Podcasts\Publishing\Controllers\PodcastEpisodeController;
use MediaPlatform\Podcasts\Publishing\Controllers\PodcastEpisodeUpdateController;

// -----------------------------------------------------------------------------
// Podcast Episodes routes
// All routes require authentication. Ownership (user_id) is enforced in the
// controllers via authorizeOwnership() or inline checks.
//
// CRUD is split across two controllers:
//   - PodcastEpisodeController       → index, show, deleteConfirm, destroy
//   - PodcastEpisodeUpdateController  → edit, update
//
// CREATE and STORE are handled by the Create Podcast Episode wizard.
// -----------------------------------------------------------------------------

Route::get('/podcast-episodes', [PodcastEpisodeController::class, 'index'])
    ->middleware(['auth'])
    ->name('podcast_episodes.index');


    // The CREATE and STORE routes are handled by the Create Podcast Episode wizard


Route::get('/podcast-episodes/{podcast_episode}', [PodcastEpisodeController::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes.show');

Route::get('/podcast-episodes/{podcast_episode}/edit', [PodcastEpisodeUpdateController::class, 'edit'])
    ->middleware(['auth'])
    ->name('podcast_episodes.edit');

Route::put('/podcast-episodes/{podcast_episode}', [PodcastEpisodeUpdateController::class, 'update'])
    ->middleware(['auth'])
    ->name('podcast_episodes.update');

Route::get('/podcast-episodes/{podcast_episode}/delete', [PodcastEpisodeController::class, 'deleteConfirm'])
    ->middleware(['auth'])
    ->name('podcast_episodes.delete.confirm');

Route::delete('/podcast-episodes/{podcast_episode}', [PodcastEpisodeController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('podcast_episodes.destroy');