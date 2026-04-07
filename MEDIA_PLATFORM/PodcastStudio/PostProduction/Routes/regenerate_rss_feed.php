<?php

// =============================================================================
// Routes: RegenerateRssFeed
//
// All routes for the "Regenerate RSS Feed" maintenance flow.
//
// This is a show-level operation — it rebuilds the entire RSS feed for a show
// from all eligible episodes, independent of any individual episode's status.
// No episode status changes occur.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/regenerate_rss_feed.php
// Registered in: routes/web.php
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\PodcastStudio\PostProduction\RegenerateRssFeed\Controllers\IndexController;
use MediaPlatform\PodcastStudio\PostProduction\RegenerateRssFeed\Controllers\StageController;
use MediaPlatform\PodcastStudio\PostProduction\RegenerateRssFeed\Controllers\PromoteController;

// Index — list all shows, select one to regenerate.
Route::get('/post-production/regenerate-rss-feed', IndexController::class)
    ->middleware(['auth'])
    ->name('post_production.regenerate_rss_feed.index');

// Stage — generate XML and upload to staging bucket, display validator links.
Route::get('/post-production/regenerate-rss-feed/{podcastShow}/stage', StageController::class)
    ->middleware(['auth'])
    ->name('post_production.regenerate_rss_feed.stage');

// Promote — promote from staging to live S3 + R2, delete staging file.
Route::post('/post-production/regenerate-rss-feed/{podcastShow}/promote', [PromoteController::class, 'promote'])
    ->middleware(['auth'])
    ->name('post_production.regenerate_rss_feed.promote');