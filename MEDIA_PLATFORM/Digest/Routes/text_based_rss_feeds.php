<?php

use MediaPlatform\Digest\ContentSources\TextBasedRssFeeds\Controllers\TextBasedRssFeedWizardController;
use Illuminate\Support\Facades\Route;


// -------------------------------------------------------------------------
// Text Based RSS Feed Wizard
// -------------------------------------------------------------------------

Route::get('/text-based-rss-feeds/create/step1', [TextBasedRssFeedWizardController::class, 'step1'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.create.step1');

Route::post('/text-based-rss-feeds/create/step1', [TextBasedRssFeedWizardController::class, 'step1Submit'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.create.step1.submit');


Route::get('/text-based-rss-feeds/create/step2', [TextBasedRssFeedWizardController::class, 'step2'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.create.step2');

Route::post('/text-based-rss-feeds/create/step2', [TextBasedRssFeedWizardController::class, 'step2Submit'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.create.step2.submit');


Route::get('/text-based-rss-feeds/create/step3', [TextBasedRssFeedWizardController::class, 'step3'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.create.step3');

Route::post('/text-based-rss-feeds/create/step3', [TextBasedRssFeedWizardController::class, 'step3Submit'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.create.step3.submit');


Route::get('/text-based-rss-feeds/create/step4', [TextBasedRssFeedWizardController::class, 'step4'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.create.step4');


// -------------------------------------------------------------------------
// Text Based RSS Feed CRUD
// -------------------------------------------------------------------------

Route::get('/text-based-rss-feeds', [TextBasedRssFeedWizardController::class, 'index'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.index');

Route::get('/text-based-rss-feeds/{textBasedRssFeed}/edit', [TextBasedRssFeedWizardController::class, 'edit'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.edit');

Route::put('/text-based-rss-feeds/{textBasedRssFeed}', [TextBasedRssFeedWizardController::class, 'update'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.update');

Route::get('/text-based-rss-feeds/{textBasedRssFeed}/delete', [TextBasedRssFeedWizardController::class, 'confirmDelete'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.delete.confirm');

Route::delete('/text-based-rss-feeds/{textBasedRssFeed}', [TextBasedRssFeedWizardController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.destroy');

Route::get('/text-based-rss-feeds/{textBasedRssFeed}', [TextBasedRssFeedWizardController::class, 'show'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.show');


// -------------------------------------------------------------------------
// Text Based RSS Feed — List Source attach / edit / detach
// -------------------------------------------------------------------------

// Attach this feed to a list (POST from the inline form on the show page)
Route::post('/text-based-rss-feeds/{textBasedRssFeed}/list-sources', [TextBasedRssFeedWizardController::class, 'attachList'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.list_sources.attach');

// Update processing_mode / search_terms for an existing list_source row
Route::patch('/text-based-rss-feeds/{textBasedRssFeed}/list-sources/{listSource}', [TextBasedRssFeedWizardController::class, 'updateListSource'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.list_sources.update');

// Confirmation page before detaching (shows which list will be affected)
Route::get('/text-based-rss-feeds/{textBasedRssFeed}/list-sources/{listSource}/detach', [TextBasedRssFeedWizardController::class, 'detachConfirm'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.list_sources.detach.confirm');

// Execute the detach (deletes the list_source row)
Route::delete('/text-based-rss-feeds/{textBasedRssFeed}/list-sources/{listSource}', [TextBasedRssFeedWizardController::class, 'detach'])
    ->middleware(['auth'])
    ->name('text_based_rss_feeds.list_sources.detach');