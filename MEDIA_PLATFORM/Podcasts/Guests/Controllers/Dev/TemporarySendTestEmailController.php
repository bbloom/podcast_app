<?php

// =============================================================================
// TemporarySendTestEmailController — TEMPORARY DEV SCAFFOLDING
//
// Provides a simple auth-protected form to send a real email to any enabled
// guest from the production app. Used to prove outbound email works end-to-end:
// email arrives in inbox, passes DKIM/SPF, and a guest_emails row is created
// with the correct message_id.
//
// Routes: GET|POST /dev/guest-email-test (auth middleware only)
//
// REMOVE in Phase 7 clean-up after Phase 6 proof-of-life is complete.
//
// Path: MEDIA_PLATFORM/Podcasts/Guests/Controllers/Dev/
// =============================================================================

namespace MediaPlatform\Podcasts\Guests\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use Symfony\Component\Mailer\Exception\TransportException;
use MediaPlatform\Podcasts\Guests\Services\GuestEmailService;

class TemporarySendTestEmailController extends Controller
{
    /**
     * Show the test email send form.
     * Passes all enabled guests to the view for the recipient dropdown.
     */
    public function create()
    {
        $guests = PodcastGuest::where('enabled', true)
            ->orderBy('full_name')
            ->get();

        return view('media_platform.podcasts.guests.dev.send_test_email', compact('guests'));
    }

    /**
     * Validate, send the email via GuestEmailService, and redirect with the Message-ID.
     * GuestEmailService is injected into the method — used in one method only.
     */
    public function store(Request $request, GuestEmailService $service): RedirectResponse
    {
        $validated = $request->validate([
            'podcast_guest_id' => ['required', 'integer', 'exists:podcast_guests,id'],
            'subject'          => ['required', 'string', 'max:255'],
            'body'             => ['required', 'string'],
        ]);

        $guest = PodcastGuest::findOrFail($validated['podcast_guest_id']);

        try {
            $email = $service->send($guest, $validated['subject'], $validated['body']);
        } catch (TransportException $e) {
            return redirect()
                ->route('dev.guest-email-test.create')
                ->withInput()
                ->with('error', 'Postmark error: ' . $e->getMessage());
        }

        return redirect()
            ->route('dev.guest-email-test.create')
            ->with('success', 'Email sent to ' . $guest->full_name . '. Message-ID: ' . $email->message_id);
    }
}