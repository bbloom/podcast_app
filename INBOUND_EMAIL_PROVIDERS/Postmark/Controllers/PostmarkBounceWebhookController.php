<?php

// =============================================================================
// PostmarkBounceWebhookController
//
// Receives bounce and spam complaint webhook POSTs from Postmark.
//
// Delegates credential verification and payload parsing to PostmarkProvider,
// then passes the normalised BounceNotification to GuestEmailService::handleBounce()
// for guest flagging and persistence.
//
// Postmark expects an HTTP 200 response to confirm receipt. A 403 stops
// retries immediately — used by PostmarkProvider on credential failure.
//
// Route: POST /webhooks/postmark/bounce (no auth middleware, CSRF exempt)
//
// Path: INBOUND_EMAIL_PROVIDERS/Postmark/Controllers/
// =============================================================================

namespace InboundEmailProviders\Postmark\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InboundEmail\ValueObjects\BounceNotification;
use InboundEmailProviders\Postmark\PostmarkProvider;
use MediaPlatform\Podcasts\Guests\Services\GuestEmailService;

class PostmarkBounceWebhookController extends Controller
{
    /**
     * Handle a bounce webhook POST from Postmark.
     */
    public function __invoke(
        Request $request,
        PostmarkProvider $provider,
        GuestEmailService $service,
    ): Response {
        $result = $provider->handle($request);

        if ($result instanceof BounceNotification) {
            $service->handleBounce($result);
        }

        return response()->noContent();
    }
}