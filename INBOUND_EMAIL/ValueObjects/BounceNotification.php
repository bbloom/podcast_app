<?php

// =============================================================================
// BounceNotification
//
// Immutable value object representing a delivery failure notification.
// Populated from Postmark's bounce webhook payload.
//
// Fields:
//   bouncedAddress  — the email address that bounced
//   bounceType      — Postmark bounce type string: 'HardBounce', 'SoftBounce',
//                     'SpamComplaint', etc.
//   description     — human-readable description of the failure
//   occurredAt      — when the bounce was recorded
//
// Constructed only via the named static factory — never via new directly.
//
// Path: INBOUND_EMAIL/ValueObjects/
// =============================================================================

namespace InboundEmail\ValueObjects;

use Illuminate\Support\Carbon;

class BounceNotification
{
    // -------------------------------------------------------------------------
    // Constructor — private. Use make() below.
    // -------------------------------------------------------------------------

    private function __construct(
        private readonly string $bouncedAddress,
        private readonly string $bounceType,
        private readonly string $description,
        private readonly Carbon $occurredAt,
    ) {}

    // -------------------------------------------------------------------------
    // Named static factory
    // -------------------------------------------------------------------------

    /**
     * Create a BounceNotification from a provider-normalised set of fields.
     */
    public static function make(
        string $bouncedAddress,
        string $bounceType,
        string $description,
        Carbon $occurredAt,
    ): self {
        return new self(
            bouncedAddress: $bouncedAddress,
            bounceType:     $bounceType,
            description:    $description,
            occurredAt:     $occurredAt,
        );
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * The email address that bounced.
     */
    public function bouncedAddress(): string
    {
        return $this->bouncedAddress;
    }

    /**
     * The Postmark bounce type. Common values: 'HardBounce', 'SoftBounce', 'SpamComplaint'.
     */
    public function bounceType(): string
    {
        return $this->bounceType;
    }

    /**
     * Human-readable description of the delivery failure.
     */
    public function description(): string
    {
        return $this->description;
    }

    /**
     * When the bounce was recorded by the provider.
     */
    public function occurredAt(): Carbon
    {
        return $this->occurredAt;
    }
}