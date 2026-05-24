<?php

use Illuminate\Support\Facades\Route;
use MediaPlatform\Podcasts\Publishing\PostProduction\UploadRecording\Controllers\UploadRecordingController;
use MediaPlatform\Podcasts\Publishing\PostProduction\UploadRecording\Controllers\DoneController;

// -----------------------------------------------------------------------------
// Post-Production — Upload Recording to S3
//
// Flow:
//   index   → list episodes with status ready_to_upload_recording
//   show    → upload page for a specific episode
//   store   → generate pre-signed S3 PUT URL (returns JSON)
//   complete → verify file exists in S3, advance status to ready_for_auphonic
// -----------------------------------------------------------------------------

Route::get('/post-production/upload-recording', [UploadRecordingController::class, 'index'])
    ->middleware(['auth'])
    ->name('post_production.upload_recording.index')
;

Route::get('/post-production/upload-recording/{episode}', [UploadRecordingController::class, 'show'])
    ->middleware(['auth'])
    ->name('post_production.upload_recording.show')
;

Route::post('/post-production/upload-recording/{episode}/presign', [UploadRecordingController::class, 'store'])
    ->middleware(['auth'])
    ->name('post_production.upload_recording.store')
;

Route::post('/post-production/upload-recording/{episode}/complete', [UploadRecordingController::class, 'complete'])
    ->middleware(['auth'])
    ->name('post_production.upload_recording.complete')
;

// Done — "what next?" page shown after a successful recording upload.
// All upload work is complete; status is ready_for_auphonic.
Route::get('/post-production/upload-recording/{episode}/done', DoneController::class)
    ->middleware(['auth'])
    ->name('post_production.upload_recording.done')
;