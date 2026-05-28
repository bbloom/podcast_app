<?php

// =============================================================================
// PostmarkProvider
//
// Adapter implementing InboundEmailProviderInterface for Postmark.
//
// Responsibilities (implemented in Phases 4 and 5):
//   - Verify the webhook credentials from the Authorization header
//   - Parse the Postmark JSON payload
//   - Determine message type (inbound email or bounce notification)
//   - Return a normalised ParsedInboundEmail or BounceNotification
//
// Phase 2: Skeleton only — autoloadable, interface-conforming, no logic.
//
// Path: INBOUND_EMAIL_PROVIDERS/Postmark/
// =============================================================================

namespace InboundEmailProviders\Postmark;

use Illuminate\Http\Request;
use InboundEmail\Contracts\InboundEmailProviderInterface;
use InboundEmail\ValueObjects\BounceNotification;
use InboundEmail\ValueObjects\ParsedInboundEmail;

class PostmarkProvider implements InboundEmailProviderInterface
{
    /**
     * Handle an incoming HTTP POST from Postmark.
     * Phase 2: not yet implemented — returns null.
     */
    public function handle(Request $request): ParsedInboundEmail|BounceNotification|null
    {
        return null;
    }
}