<?php

// =============================================================================
// Routes: AuphonicProcessing
//
// All routes for the "Submit to Auphonic" post-production pipeline step.
//
// The webhook route is excluded from CSRF verification in bootstrap/app.php.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/AuphonicProcessing/Routes/
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Controllers\IndexController;
use MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Controllers\SubmitController;
use MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Controllers\WebhookController;
use MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Controllers\CompleteController;
use MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Controllers\ResubmitController;
use MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Controllers\WebhookStatusController;
use MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Controllers\CleanUpController;
use MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Controllers\ReplaceRecordingController;
use MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Controllers\DoneController;


// -----------------------------------------------------------------------------
// Webhook route — no auth, no CSRF (external POST from Auphonic servers).
// CSRF exclusion is configured in bootstrap/app.php.
//
// IMPORTANT: This route must be defined BEFORE the {podcastEpisode} wildcard
// routes, otherwise Laravel will match "webhook" as a podcastEpisode parameter.
// -----------------------------------------------------------------------------

Route::post('/post-production/auphonic/webhook', WebhookController::class)
    ->name('post_production.auphonic_processing.webhook')
;



// -----------------------------------------------------------------------------
// Authenticated routes — all require a logged-in user.
// -----------------------------------------------------------------------------

// Index — list episodes ready for Auphonic submission.
Route::get('/post-production/auphonic', IndexController::class)
    ->middleware(['auth'])
    ->name('post_production.auphonic_processing.index')
;

// Show — episode detail page with S3 check and "Submit to Auphonic" button.
Route::get('/post-production/auphonic/{podcastEpisode}',[SubmitController::class, 'show'])
    ->middleware(['auth'])
    ->name('post_production.auphonic_processing.show')
;

// Submit — POST to create and start an Auphonic production.
Route::post('/post-production/auphonic/{podcastEpisode}/submit',[SubmitController::class, 'submit'])
    ->middleware(['auth'])
    ->name('post_production.auphonic_processing.submit')
;

// Complete — "Done!" screen shown after the webhook fires.
Route::get('/post-production/auphonic/{podcastEpisode}/complete', CompleteController::class)
    ->middleware(['auth'])
    ->name('post_production.auphonic_processing.complete')
;

// Resubmit confirm — confirmation page before the destructive re-submit runs.
Route::get('/post-production/auphonic/{podcastEpisode}/resubmit',[ResubmitController::class, 'confirm'])
    ->middleware(['auth'])
    ->name('post_production.auphonic_processing.resubmit_confirm')
;

// Resubmit — delete existing Auphonic production and start a new one.
Route::post('/post-production/auphonic/{podcastEpisode}/resubmit',[ResubmitController::class, 'resubmit'])
    ->middleware(['auth'])
    ->name('post_production.auphonic_processing.resubmit')
;

// Webhook status — JSON endpoint polled by Alpine.js on the processing page.
// Returns the current episode status so the UI can react when Auphonic completes.
Route::get('/post-production/auphonic/{podcastEpisode}/webhook-status', WebhookStatusController::class)
    ->middleware(['auth'])
    ->name('post_production.auphonic_processing.webhook_status')
;

// Replace recording — resets status to ready_to_upload_recording and redirects
// to the upload flow when the wrong file was uploaded to S3.
Route::post('/post-production/auphonic/{podcastEpisode}/replace-recording', ReplaceRecordingController::class)
    ->middleware(['auth'])
    ->name('post_production.auphonic_processing.replace_recording')
;

// Clean Up — confirmation page before destructive clean-up runs.
Route::get('/post-production/auphonic/{podcastEpisode}/cleanup',[CleanUpController::class, 'confirm'])
    ->middleware(['auth'])
    ->name('post_production.auphonic_processing.cleanup_confirm')
;

// Clean Up — runs the destructive clean-up sequence after confirmation.
Route::post('/post-production/auphonic/{podcastEpisode}/cleanup',[CleanUpController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('post_production.auphonic_processing.cleanup_destroy')
;

// Done — "what next?" page shown after clean-up completes.
// All Auphonic work is done; status is ready_to_upload_production_file.
Route::get('/post-production/auphonic/{podcastEpisode}/done', DoneController::class)
    ->middleware(['auth'])
    ->name('post_production.auphonic_processing.done')
;