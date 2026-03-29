<?php

use MediaPlatform\PodcastStudio\Management\Controllers\PodcastGuestController;

// -----------------------------------------------------------------------------
// Podcast Guests routes
// Authentication required on all routes.
// Deletion is blocked in the controller if the guest is attached to any episode.
// -----------------------------------------------------------------------------

// ── CRUD ─────────────────────────────────────────────────────────────────────

Route::get('/podcast-guests', [PodcastGuestController::class, 'index'])
    ->middleware(['auth'])
    ->name('podcast_guests.index');

Route::get('/podcast-guests/create', [PodcastGuestController::class, 'create'])
    ->middleware(['auth'])
    ->name('podcast_guests.create');

Route::post('/podcast-guests', [PodcastGuestController::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_guests.store');

Route::get('/podcast-guests/{podcast_guest}', [PodcastGuestController::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_guests.show');

Route::get('/podcast-guests/{podcast_guest}/edit', [PodcastGuestController::class, 'edit'])
    ->middleware(['auth'])
    ->name('podcast_guests.edit');

Route::put('/podcast-guests/{podcast_guest}', [PodcastGuestController::class, 'update'])
    ->middleware(['auth'])
    ->name('podcast_guests.update');

Route::get('/podcast-guests/{podcast_guest}/delete', [PodcastGuestController::class, 'deleteConfirm'])
    ->middleware(['auth'])
    ->name('podcast_guests.delete.confirm');

Route::delete('/podcast-guests/{podcast_guest}', [PodcastGuestController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('podcast_guests.destroy');

// ── Attach / Detach from guest show view ─────────────────────────────────────

Route::get('/podcast-guests/{podcast_guest}/episodes/attach', [PodcastGuestController::class, 'attachEpisodeIndex'])
    ->middleware(['auth'])
    ->name('podcast_guests.attach.episode.index');

Route::post('/podcast-guests/{podcast_guest}/episodes/{podcast_episode}/attach', [PodcastGuestController::class, 'attachEpisode'])
    ->middleware(['auth'])
    ->name('podcast_guests.attach.episode');

Route::delete('/podcast-guests/{podcast_guest}/episodes/{podcast_episode}/detach', [PodcastGuestController::class, 'detachEpisode'])
    ->middleware(['auth'])
    ->name('podcast_guests.detach.episode');

// ── Attach / Detach from episode show view ────────────────────────────────────

Route::get('/podcast-episodes/{podcast_episode}/guests/attach', [PodcastGuestController::class, 'attachGuestIndex'])
    ->middleware(['auth'])
    ->name('podcast_guests.attach.guest.index');

Route::post('/podcast-episodes/{podcast_episode}/guests/{podcast_guest}/attach', [PodcastGuestController::class, 'attachGuest'])
    ->middleware(['auth'])
    ->name('podcast_guests.attach.guest');

Route::delete('/podcast-episodes/{podcast_episode}/guests/{podcast_guest}/detach', [PodcastGuestController::class, 'detachGuest'])
    ->middleware(['auth'])
    ->name('podcast_guests.detach.guest');