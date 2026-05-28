<?php

// =============================================================================
// Routes — Upload Production Audio
//
// All routes are individually declared — no Route::resource() or ->group().
// Auth middleware is applied per route.
//
// Route name prefix: post_production.upload_production_audio
//
// Require this file from routes/web.php:
//   require __DIR__ . '/../MEDIA_PLATFORM/Podcasts/PostProduction/UploadProductionAudio/Routes/upload_production_audio.php';
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\Podcasts\Publishing\PostProduction\UploadProductionAudio\Controllers\IndexController;
use MediaPlatform\Podcasts\Publishing\PostProduction\UploadProductionAudio\Controllers\ShowController;
use MediaPlatform\Podcasts\Publishing\PostProduction\UploadProductionAudio\Controllers\ManualUploadController;
use MediaPlatform\Podcasts\Publishing\PostProduction\UploadProductionAudio\Controllers\UploadToStorageController;
use MediaPlatform\Podcasts\Publishing\PostProduction\UploadProductionAudio\Controllers\CleanUpController;
use MediaPlatform\Podcasts\Publishing\PostProduction\UploadProductionAudio\Controllers\DoneController;


// ---------------------------------------------------------------------------
// Index — list episodes ready to upload production audio
// ---------------------------------------------------------------------------
Route::get('/post-production/upload-production-audio', IndexController::class)
    ->middleware('auth')
    ->name('post_production.upload_production_audio.index');

// ---------------------------------------------------------------------------
// Show — decision page (is the file on the server?)
// ---------------------------------------------------------------------------
Route::get('/post-production/upload-production-audio/{podcastEpisode}', ShowController::class)
    ->middleware('auth')
    ->name('post_production.upload_production_audio.show');

// ---------------------------------------------------------------------------
// Manual upload — form (GET) and store (POST)
// ---------------------------------------------------------------------------
Route::get('/post-production/upload-production-audio/{podcastEpisode}/manual-upload', [ManualUploadController::class, 'show'])
    ->middleware('auth')
    ->name('post_production.upload_production_audio.manual_upload');

Route::post('/post-production/upload-production-audio/{podcastEpisode}/manual-upload', [ManualUploadController::class, 'store'])
    ->middleware('auth')
    ->name('post_production.upload_production_audio.manual_upload.store');

// ---------------------------------------------------------------------------
// Upload to storage — confirmation page (GET) and upload (POST)
// ---------------------------------------------------------------------------
Route::get('/post-production/upload-production-audio/{podcastEpisode}/upload-to-storage', [UploadToStorageController::class, 'show'])
    ->middleware('auth')
    ->name('post_production.upload_production_audio.upload_to_storage');

Route::post('/post-production/upload-production-audio/{podcastEpisode}/upload-to-storage', [UploadToStorageController::class, 'store'])
    ->middleware('auth')
    ->name('post_production.upload_production_audio.upload_to_storage.store');

// ---------------------------------------------------------------------------
// Clean-up — confirmation page (GET) and destroy (POST)
// ---------------------------------------------------------------------------
Route::get('/post-production/upload-production-audio/{podcastEpisode}/clean-up', [CleanUpController::class, 'confirm'])
    ->middleware('auth')
    ->name('post_production.upload_production_audio.cleanup_confirm');

Route::post('/post-production/upload-production-audio/{podcastEpisode}/clean-up', [CleanUpController::class, 'destroy'])
    ->middleware('auth')
    ->name('post_production.upload_production_audio.cleanup');



// Done — "what next?" page shown after production audio clean-up completes.
// All upload work is done; status is ready_to_generate_rss_feed.
Route::get('/post-production/upload-production-audio/{podcastEpisode}/done', DoneController::class)
    ->middleware(['auth'])
    ->name('post_production.upload_production_audio.done')
;    