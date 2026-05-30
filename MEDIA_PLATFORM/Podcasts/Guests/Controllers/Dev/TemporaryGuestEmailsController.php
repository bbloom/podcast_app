<?php

// =============================================================================
// TemporaryGuestEmailsController — TEMPORARY DEV SCAFFOLDING
//
// Displays all guest_emails rows with guest details for Phase 6 proof-of-life
// verification. Auth-protected — not visible to guests.
//
// Route: GET /dev/guest-emails (auth middleware only)
//
// REMOVE in Phase 7 clean-up after Phase 6 proof-of-life is complete.
//
// Path: MEDIA_PLATFORM/Podcasts/Guests/Controllers/Dev/
// =============================================================================

namespace MediaPlatform\Podcasts\Guests\Controllers\Dev;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Guests\Models\GuestEmail;

class TemporaryGuestEmailsController extends Controller
{
    /**
     * Display all guest email rows, newest first, with guest details eager-loaded.
     */
    public function index()
    {
        $emails = GuestEmail::with('guest')
            ->orderByDesc('id')
            ->get();

        return view('media_platform.podcasts.guests.dev.guest_emails', compact('emails'));
    }
}