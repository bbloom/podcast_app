<?php

use MediaPlatform\PodcastStudio\Management\Controllers\PodcastEpisodeStatusLookupController;

// -----------------------------------------------------------------------------
// Podcast Episode Status Lookup routes
// Admin-only resource. @can('admin') guards are applied in views; hard 403s
// are applied in the controller and form request authorize() method.
// -----------------------------------------------------------------------------

Route::get('/podcast-episode-status-lookup', [PodcastEpisodeStatusLookupController::class, 'index'])
    ->middleware(['auth'])
    ->name('podcast_episode_status_lookup.index');

Route::get('/podcast-episode-status-lookup/create', [PodcastEpisodeStatusLookupController::class, 'create'])
    ->middleware(['auth'])
    ->name('podcast_episode_status_lookup.create');

Route::post('/podcast-episode-status-lookup', [PodcastEpisodeStatusLookupController::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episode_status_lookup.store');

Route::get('/podcast-episode-status-lookup/{podcast_episode_status_lookup}', [PodcastEpisodeStatusLookupController::class, 'show'])
->middleware(['auth'])
->name('podcast_episode_status_lookup.show');

Route::get('/podcast-episode-status-lookup/{podcast_episode_status_lookup}/edit', [PodcastEpisodeStatusLookupController::class, 'edit'])
    ->middleware(['auth'])
    ->name('podcast_episode_status_lookup.edit');

Route::put('/podcast-episode-status-lookup/{podcast_episode_status_lookup}', [PodcastEpisodeStatusLookupController::class, 'update'])
    ->middleware(['auth'])
    ->name('podcast_episode_status_lookup.update');

Route::get('/podcast-episode-status-lookup/{podcast_episode_status_lookup}/delete', [PodcastEpisodeStatusLookupController::class, 'deleteConfirm'])
    ->middleware(['auth'])
    ->name('podcast_episode_status_lookup.delete.confirm');

Route::delete('/podcast-episode-status-lookup/{podcast_episode_status_lookup}', [PodcastEpisodeStatusLookupController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('podcast_episode_status_lookup.destroy');