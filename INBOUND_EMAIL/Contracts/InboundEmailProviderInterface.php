<?php

// =============================================================================
// InboundEmailProviderInterface
//
// Contract that every inbound email provider adapter must implement.
//
// The provider receives a raw HTTP request from the email provider, verifies
// its authenticity, parses the payload, and returns a normalised value object
// ready for the provider-agnostic core to act on.
//
// Returns:
//   ParsedInboundEmail   — a verified, parsed inbound message
//   BounceNotification   — a delivery failure notification
//   null                 — non-actionable request (bad signature, unknown type)
//
// Path: INBOUND_EMAIL/Contracts/
// =============================================================================

namespace InboundEmail\Contracts;

use Illuminate\Http\Request;
use InboundEmail\ValueObjects\BounceNotification;
use InboundEmail\ValueObjects\ParsedInboundEmail;

interface InboundEmailProviderInterface
{
    /**
     * Handle an incoming HTTP request from the email provider.
     * Verify credentials, parse payload, return normalised value object or null.
     */
    public function handle(Request $request): ParsedInboundEmail|BounceNotification|null;
}