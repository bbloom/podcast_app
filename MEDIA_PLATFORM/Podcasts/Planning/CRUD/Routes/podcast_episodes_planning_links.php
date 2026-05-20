<?php

use MediaPlatform\Podcasts\Planning\CRUD\Controllers\PodcastEpisodePlanningLinkController;

// -----------------------------------------------------------------------------
// Planning Episode — Link Attach / Detach
// -----------------------------------------------------------------------------

Route::get('/planning-episodes/{podcast_episode_planning}/attach-link', [PodcastEpisodePlanningLinkController::class, 'attachIndex'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.links.attach.index');

Route::post('/planning-episodes/{podcast_episode_planning}/attach-link/{podcast_link}', [PodcastEpisodePlanningLinkController::class, 'attach'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.links.attach');

Route::delete('/planning-episodes/{podcast_episode_planning}/detach-link/{podcast_link}', [PodcastEpisodePlanningLinkController::class, 'detach'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.links.detach');