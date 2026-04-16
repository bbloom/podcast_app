<?php

// =============================================================================
// DeployHookTriggerService
//
// Fires a single deploy hook by POSTing to its URL and records the outcome.
//
// The deploy hook URL is the authentication credential — no headers or body
// are required. The secret is baked into the URL by the hosting provider.
//
// Supports Cloudflare Pages, Netlify, and Vercel. Each provider returns a
// different response body, but all use standard HTTP status codes:
//
//   Cloudflare Pages — 200 with JSON: { "id": "build-uuid", "already_exists": bool }
//   Netlify          — 200 with minimal or empty body
//   Vercel           — 200 with minimal or empty body
//
// Regardless of provider, the outcome is always recorded on the hook:
//   - last_triggered_at  — timestamp of this attempt
//   - last_build_id      — build UUID from Cloudflare, null for others
//   - last_trigger_status — "success" or "failed"
//
// Recording failures is essential — a failed trigger requires active
// investigation and resolution. The hook record is the audit trail.
//
// Path: MEDIA_PLATFORM/StaticSiteDeployHooks/Services/
// =============================================================================

namespace MediaPlatform\StaticSiteDeployHooks\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;

class DeployHookTriggerService
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  trigger()                                                             │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Fire a deploy hook and record the outcome on the hook record.
     *
     * Always records the attempt — success or failure — so there is always
     * an audit trail on the hook. Failures are logged for visibility.
     *
     * Returns a DeployHookTriggerResult value object that the caller (UI or
     * controller) uses to render feedback to the user.
     */
    public function trigger(DeployHook $hook): DeployHookTriggerResult
    {
        try {
            $response = Http::timeout(15)->post($hook->url);
        } catch (\Throwable $e) {
            // Network-level failure — connection refused, timeout, DNS failure, etc.
            return $this->recordAndReturn(
                $hook,
                DeployHookTriggerResult::failure(
                    hook:         $hook,
                    httpStatus:   0,
                    errorMessage: 'Could not reach the hosting provider. Error: ' . $e->getMessage(),
                )
            );
        }

        // ── Non-success HTTP response ─────────────────────────────────────────
        if (! $response->successful()) {
            return $this->recordAndReturn(
                $hook,
                DeployHookTriggerResult::failure(
                    hook:         $hook,
                    httpStatus:   $response->status(),
                    errorMessage: 'The hosting provider returned HTTP ' . $response->status() . '. '
                                  . $this->extractProviderError($response),
                )
            );
        }

        // ── Parse provider-specific response body ─────────────────────────────
        [$buildId, $alreadyExists] = $this->parseResponseBody($hook, $response);

        return $this->recordAndReturn(
            $hook,
            DeployHookTriggerResult::success(
                hook:          $hook,
                httpStatus:    $response->status(),
                buildId:       $buildId,
                alreadyExists: $alreadyExists,
            )
        );
    }

    // =========================================================================
    // PRIVATE METHODS
    // =========================================================================

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  recordAndReturn()                                                     │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Persist the trigger outcome on the hook record and return the result.
     *
     * Called for both success and failure — the hook record always reflects
     * the most recent attempt regardless of outcome.
     */
    private function recordAndReturn(DeployHook $hook, DeployHookTriggerResult $result): DeployHookTriggerResult
    {
        $hook->update([
            'last_triggered_at'   => now(),
            'last_build_id'       => $result->buildId(),
            'last_trigger_status' => $result->succeeded() ? 'success' : 'failed',
        ]);

        if (! $result->succeeded()) {
            Log::warning('DeployHookTriggerService: trigger failed.', [
                'hook_id'       => $hook->id,
                'hook_label'    => $hook->label,
                'provider'      => $hook->provider->value,
                'http_status'   => $result->httpStatus(),
                'error_message' => $result->errorMessage(),
            ]);
        } else {
            Log::info('DeployHookTriggerService: trigger succeeded.', [
                'hook_id'        => $hook->id,
                'hook_label'     => $hook->label,
                'provider'       => $hook->provider->value,
                'build_id'       => $result->buildId(),
                'already_exists' => $result->alreadyExists(),
            ]);
        }

        return $result;
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  parseResponseBody()                                                   │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Parse the provider-specific response body.
     *
     * Returns [$buildId, $alreadyExists].
     *
     * Cloudflare Pages returns JSON with an "id" (build UUID) and
     * "already_exists" flag. Netlify and Vercel return minimal bodies
     * that we do not attempt to parse — buildId is null for those providers.
     */
    private function parseResponseBody(DeployHook $hook, $response): array
    {
        if ($hook->provider === DeployHookProvider::cloudflare_pages) {
            $body          = $response->json();
            $buildId       = $body['id'] ?? null;
            $alreadyExists = (bool) ($body['already_exists'] ?? false);

            return [$buildId, $alreadyExists];
        }

        // Netlify, Vercel — no meaningful body to parse.
        return [null, false];
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  extractProviderError()                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Attempt to extract a human-readable error message from a failed
     * provider response body. Falls back to an empty string if the body
     * is not JSON or contains no useful message.
     */
    private function extractProviderError($response): string
    {
        try {
            $body = $response->json();

            // Cloudflare errors: { "error": "..." }
            if (! empty($body['error'])) {
                return 'Provider error: ' . $body['error'];
            }

            // Cloudflare errors: { "errors": [{ "message": "..." }] }
            if (! empty($body['errors'][0]['message'])) {
                return 'Provider error: ' . $body['errors'][0]['message'];
            }
        } catch (\Throwable) {
            // Body is not JSON — nothing useful to extract.
        }

        return '';
    }
}