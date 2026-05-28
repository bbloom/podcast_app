<?php

// =============================================================================
// PostmarkInboundWebhookController
//
// Receives inbound email webhook POSTs from Postmark.
//
// Delegates credential verification and payload parsing to PostmarkProvider,
// then passes the normalised ParsedInboundEmail to GuestEmailService::receive()
// for guest matching and persistence.
//
// Postmark expects an HTTP 200 response to confirm receipt. Any non-200
// response triggers Postmark's retry schedule (up to 10 retries). A 403
// stops retries immediately — used by PostmarkProvider on credential failure.
//
// Route: POST /webhooks/postmark/inbound (no auth middleware, CSRF exempt)
//
// Path: INBOUND_EMAIL_PROVIDERS/Postmark/Controllers/
// =============================================================================

namespace InboundEmailProviders\Postmark\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InboundEmail\ValueObjects\ParsedInboundEmail;
use InboundEmailProviders\Postmark\PostmarkProvider;
use MediaPlatform\Podcasts\Guests\Services\GuestEmailService;

class PostmarkInboundWebhookController extends Controller
{
    /**
     * Handle an inbound email webhook POST from Postmark.
     *
     * PostmarkProvider and GuestEmailService are injected into the method —
     * each is used in one method only.
     */
    public function __invoke(
        Request $request,
        PostmarkProvider $provider,
        GuestEmailService $service,
    ): Response {
        $result = $provider->handle($request);

        if ($result instanceof ParsedInboundEmail) {
            $service->receive($result);
        }

        return response()->noContent();
    }
}