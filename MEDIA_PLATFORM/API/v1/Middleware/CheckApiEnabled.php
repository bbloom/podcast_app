<?php

namespace MediaPlatform\API\v1\Middleware;

use Closure;
use Illuminate\Http\Request;
use MediaPlatform\API\v1\Models\ApiControl;
use Symfony\Component\HttpFoundation\Response;

class CheckApiEnabled
{
    /**
     * Reject the request with 503 if the API is currently switched off.
     * This check runs before authentication — no point validating a token
     * if the API is closed entirely.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! ApiControl::instance()->is_enabled) {
            return response()->json([
                'error'  => 'Service unavailable.',
                'reason' => 'The API is currently disabled.',
            ], 503);
        }

        return $next($request);
    }
}