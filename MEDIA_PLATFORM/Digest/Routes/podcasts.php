<?php

use MediaPlatform\Digest\ContentSources\Podcasts\Controllers\PodcastWizardController;
use Illuminate\Support\Facades\Route;


// -------------------------------------------------------------------------
// Podcast Wizard
// -------------------------------------------------------------------------

Route::get('/podcasts/create/step1', [PodcastWizardController::class, 'step1'])
    ->middleware(['auth'])
    ->name('podcasts.create.step1');

Route::post('/podcasts/create/step1', [PodcastWizardController::class, 'step1Submit'])
    ->middleware(['auth'])
    ->name('podcasts.create.step1.submit');


Route::get('/podcasts/create/step2', [PodcastWizardController::class, 'step2'])
    ->middleware(['auth'])
    ->name('podcasts.create.step2');

Route::post('/podcasts/create/step2', [PodcastWizardController::class, 'step2Submit'])
    ->middleware(['auth'])
    ->name('podcasts.create.step2.submit');


Route::get('/podcasts/create/step3', [PodcastWizardController::class, 'step3'])
    ->middleware(['auth'])
    ->name('podcasts.create.step3');

Route::post('/podcasts/create/step3', [PodcastWizardController::class, 'step3Submit'])
    ->middleware(['auth'])
    ->name('podcasts.create.step3.submit');


Route::get('/podcasts/create/step4', [PodcastWizardController::class, 'step4'])
    ->middleware(['auth'])
    ->name('podcasts.create.step4');


// -------------------------------------------------------------------------
// Podcast CRUD
// -------------------------------------------------------------------------

Route::get('/podcasts', [PodcastWizardController::class, 'index'])
    ->middleware(['auth'])
    ->name('podcasts.index');

Route::get('/podcasts/{podcast}/edit', [PodcastWizardController::class, 'edit'])
    ->middleware(['auth'])
    ->name('podcasts.edit');

Route::put('/podcasts/{podcast}', [PodcastWizardController::class, 'update'])
    ->middleware(['auth'])
    ->name('podcasts.update');

Route::get('/podcasts/{podcast}/delete', [PodcastWizardController::class, 'confirmDelete'])
    ->middleware(['auth'])
    ->name('podcasts.delete.confirm');

Route::delete('/podcasts/{podcast}', [PodcastWizardController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('podcasts.destroy');

Route::get('/podcasts/{podcast}', [PodcastWizardController::class, 'show'])
    ->middleware(['auth'])
    ->name('podcasts.show');


// -------------------------------------------------------------------------
// Podcast — List Source attach / edit / detach
// -------------------------------------------------------------------------

// Attach this podcast to a list (POST from the inline form on the show page)
Route::post('/podcasts/{podcast}/list-sources', [PodcastWizardController::class, 'attachList'])
    ->middleware(['auth'])
    ->name('podcasts.list_sources.attach');

// Update processing_mode / search_terms for an existing list_source row
Route::patch('/podcasts/{podcast}/list-sources/{listSource}', [PodcastWizardController::class, 'updateListSource'])
    ->middleware(['auth'])
    ->name('podcasts.list_sources.update');

// Confirmation page before detaching (shows which list will be affected)
Route::get('/podcasts/{podcast}/list-sources/{listSource}/detach', [PodcastWizardController::class, 'detachConfirm'])
    ->middleware(['auth'])
    ->name('podcasts.list_sources.detach.confirm');

// Execute the detach (deletes the list_source row)
Route::delete('/podcasts/{podcast}/list-sources/{listSource}', [PodcastWizardController::class, 'detach'])
    ->middleware(['auth'])
    ->name('podcasts.list_sources.detach');