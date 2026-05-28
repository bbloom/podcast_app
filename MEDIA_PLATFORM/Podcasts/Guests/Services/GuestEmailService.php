<?php

// =============================================================================
// GuestEmailService
//
// Sends an email to a podcast guest and persists the outbound record.
//
// Generates a unique Message-ID before sending, passes it to GuestEmailMailable
// so it is set as the email's Message-ID header, and stores it in guest_emails
// so inbound replies can be correlated via their In-Reply-To header.
//
// This is the single point of entry for all outbound guest email. Future
// callers (milestone triggers, reply-from-app UI) should go through here
// rather than constructing GuestEmailMailable directly.
//
// Path: MEDIA_PLATFORM/Podcasts/Guests/Services/
// =============================================================================

namespace MediaPlatform\Podcasts\Guests\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use MediaPlatform\Podcasts\Guests\Enums\GuestEmailDirection;
use MediaPlatform\Podcasts\Guests\Mail\GuestEmailMailable;
use MediaPlatform\Podcasts\Guests\Models\GuestEmail;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;

class GuestEmailService
{
    /**
     * Send an email to a guest and store the outbound record in guest_emails.
     *
     * @param  PodcastGuest  $guest      The recipient.
     * @param  string        $subject    Email subject line.
     * @param  string        $body       Email body text.
     * @param  string|null   $inReplyTo  Message-ID of the prior email in this thread,
     *                                   without angle brackets. Null for first contact.
     * @return GuestEmail                The persisted outbound record.
     */
    public function send(
        PodcastGuest $guest,
        string $subject,
        string $body,
        ?string $inReplyTo = null,
    ): GuestEmail {
        $messageId = Str::uuid() . '@bobbloominterviews.com';

        Mail::to($guest->email_address)
            ->send(new GuestEmailMailable(
                guest:        $guest,
                emailSubject: $subject,
                body:         $body,
                messageId:    $messageId,
                inReplyTo:    $inReplyTo,
            ));

        return GuestEmail::create([
            'podcast_guest_id' => $guest->id,
            'direction'        => GuestEmailDirection::Outbound,
            'subject'          => $subject,
            'body_stripped'    => $body,
            'body_full'        => $body,
            'message_id'       => $messageId,
            'in_reply_to'      => null,
            'sent_at'          => now(),
        ]);
    }
}