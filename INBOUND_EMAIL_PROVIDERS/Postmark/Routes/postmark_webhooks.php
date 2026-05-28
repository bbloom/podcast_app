<?php

use InboundEmailProviders\Postmark\Controllers\PostmarkInboundWebhookController;

// -----------------------------------------------------------------------------
// Postmark Webhooks
//
// No auth middleware — Postmark POSTs from outside the application.
// CSRF exempt — configured in bootstrap/app.php validateCsrfTokens().
// Credentials are verified inside PostmarkProvider via Basic Auth header.
//
// Postmark expects HTTP 200 on success. A 403 stops retries immediately.
// A non-200/403 response triggers Postmark's retry schedule (up to 10 retries).
//
// POST /webhooks/postmark/bounce is reserved for Phase 5 bounce handling.
// -----------------------------------------------------------------------------

Route::post('/webhooks/postmark/inbound', PostmarkInboundWebhookController::class)
    ->name('webhooks.postmark.inbound');