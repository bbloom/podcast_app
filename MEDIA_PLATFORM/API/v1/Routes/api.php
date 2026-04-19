<?php

// =============================================================================
// Routes: Public API v1
//
// All routes here are stateless API routes — no sessions, no CSRF.
// Two middleware layers protect every route:
//   1. CheckApiEnabled    — returns 503 if the API is switched off
//   2. AuthenticateApiClient — validates RequestingDomain header + bearer token
//
// These routes are loaded via routes/api.php, which is registered in
// bootstrap/app.php alongside routes/web.php.
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\API\v1\Controllers\DigestApiController;
use MediaPlatform\API\v1\Controllers\PodcastEpisodesController;
use MediaPlatform\API\v1\Middleware\AuthenticateApiClient;
use MediaPlatform\API\v1\Middleware\CheckApiEnabled;

Route::middleware([CheckApiEnabled::class, AuthenticateApiClient::class])
    ->group(function () {

        // Returns all published episodes, all enabled guests, and all enabled
        // sponsors in a single JSON payload for the Astro static site build.
        Route::get('/v1/podcastepisodes', PodcastEpisodesController::class)
            ->name('api.v1.podcast_episodes');

        // Returns all retained published digests for a given list.
        // The list is identified by the X-Digest-List request header.
        Route::get('/v1/digests', DigestApiController::class)
            ->name('api.v1.digests');

        // Full URLs:
        //   /api/v1/podcastepisodes
        //   /api/v1/digests
        // Laravel automatically prefixes routes/api.php routes with /api.
    });