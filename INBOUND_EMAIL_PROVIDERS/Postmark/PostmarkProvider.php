<?php

// =============================================================================
// PostmarkProvider
//
// Adapter implementing InboundEmailProviderInterface for Postmark.
//
// Responsibilities:
//   - Verify Basic Auth webhook credentials against POSTMARK_WEBHOOK_USER
//     and POSTMARK_WEBHOOK_PASSWORD from .env. Reject with HTTP 403 on mismatch.
//   - Parse Postmark's pre-parsed JSON payload.
//   - Return ParsedInboundEmail for inbound messages.
//   - Return BounceNotification for bounce notifications (Phase 5).
//   - Return null for unrecognised or non-actionable payloads.
//
// Postmark sends webhook credentials via HTTP Basic Auth embedded in the
// webhook URL: https://user:password@yourdomain.com/webhooks/postmark/inbound
// Laravel/Symfony surfaces these via $request->getUser() and $request->getPassword().
//
// Path: INBOUND_EMAIL_PROVIDERS/Postmark/
// =============================================================================

namespace InboundEmailProviders\Postmark;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InboundEmail\Contracts\InboundEmailProviderInterface;
use InboundEmail\ValueObjects\BounceNotification;
use InboundEmail\ValueObjects\ParsedInboundEmail;

class PostmarkProvider implements InboundEmailProviderInterface
{
    /**
     * Handle an incoming HTTP POST from Postmark.
     *
     * Verifies credentials, determines payload type, and returns a normalised
     * value object. Returns null if credentials fail or payload is unrecognised.
     */
    public function handle(Request $request): ParsedInboundEmail|BounceNotification|null
    {
        if (! $this->credentialsAreValid($request)) {
            abort(403);
        }

        $payload = $request->json()->all();

        if ($this->isInboundEmail($payload)) {
            return $this->parseInboundEmail($payload);
        }

        if ($this->isBounceNotification($payload)) {
            return $this->parseBounceNotification($payload);
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Credential verification
    // -------------------------------------------------------------------------

    /**
     * Verify the Basic Auth credentials Postmark sends with every webhook POST.
     * Simple constant-time string comparison — no cryptographic ceremony needed.
     */
    private function credentialsAreValid(Request $request): bool
    {
        return hash_equals(config('services.postmark_webhook.user'),     (string) $request->getUser())
            && hash_equals(config('services.postmark_webhook.password'), (string) $request->getPassword());
    }

    // -------------------------------------------------------------------------
    // Payload type detection
    // -------------------------------------------------------------------------

    /**
     * Postmark inbound payloads always contain a 'FromFull' object and 'StrippedTextReply'.
     */
    private function isInboundEmail(array $payload): bool
    {
        return isset($payload['FromFull'], $payload['TextBody']);
    }

    /**
     * Postmark bounce payloads always contain a 'Type' and 'Email' at the top level,
     * and lack the inbound-specific 'FromFull' key.
     */
    private function isBounceNotification(array $payload): bool
    {
        return isset($payload['Type'], $payload['Email']) && ! isset($payload['FromFull']);
    }

    // -------------------------------------------------------------------------
    // Inbound email parsing
    // -------------------------------------------------------------------------

    /**
     * Parse a Postmark inbound webhook payload into a ParsedInboundEmail.
     *
     * Uses FromFull.Email for the sender address — more reliable than parsing
     * the raw From string which may include a display name.
     *
     * Message-ID and In-Reply-To are extracted from the Headers array by name.
     * Values are stored without angle brackets for consistent DB matching.
     */
    private function parseInboundEmail(array $payload): ParsedInboundEmail
    {
        return ParsedInboundEmail::make(
            fromAddress:       $payload['FromFull']['Email'] ?? $payload['From'],
            subject:           $payload['Subject']           ?? '',
            strippedReplyBody: $payload['StrippedTextReply'] ?? '',
            fullTextBody:      $payload['TextBody']          ?? '',
            messageId:         $this->extractHeader($payload, 'Message-ID'),
            inReplyTo:         $this->extractHeader($payload, 'In-Reply-To') ?: null,
            receivedAt:        Carbon::now(),
        );
    }

    // -------------------------------------------------------------------------
    // Bounce notification parsing (Phase 5)
    // -------------------------------------------------------------------------

    /**
     * Parse a Postmark bounce webhook payload into a BounceNotification.
     */
    private function parseBounceNotification(array $payload): BounceNotification
    {
        return BounceNotification::make(
            bouncedAddress: $payload['Email']       ?? '',
            bounceType:     $payload['Type']        ?? '',
            description:    $payload['Description'] ?? '',
            occurredAt:     isset($payload['BouncedAt'])
                                ? Carbon::parse($payload['BouncedAt'])
                                : Carbon::now(),
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Extract a header value from Postmark's Headers array by header name.
     * Returns the value without angle brackets, or an empty string if not found.
     *
     * Postmark surfaces headers as: [['Name' => 'Message-ID', 'Value' => '<id@domain>'], ...]
     */
    private function extractHeader(array $payload, string $name): string
    {
        $headers = $payload['Headers'] ?? [];

        foreach ($headers as $header) {
            if (isset($header['Name'], $header['Value']) && $header['Name'] === $name) {
                return trim($header['Value'], '<>');
            }
        }

        return '';
    }
}