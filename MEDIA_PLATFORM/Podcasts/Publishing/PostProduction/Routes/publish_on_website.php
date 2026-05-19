<?php

// =============================================================================
// Routes: PublishOnWebsite
//
// All routes for the "Publish on Website" post-production pipeline step,
// including the "Trigger Static Site Builds" step that follows publishing.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/publish_on_website.php
// Registered in: routes/web.php
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers\IndexController;
use MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers\ShowController;
use MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers\PublishController;
use MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers\TriggerBuildsController;
use MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers\TriggerBuildsResultController;

// ── Publish on Website pipeline ───────────────────────────────────────────────

// Index — list episodes ready to publish on the website.
Route::get('/post-production/publish-on-website', IndexController::class)
    ->middleware(['auth'])
    ->name('post_production.publish_on_website.index');

// Show — display the confirmation page for a specific episode.
Route::get('/post-production/publish-on-website/{podcastEpisode}', ShowController::class)
    ->middleware(['auth'])
    ->name('post_production.publish_on_website.show');

// Publish — confirm and publish the episode on the website.
Route::post('/post-production/publish-on-website/{podcastEpisode}', [PublishController::class, 'publish'])
    ->middleware(['auth'])
    ->name('post_production.publish_on_website.publish');

// ── Trigger Static Site Builds ────────────────────────────────────────────────
//
// Reached from two entry points:
//   1. After publishing an episode (PublishController redirects here when
//      website_publish_on <= today)
//   2. Directly from the podcast show's show view (manual trigger)
//
// The {context} parameter in the select route is optional — defaults to 'show'.

// Select — checkbox page to choose which deploy hooks to fire.
Route::get('/post-production/trigger-builds/{podcastShow}', [TriggerBuildsController::class, 'select'])
    ->middleware(['auth'])
    ->name('post_production.trigger_builds.select');

// Trigger — fire the selected hooks and redirect to results.
Route::post('/post-production/trigger-builds/{podcastShow}', [TriggerBuildsController::class, 'trigger'])
    ->middleware(['auth'])
    ->name('post_production.trigger_builds.trigger');

// Results — display the outcome of the triggered builds.
Route::get('/post-production/trigger-builds/{podcastShow}/results', TriggerBuildsResultController::class)
    ->middleware(['auth'])
    ->name('post_production.trigger_builds.results');