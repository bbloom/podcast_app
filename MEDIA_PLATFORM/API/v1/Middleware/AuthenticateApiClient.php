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
     */
    public function handle(Request $request, Closure $next): Response
    {
        $domain      = $request->header('RequestingDomain');
        $bearerToken = $request->bearerToken();

        // Both headers must be present.
        if (! $domain || ! $bearerToken) {
            return $this->forbidden();
        }

        // Domain must match an active client.
        $client = ApiClient::findActiveByDomain($domain);

        if (! $client) {
            return $this->forbidden();
        }

        // Bearer token must verify against the stored hash.
        if (! $client->verifyToken($bearerToken)) {
            return $this->forbidden();
        }

        // Record the successful request timestamp.
        $client->touchLastUsed();

        return $next($request);
    }

    /**
     * Return a generic 403 response.
     * Intentionally vague — do not reveal which check failed.
     */
    private function forbidden(): Response
    {
        return response()->json([
            'error' => 'Forbidden.',
        ], 403);
    }
}