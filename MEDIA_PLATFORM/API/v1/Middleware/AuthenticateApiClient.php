<?php

namespace MediaPlatform\API\v1\Middleware;

use Closure;
use Illuminate\Http\Request;
use MediaPlatform\API\v1\Models\ApiClient;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiClient
{
    /**
     * Authenticate the incoming API request.
     *
     * Two checks must both pass:
     *   1. The RequestingDomain header must match an active api_clients record.
     *   2. The Authorization: Bearer token must verify against that client's hash.
     *
     * If either check fails, a 403 is returned. We deliberately give no detail
     * about which check failed — attackers should not know how close they got.
     *
     * When AUTHENTICATE_API_CLIENT_DEBUG is enabled, the reason for failure
     * is included in the response to aid development debugging.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $domain      = $request->header('RequestingDomain');
        $bearerToken = $request->bearerToken();

        // Both headers must be present.
        if (! $domain || ! $bearerToken) {
            return $this->forbidden('Missing RequestingDomain or Authorization header.');
        }

        // Domain must match an active client.
        $client = ApiClient::findActiveByDomain($domain);

        if (! $client) {
            return $this->forbidden("No active api_clients record found for domain: {$domain}");
        }

        // Bearer token must verify against the stored hash.
        if (! $client->verifyToken($bearerToken)) {
            return $this->forbidden("Bearer token verification failed for domain: {$domain}");
        }

        // Record the successful request timestamp.
        $client->touchLastUsed();

        return $next($request);
    }

    /**
     * Return a 403 response.
     * In debug mode, include the reason. In production, stay intentionally vague.
     */
    private function forbidden(string $reason = 'Forbidden.'): Response
    {
        $body = ['error' => 'Forbidden.'];

        if (config('admin.authenticate_api_client_debug')) {
            $body['reason'] = $reason;
        }

        return response()->json($body, 403);
    }
}