<?php

// =============================================================================
// Routes: GenerateRssFeed
//
// All routes for the "Generate RSS Feed" post-production pipeline step.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/generate_rss_feed.php
// Registered in: routes/web.php
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\PodcastStudio\PostProduction\GenerateRssFeed\Controllers\IndexController;
use MediaPlatform\PodcastStudio\PostProduction\GenerateRssFeed\Controllers\Step1Controller;
use MediaPlatform\PodcastStudio\PostProduction\GenerateRssFeed\Controllers\Step2Controller;
use MediaPlatform\PodcastStudio\PostProduction\GenerateRssFeed\Controllers\Step3Controller;
use MediaPlatform\PodcastStudio\PostProduction\GenerateRssFeed\Controllers\Step4Controller;
use MediaPlatform\PodcastStudio\PostProduction\GenerateRssFeed\Controllers\Step5Controller;

// Index — list episodes ready for RSS feed generation.
Route::get('/post-production/generate-rss-feed', IndexController::class)
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.index');

// Step 1 — episode review page (show link, enclosure length, duration).
Route::get('/post-production/generate-rss-feed/{podcastEpisode}/step1', [Step1Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.step1');

// Step 1 — confirm and advance to Step 2.
Route::post('/post-production/generate-rss-feed/{podcastEpisode}/step1', [Step1Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.step1.store');

// Step 2 — pre-generation validation.
Route::get('/post-production/generate-rss-feed/{podcastEpisode}/step2', [Step2Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.step2');

// Step 2 — R2 manual enclosure confirmation form submission.
Route::post('/post-production/generate-rss-feed/{podcastEpisode}/step2', [Step2Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.step2.store');

// Step 3 — generate XML and upload to staging bucket.
Route::get('/post-production/generate-rss-feed/{podcastEpisode}/step3', [Step3Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.step3');

// Step 4 — external validator links page.
Route::get('/post-production/generate-rss-feed/{podcastEpisode}/step4', [Step4Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.step4');

// Step 4 — "something failed" — redirect to episode show page.
Route::post('/post-production/generate-rss-feed/{podcastEpisode}/step4/failed', [Step4Controller::class, 'failed'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.step4.failed');

// Step 5 — promote to live S3 + R2, advance status, clear session.
Route::post('/post-production/generate-rss-feed/{podcastEpisode}/step5', [Step5Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.step5');