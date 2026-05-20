<?php

use MediaPlatform\Digest\ContentSources\Podcasts\Controllers\PodcastWizardController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// Digest — Podcast Content Sources
//
// URL prefix: /digests/podcasts
// Route name prefix: podcasts.*
//
// These routes manage podcast RSS feeds as content sources for the Digest
// feature. They are entirely separate from the Podcasts production module
// at MEDIA_PLATFORM/Podcasts/, which handles episode production and publishing.
// =============================================================================


// -------------------------------------------------------------------------
// Podcast Wizard
// -------------------------------------------------------------------------

Route::get('/digests/podcasts/create/step1', [PodcastWizardController::class, 'step1'])
    ->middleware(['auth'])
    ->name('digest-podcasts.create.step1');

Route::post('/digests/podcasts/create/step1', [PodcastWizardController::class, 'step1Submit'])
    ->middleware(['auth'])
    ->name('digest-podcasts.create.step1.submit');

Route::get('/digests/podcasts/create/step2', [PodcastWizardController::class, 'step2'])
    ->middleware(['auth'])
    ->name('digest-podcasts.create.step2');

Route::post('/digests/podcasts/create/step2', [PodcastWizardController::class, 'step2Submit'])
    ->middleware(['auth'])
    ->name('digest-podcasts.create.step2.submit');

Route::get('/digests/podcasts/create/step3', [PodcastWizardController::class, 'step3'])
    ->middleware(['auth'])
    ->name('digest-podcasts.create.step3');

Route::post('/digests/podcasts/create/step3', [PodcastWizardController::class, 'step3Submit'])
    ->middleware(['auth'])
    ->name('digest-podcasts.create.step3.submit');

Route::get('/digests/podcasts/create/step4', [PodcastWizardController::class, 'step4'])
    ->middleware(['auth'])
    ->name('digest-podcasts.create.step4');


// -------------------------------------------------------------------------
// Podcast CRUD
// -------------------------------------------------------------------------

Route::get('/digests/podcasts', [PodcastWizardController::class, 'index'])
    ->middleware(['auth'])
    ->name('digest-podcasts.index');

Route::get('/digests/podcasts/{podcast}/edit', [PodcastWizardController::class, 'edit'])
    ->middleware(['auth'])
    ->name('digest-podcasts.edit');

Route::put('/digests/podcasts/{podcast}', [PodcastWizardController::class, 'update'])
    ->middleware(['auth'])
    ->name('digest-podcasts.update');

Route::get('/digests/podcasts/{podcast}/delete', [PodcastWizardController::class, 'confirmDelete'])
    ->middleware(['auth'])
    ->name('digest-podcasts.delete.confirm');

Route::delete('/digests/podcasts/{podcast}', [PodcastWizardController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('digest-podcasts.destroy');

Route::get('/digests/podcasts/{podcast}', [PodcastWizardController::class, 'show'])
    ->middleware(['auth'])
    ->name('digest-podcasts.show');


// -------------------------------------------------------------------------
// Podcast — List Source attach / edit / detach
// -------------------------------------------------------------------------

Route::post('/digests/podcasts/{podcast}/list-sources', [PodcastWizardController::class, 'attachList'])
    ->middleware(['auth'])
    ->name('digest-podcasts.list_sources.attach');

Route::patch('/digests/podcasts/{podcast}/list-sources/{listSource}', [PodcastWizardController::class, 'updateListSource'])
    ->middleware(['auth'])
    ->name('digest-podcasts.list_sources.update');

Route::get('/digests/podcasts/{podcast}/list-sources/{listSource}/detach', [PodcastWizardController::class, 'detachConfirm'])
    ->middleware(['auth'])
    ->name('digest-podcasts.list_sources.detach.confirm');

Route::delete('/digests/podcasts/{podcast}/list-sources/{listSource}', [PodcastWizardController::class, 'detach'])
    ->middleware(['auth'])
    ->name('digest-podcasts.list_sources.detach');