<?php

use MediaPlatform\Podcasts\Links\Controllers\PodcastLinkController;

// -----------------------------------------------------------------------------
// Podcast Links routes
// Authentication required on all routes.
// Deletion is blocked in the controller if the link is attached to any episode.
// -----------------------------------------------------------------------------

// ── CRUD ─────────────────────────────────────────────────────────────────────

Route::get('/podcast-links', [PodcastLinkController::class, 'index'])
    ->middleware(['auth'])
    ->name('podcast_links.index');

Route::get('/podcast-links/create', [PodcastLinkController::class, 'create'])
    ->middleware(['auth'])
    ->name('podcast_links.create');

Route::post('/podcast-links', [PodcastLinkController::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_links.store');

Route::get('/podcast-links/{podcast_link}', [PodcastLinkController::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_links.show');

Route::get('/podcast-links/{podcast_link}/edit', [PodcastLinkController::class, 'edit'])
    ->middleware(['auth'])
    ->name('podcast_links.edit');

Route::put('/podcast-links/{podcast_link}', [PodcastLinkController::class, 'update'])
    ->middleware(['auth'])
    ->name('podcast_links.update');

Route::get('/podcast-links/{podcast_link}/delete', [PodcastLinkController::class, 'deleteConfirm'])
    ->middleware(['auth'])
    ->name('podcast_links.delete.confirm');

Route::delete('/podcast-links/{podcast_link}', [PodcastLinkController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('podcast_links.destroy');

// ── Attach / Detach ───────────────────────────────────────────────────────────

Route::get('/podcast-episodes/{podcast_episode}/links/attach', [PodcastLinkController::class, 'attachIndex'])
    ->middleware(['auth'])
    ->name('podcast_links.attach.index');

Route::post('/podcast-episodes/{podcast_episode}/links/{podcast_link}/attach', [PodcastLinkController::class, 'attach'])
    ->middleware(['auth'])
    ->name('podcast_links.attach');

Route::delete('/podcast-episodes/{podcast_episode}/links/{podcast_link}/detach', [PodcastLinkController::class, 'detach'])
    ->middleware(['auth'])
    ->name('podcast_links.detach');