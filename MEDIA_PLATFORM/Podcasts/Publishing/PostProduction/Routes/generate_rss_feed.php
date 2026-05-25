<?php

// =============================================================================
// Routes: GenerateRssFeed
//
// RSS PIPELINE REORDER CHANGES:
//   - Step 4 (staging validation) routes REMOVED entirely.
//   - Live Validation routes ADDED after Step 5.
//   - Restart route ADDED for rss_validation_failed and session-expired recovery.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/generate_rss_feed.php
// Registered in: routes/web.php
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers\IndexController;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers\Step1Controller;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers\Step2Controller;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers\Step3Controller;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers\Step5Controller;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers\LiveValidationController;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers\RestartController;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers\DoneController;

// Index — list episodes ready for RSS feed generation.
Route::get('/post-production/generate-rss-feed', IndexController::class)
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.index');

// Step 1 — episode review page (show link, enclosure length, duration).
Route::get('/post-production/generate-rss-feed/{podcastEpisode}/step1', [Step1Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.step1');

Route::post('/post-production/generate-rss-feed/{podcastEpisode}/step1', [Step1Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.step1.store');

// Step 2 — pre-generation field validation.
Route::get('/post-production/generate-rss-feed/{podcastEpisode}/step2', [Step2Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.step2');

Route::post('/post-production/generate-rss-feed/{podcastEpisode}/step2', [Step2Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.step2.store');

// Step 3 — generate XML and upload to staging bucket.
Route::get('/post-production/generate-rss-feed/{podcastEpisode}/step3', [Step3Controller::class, 'show'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.step3');

// Step 4 REMOVED — staging validation replaced by Live Validation after Step 5.

// Step 5 — promote to live S3 (R2 deferred to Live Validation).
Route::post('/post-production/generate-rss-feed/{podcastEpisode}/step5', [Step5Controller::class, 'store'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.step5');

// Live Validation — validate against live S3 URL, then promote to R2.
Route::get('/post-production/generate-rss-feed/{podcastEpisode}/live-validation', [LiveValidationController::class, 'show'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.live_validation');

Route::post('/post-production/generate-rss-feed/{podcastEpisode}/live-validation/promote', [LiveValidationController::class, 'promoteToR2'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.live_validation.promote');

Route::post('/post-production/generate-rss-feed/{podcastEpisode}/live-validation/fail', [LiveValidationController::class, 'fail'])
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.live_validation.fail');

// Restart — resets rss_validation_failed or ready_to_upload_rss_feed back
// to ready_to_generate_rss_feed and redirects to Step 1.
Route::get('/post-production/generate-rss-feed/{podcastEpisode}/restart', RestartController::class)
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.restart');

// Done — shown after R2 promotion succeeds. Episode is now published.
Route::get('/post-production/generate-rss-feed/{podcastEpisode}/done', DoneController::class)
    ->middleware(['auth'])
    ->name('post_production.generate_rss_feed.done');