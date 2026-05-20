<?php

use MediaPlatform\Podcasts\Planning\CRUD\Controllers\PodcastEpisodePlanningGuestController;

// -----------------------------------------------------------------------------
// Planning Episode — Guest Attach / Detach
// -----------------------------------------------------------------------------

Route::get('/planning-episodes/{podcast_episode_planning}/attach-guest', [PodcastEpisodePlanningGuestController::class, 'attachIndex'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.guests.attach.index');

Route::post('/planning-episodes/{podcast_episode_planning}/attach-guest/{podcast_guest}', [PodcastEpisodePlanningGuestController::class, 'attach'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.guests.attach');

Route::delete('/planning-episodes/{podcast_episode_planning}/detach-guest/{podcast_guest}', [PodcastEpisodePlanningGuestController::class, 'detach'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.guests.detach');