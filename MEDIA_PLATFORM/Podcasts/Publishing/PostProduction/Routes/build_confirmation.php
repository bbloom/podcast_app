<?php

// =============================================================================
// Routes: BuildConfirmation
//
// Pipeline step between TriggerBuilds and GenerateRssFeed.
// The episode sits in `build_triggered` status while on this step.
//
// Path: MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/BuildConfirmation/Routes/
// Register in routes/web.php alongside the other post-production route files.
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\Podcasts\Publishing\PostProduction\BuildConfirmation\Controllers\ShowController;
use MediaPlatform\Podcasts\Publishing\PostProduction\BuildConfirmation\Controllers\ConfirmController;
use MediaPlatform\StaticSiteDeployHooks\Controllers\BuildStatusController;

// Show — auto-polling build status page. Episode must be in `build_triggered`.
Route::get('/post-production/build-confirmation/{podcastEpisode}', ShowController::class)
    ->middleware(['auth'])
    ->name('post_production.build_confirmation.show');

// Confirm — advance status to `ready_to_generate_rss_feed` and continue.
// Reached via the "Continue" link (automated) or "Confirm manually" link (manual).
Route::get('/post-production/build-confirmation/{podcastEpisode}/confirm', ConfirmController::class)
    ->middleware(['auth'])
    ->name('post_production.build_confirmation.confirm');


// ── Build Status (Cloudflare Pages) ──────────────────────────────────────────
//
// JSON endpoint polled by Alpine.js on the deploy hook show page and the
// BuildConfirmation pipeline step. Returns the current deployment status
// for the hook's last triggered build.

Route::get('/deploy-hooks/{deploy_hook}/build-status', BuildStatusController::class)
    ->middleware(['auth'])
    ->name('deploy_hooks.build_status');    
