<?php

use MediaPlatform\Digest\ContentSources\Youtube\Controllers\YoutubeChannelWizardController;
use Illuminate\Support\Facades\Route;


// -------------------------------------------------------------------------
// Youtube Channel Wizard
// -------------------------------------------------------------------------

Route::get('/youtube/channels/create/step1', [YoutubeChannelWizardController::class, 'step1'])
    ->middleware(['auth'])
    ->name('youtube.channels.create.step1');

Route::post('/youtube/channels/create/step1', [YoutubeChannelWizardController::class, 'step1Submit'])
    ->middleware(['auth'])
    ->name('youtube.channels.create.step1.submit');


Route::get('/youtube/channels/create/step2', [YoutubeChannelWizardController::class, 'step2'])
    ->middleware(['auth'])
    ->name('youtube.channels.create.step2');

Route::post('/youtube/channels/create/step2', [YoutubeChannelWizardController::class, 'step2Submit'])
    ->middleware(['auth'])
    ->name('youtube.channels.create.step2.submit');


Route::get('/youtube/channels/create/step3', [YoutubeChannelWizardController::class, 'step3'])
    ->middleware(['auth'])
    ->name('youtube.channels.create.step3');

Route::post('/youtube/channels/create/step3', [YoutubeChannelWizardController::class, 'step3Submit'])
    ->middleware(['auth'])
    ->name('youtube.channels.create.step3.submit');


Route::get('/youtube/channels/create/step4', [YoutubeChannelWizardController::class, 'step4'])
    ->middleware(['auth'])
    ->name('youtube.channels.create.step4');

Route::post('/youtube/channels/create/step4', [YoutubeChannelWizardController::class, 'step4Submit'])
    ->middleware(['auth'])
    ->name('youtube.channels.create.step4.submit');


Route::get('/youtube/channels/create/step5', [YoutubeChannelWizardController::class, 'step5'])
    ->middleware(['auth'])
    ->name('youtube.channels.create.step5');


// -------------------------------------------------------------------------
// Youtube Channel CRUD
// -------------------------------------------------------------------------

Route::get('/youtube/channels', [YoutubeChannelWizardController::class, 'index'])
    ->middleware(['auth'])
    ->name('youtube.channels.index');

Route::get('/youtube/channels/{youtubeChannel}/edit', [YoutubeChannelWizardController::class, 'edit'])
    ->middleware(['auth'])
    ->name('youtube.channels.edit');

Route::put('/youtube/channels/{youtubeChannel}', [YoutubeChannelWizardController::class, 'update'])
    ->middleware(['auth'])
    ->name('youtube.channels.update');

Route::get('/youtube/channels/{youtubeChannel}/delete', [YoutubeChannelWizardController::class, 'confirmDelete'])
    ->middleware(['auth'])
    ->name('youtube.channels.delete.confirm');

Route::delete('/youtube/channels/{youtubeChannel}', [YoutubeChannelWizardController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('youtube.channels.destroy');

Route::get('/youtube/channels/{youtubeChannel}', [YoutubeChannelWizardController::class, 'show'])
    ->middleware(['auth'])
    ->name('youtube.channels.show');


// -------------------------------------------------------------------------
// Youtube Channel — List Source attach / edit / detach
// -------------------------------------------------------------------------

// Attach this channel to a list (POST from the inline form on the show page)
Route::post('/youtube/channels/{youtubeChannel}/list-sources', [YoutubeChannelWizardController::class, 'attachList'])
    ->middleware(['auth'])
    ->name('youtube.channels.list_sources.attach');

// Update processing_mode / search_terms for an existing list_source row
Route::patch('/youtube/channels/{youtubeChannel}/list-sources/{listSource}', [YoutubeChannelWizardController::class, 'updateListSource'])
    ->middleware(['auth'])
    ->name('youtube.channels.list_sources.update');

// Confirmation page before detaching (shows which list will be affected)
Route::get('/youtube/channels/{youtubeChannel}/list-sources/{listSource}/detach', [YoutubeChannelWizardController::class, 'detachConfirm'])
    ->middleware(['auth'])
    ->name('youtube.channels.list_sources.detach.confirm');

// Execute the detach (deletes the list_source row)
Route::delete('/youtube/channels/{youtubeChannel}/list-sources/{listSource}', [YoutubeChannelWizardController::class, 'detach'])
    ->middleware(['auth'])
    ->name('youtube.channels.list_sources.detach');