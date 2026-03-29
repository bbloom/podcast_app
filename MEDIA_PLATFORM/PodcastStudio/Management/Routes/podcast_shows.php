<?php

use MediaPlatform\PodcastStudio\Management\Controllers\PodcastShowController;

// -----------------------------------------------------------------------------
// Podcast Shows routes
// All routes require authentication. Ownership (user_id) is enforced in the
// controller via abort_if().
// -----------------------------------------------------------------------------

Route::get('/podcast-shows', [PodcastShowController::class, 'index'])
    ->middleware(['auth'])
    ->name('podcast_shows.index');

Route::get('/podcast-shows/create', [PodcastShowController::class, 'create'])
    ->middleware(['auth'])
    ->name('podcast_shows.create');

Route::post('/podcast-shows', [PodcastShowController::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_shows.store');

Route::get('/podcast-shows/{podcast_show}', [PodcastShowController::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_shows.show');

Route::get('/podcast-shows/{podcast_show}/edit', [PodcastShowController::class, 'edit'])
    ->middleware(['auth'])
    ->name('podcast_shows.edit');

Route::put('/podcast-shows/{podcast_show}', [PodcastShowController::class, 'update'])
    ->middleware(['auth'])
    ->name('podcast_shows.update');

Route::get('/podcast-shows/{podcast_show}/delete', [PodcastShowController::class, 'deleteConfirm'])
    ->middleware(['auth'])
    ->name('podcast_shows.delete.confirm');

Route::delete('/podcast-shows/{podcast_show}', [PodcastShowController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('podcast_shows.destroy');