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
use InboundEmail\ValueObjects\BounceNotification;
use InboundEmail\ValueObjects\ParsedInboundEmail;
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
    /**
     * Receive an inbound reply from a guest and store it in guest_emails.
     *
     * Matches the sender against podcast_guests.email_address to identify the guest.
     * Matches in_reply_to against a prior outbound message_id for thread correlation.
     * Returns the stored GuestEmail, or null if the sender is not a known guest.
     *
     * Message-ID and In-Reply-To values arrive from PostmarkProvider already
     * stripped of angle brackets — stored as-is for consistent DB matching.
     */
    public function receive(ParsedInboundEmail $inbound): ?GuestEmail
    {
        $guest = PodcastGuest::where('email_address', $inbound->fromAddress())->first();

        if (! $guest) {
            // Unknown sender — not a known guest. Silently discard.
            // See INBOUND_EMAILS_FEATURES.md §1 for deferred handling options.
            return null;
        }

        return GuestEmail::create([
            'podcast_guest_id' => $guest->id,
            'direction'        => GuestEmailDirection::Inbound,
            'subject'          => $inbound->subject(),
            'body_stripped'    => $inbound->strippedReplyBody(),
            'body_full'        => $inbound->fullTextBody(),
            'message_id'       => $inbound->messageId(),
            'in_reply_to'      => $inbound->inReplyTo() ?: null,
            'sent_at'          => null,
            'received_at'      => $inbound->receivedAt(),
        ]);
    }

    /**
     * Handle a bounce notification from Postmark.
     *
     * All bounce types are stored in guest_emails for a complete correspondence history.
     * HardBounce additionally flags the guest record — the address is permanently undeliverable.
     * SoftBounce and SpamComplaint are stored only — the guest record is not flagged.
     *
     * Returns null silently if the bounced address does not match a known guest.
     */
    public function handleBounce(BounceNotification $bounce): ?GuestEmail
    {
        $guest = PodcastGuest::where('email_address', $bounce->bouncedAddress())->first();

        if (! $guest) {
            return null;
        }

        $direction = match (true) {
            str_contains($bounce->bounceType(), 'Spam') => GuestEmailDirection::SpamComplaint,
            str_starts_with($bounce->bounceType(), 'Soft') => GuestEmailDirection::SoftBounce,
            default => GuestEmailDirection::HardBounce,
        };

        if ($direction === GuestEmailDirection::HardBounce) {
            $guest->update([
                'email_bounced'    => true,
                'email_bounced_at' => $bounce->occurredAt(),
            ]);
        }

        return GuestEmail::create([
            'podcast_guest_id' => $guest->id,
            'direction'        => $direction,
            'subject'          => $bounce->bounceType(),
            'body_stripped'    => $bounce->description(),
            'body_full'        => $bounce->description(),
            'message_id'       => Str::uuid() . '@bobbloominterviews.com',
            'in_reply_to'      => null,
            'sent_at'          => null,
            'received_at'      => $bounce->occurredAt(),
        ]);
    }

    // -------------------------------------------------------------------------

    /**
     * Send an email to a guest and store the outbound record in guest_emails.
     *
     * @param  string        $subject    Email subject line.
     * @param  string        $body       Email body text.
     * @param  string|null   $inReplyTo  message_id of the prior email in this thread,
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