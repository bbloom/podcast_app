<?php

// =============================================================================
// Routes: PublishOnWebsite
//
// All routes for the "Publish on Website" post-production pipeline step.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/publish_on_website.php
// Registered in: routes/web.php
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\PodcastStudio\PostProduction\PublishOnWebsite\Controllers\IndexController;
use MediaPlatform\PodcastStudio\PostProduction\PublishOnWebsite\Controllers\ShowController;
use MediaPlatform\PodcastStudio\PostProduction\PublishOnWebsite\Controllers\PublishController;

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