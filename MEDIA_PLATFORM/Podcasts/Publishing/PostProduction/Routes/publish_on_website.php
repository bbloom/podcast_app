<?php

// =============================================================================
// Routes: PublishOnWebsite
//
// All routes for the "Publish on Website" post-production pipeline step,
// including the "Trigger Static Site Builds" step that follows publishing,
// and the PrepareTriggerBuilds bridge route added for the RSS Pipeline Reorder.
//
// Path: MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/Routes/publish_on_website.php
// Registered in: routes/web.php
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers\IndexController;
use MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers\ShowController;
use MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers\PublishController;
use MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers\PrepareTriggerBuildsController;
use MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers\TriggerBuildsController;
use MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers\TriggerBuildsResultController;

// ── Publish on Website pipeline ───────────────────────────────────────────────

Route::get('/post-production/publish-on-website', IndexController::class)
    ->middleware(['auth'])
    ->name('post_production.publish_on_website.index');

Route::get('/post-production/publish-on-website/{podcastEpisode}', ShowController::class)
    ->middleware(['auth'])
    ->name('post_production.publish_on_website.show');

Route::post('/post-production/publish-on-website/{podcastEpisode}', [PublishController::class, 'publish'])
    ->middleware(['auth'])
    ->name('post_production.publish_on_website.publish');

// ── Prepare Trigger Builds ────────────────────────────────────────────────────
//
// Bridge route for the dashboard Continue button when an episode is in
// `website_published` status. Stores the episode ID in the session and
// redirects to the show-level TriggerBuilds select page.
// Added as part of the RSS Pipeline Reorder.

Route::get('/post-production/prepare-trigger-builds/{podcastEpisode}', PrepareTriggerBuildsController::class)
    ->middleware(['auth'])
    ->name('post_production.prepare_trigger_builds');

// ── Trigger Static Site Builds ────────────────────────────────────────────────

Route::get('/post-production/trigger-builds/{podcastShow}', [TriggerBuildsController::class, 'select'])
    ->middleware(['auth'])
    ->name('post_production.trigger_builds.select');

Route::post('/post-production/trigger-builds/{podcastShow}', [TriggerBuildsController::class, 'trigger'])
    ->middleware(['auth'])
    ->name('post_production.trigger_builds.trigger');

Route::get('/post-production/trigger-builds/{podcastShow}/results', TriggerBuildsResultController::class)
    ->middleware(['auth'])
    ->name('post_production.trigger_builds.results');