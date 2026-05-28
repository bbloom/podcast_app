<?php

use MediaPlatform\Podcasts\Guests\Controllers\Dev\TemporarySendTestEmailController;

// -----------------------------------------------------------------------------
// Dev — Send Test Email
//
// TEMPORARY scaffolding for proving outbound email works end-to-end in production.
// Auth-protected — not visible to guests.
//
// REMOVE in Phase 7 clean-up after Phase 6 proof-of-life is complete.
// -----------------------------------------------------------------------------

Route::get('/dev/guest-email-test', [TemporarySendTestEmailController::class, 'create'])
    ->middleware(['auth'])
    ->name('dev.guest-email-test.create');

Route::post('/dev/guest-email-test', [TemporarySendTestEmailController::class, 'store'])
    ->middleware(['auth'])
    ->name('dev.guest-email-test.store');