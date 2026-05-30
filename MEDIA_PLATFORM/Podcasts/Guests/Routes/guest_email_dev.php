<?php

use MediaPlatform\Podcasts\Guests\Controllers\Dev\TemporaryGuestEmailsController;
use MediaPlatform\Podcasts\Guests\Controllers\Dev\TemporarySendTestEmailController;

// -----------------------------------------------------------------------------
// Dev — Guest Email Scaffolding
//
// TEMPORARY routes for Phase 6 proof-of-life testing. Auth-protected.
// Not visible to guests.
//
// REMOVE both routes and this file entirely in Phase 7 clean-up after
// Phase 6 proof-of-life is complete.
// -----------------------------------------------------------------------------

// Send a test outbound email to a guest.
Route::get('/dev/guest-email-test', [TemporarySendTestEmailController::class, 'create'])
    ->middleware(['auth'])
    ->name('dev.guest-email-test.create');

Route::post('/dev/guest-email-test', [TemporarySendTestEmailController::class, 'store'])
    ->middleware(['auth'])
    ->name('dev.guest-email-test.store');

// Inspect all guest_emails rows — verify inbound, outbound, and bounce results.
Route::get('/dev/guest-emails', [TemporaryGuestEmailsController::class, 'index'])
    ->middleware(['auth'])
    ->name('dev.guest-emails.index');