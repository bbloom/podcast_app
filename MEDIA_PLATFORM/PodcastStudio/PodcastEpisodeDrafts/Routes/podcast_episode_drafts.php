<?php

// =============================================================================
// Routes: Podcast Episode Drafts
//
// Create Draft wizard + CRUD routes.
// All routes require authentication. Ownership (user_id) is enforced in the
// controllers via abort_if().
//
// Path: MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/Routes/
// Loaded via routes/web.php.
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Controllers\PodcastEpisodeDraftController;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\CreateDraft\Controllers\Step1Controller;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\CreateDraft\Controllers\Step2Controller;

// -------------------------------------------------------------------------
// Create Draft Wizard
// -------------------------------------------------------------------------

// Step 1 — Select a podcast show
Route::get('/podcast-episode-drafts/create', [Step1Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episode_drafts.create.step1');

Route::post('/podcast-episode-drafts/create/step1', [Step1Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episode_drafts.create.step1.store');

// Step 2 — Draft details
Route::get('/podcast-episode-drafts/create/step2', [Step2Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episode_drafts.create.step2');

Route::post('/podcast-episode-drafts/create/step2', [Step2Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('podcast_episode_drafts.create.step2.store');

// -------------------------------------------------------------------------
// CRUD
// -------------------------------------------------------------------------

Route::get('/podcast-episode-drafts', [PodcastEpisodeDraftController::class, 'index'])
    ->middleware(['auth'])
    ->name('podcast_episode_drafts.index');

Route::get('/podcast-episode-drafts/{podcast_episode_draft}', [PodcastEpisodeDraftController::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episode_drafts.show');

Route::get('/podcast-episode-drafts/{podcast_episode_draft}/edit', [PodcastEpisodeDraftController::class, 'edit'])
    ->middleware(['auth'])
    ->name('podcast_episode_drafts.edit');

Route::put('/podcast-episode-drafts/{podcast_episode_draft}', [PodcastEpisodeDraftController::class, 'update'])
    ->middleware(['auth'])
    ->name('podcast_episode_drafts.update');

Route::get('/podcast-episode-drafts/{podcast_episode_draft}/delete', [PodcastEpisodeDraftController::class, 'deleteConfirm'])
    ->middleware(['auth'])
    ->name('podcast_episode_drafts.delete.confirm');

Route::delete('/podcast-episode-drafts/{podcast_episode_draft}', [PodcastEpisodeDraftController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('podcast_episode_drafts.destroy');