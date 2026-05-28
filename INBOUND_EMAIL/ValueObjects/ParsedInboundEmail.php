<?php

// =============================================================================
// ParsedInboundEmail
//
// Immutable value object representing a verified, parsed inbound email.
// Populated from Postmark's pre-parsed JSON payload — no raw MIME parsing
// required. Carries only what the app needs to store and correlate the reply.
//
// Fields:
//   fromAddress        — the guest's email address (From header)
//   subject            — email subject line
//   strippedReplyBody  — Postmark's StrippedTextReply: only what the guest wrote
//   fullTextBody       — Postmark's TextBody: full text including quoted history
//   messageId          — RFC 2822 Message-ID of the incoming message
//   inReplyTo          — In-Reply-To header value; null if not a reply
//   receivedAt         — when the message was received
//
// Constructed only via the named static factory — never via new directly.
//
// Path: INBOUND_EMAIL/ValueObjects/
// =============================================================================

namespace InboundEmail\ValueObjects;

use Illuminate\Support\Carbon;

class ParsedInboundEmail
{
    // -------------------------------------------------------------------------
    // Constructor — private. Use make() below.
    // -------------------------------------------------------------------------

    private function __construct(
        private readonly string  $fromAddress,
        private readonly string  $subject,
        private readonly string  $strippedReplyBody,
        private readonly string  $fullTextBody,
        private readonly string  $messageId,
        private readonly ?string $inReplyTo,
        private readonly Carbon  $receivedAt,
    ) {}

    // -------------------------------------------------------------------------
    // Named static factory
    // -------------------------------------------------------------------------

    /**
     * Create a ParsedInboundEmail from a provider-normalised set of fields.
     */
    public static function make(
        string  $fromAddress,
        string  $subject,
        string  $strippedReplyBody,
        string  $fullTextBody,
        string  $messageId,
        ?string $inReplyTo,
        Carbon  $receivedAt,
    ): self {
        return new self(
            fromAddress:       $fromAddress,
            subject:           $subject,
            strippedReplyBody: $strippedReplyBody,
            fullTextBody:      $fullTextBody,
            messageId:         $messageId,
            inReplyTo:         $inReplyTo,
            receivedAt:        $receivedAt,
        );
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * The sender's email address.
     */
    public function fromAddress(): string
    {
        return $this->fromAddress;
    }

    /**
     * The email subject line.
     */
    public function subject(): string
    {
        return $this->subject;
    }

    /**
     * The reply body only — Postmark's StrippedTextReply.
     * Contains only what the guest actually wrote; quoted history is stripped.
     */
    public function strippedReplyBody(): string
    {
        return $this->strippedReplyBody;
    }

    /**
     * The full plain-text body including quoted history — Postmark's TextBody.
     * Retained as a fallback in case StrippedTextReply misfires on unusual clients.
     */
    public function fullTextBody(): string
    {
        return $this->fullTextBody;
    }

    /**
     * The RFC 2822 Message-ID of this incoming message.
     */
    public function messageId(): string
    {
        return $this->messageId;
    }

    /**
     * The In-Reply-To header value.
     * Used to correlate this reply with a previously sent outbound email.
     * Null if this is not a reply to a known message.
     */
    public function inReplyTo(): ?string
    {
        return $this->inReplyTo;
    }

    /**
     * When the message was received.
     */
    public function receivedAt(): Carbon
    {
        return $this->receivedAt;
    }
}