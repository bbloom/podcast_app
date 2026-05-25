<?php

// =============================================================================
// Routes: RegenerateRssFeed
//
// RSS PIPELINE REORDER CHANGES:
//   Live Validation routes added after PromoteController. PromoteController
//   now redirects to live_validation instead of the index.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/regenerate_rss_feed.php
// Registered in: routes/web.php
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\Podcasts\Publishing\PostProduction\RegenerateRssFeed\Controllers\IndexController;
use MediaPlatform\Podcasts\Publishing\PostProduction\RegenerateRssFeed\Controllers\StageController;
use MediaPlatform\Podcasts\Publishing\PostProduction\RegenerateRssFeed\Controllers\PromoteController;
use MediaPlatform\Podcasts\Publishing\PostProduction\RegenerateRssFeed\Controllers\LiveValidationController;

// Index — list all shows, select one to regenerate.
Route::get('/post-production/regenerate-rss-feed', IndexController::class)
    ->middleware(['auth'])
    ->name('post_production.regenerate_rss_feed.index');

// Stage — generate XML, upload to WIP staging bucket.
// View no longer shows external validator links (moved to Live Validation).
Route::get('/post-production/regenerate-rss-feed/{podcastShow}/stage', StageController::class)
    ->middleware(['auth'])
    ->name('post_production.regenerate_rss_feed.stage');

// Promote — upload to live S3 only, redirect to Live Validation.
Route::post('/post-production/regenerate-rss-feed/{podcastShow}/promote', [PromoteController::class, 'promote'])
    ->middleware(['auth'])
    ->name('post_production.regenerate_rss_feed.promote');

// Live Validation — validate against live S3 URL, then promote to R2.
Route::get('/post-production/regenerate-rss-feed/{podcastShow}/live-validation', [LiveValidationController::class, 'show'])
    ->middleware(['auth'])
    ->name('post_production.regenerate_rss_feed.live_validation');

Route::post('/post-production/regenerate-rss-feed/{podcastShow}/live-validation/promote', [LiveValidationController::class, 'promoteToR2'])
    ->middleware(['auth'])
    ->name('post_production.regenerate_rss_feed.live_validation.promote');