<?php

namespace MediaPlatform\Digest\ContentSources\OutputDestinations\Services;

use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WordPressService — publishes digest posts via the WordPress REST API.
 *
 * AUTHENTICATION
 * ──────────────
 * Uses WordPress Application Passwords (introduced in WP 5.6) with HTTP Basic Auth.
 * The username is the WP login username; the app password is the generated
 * Application Password (format: xxxx xxxx xxxx xxxx xxxx xxxx).
 * Spaces in the app password are fine — WordPress strips them on its end.
 *
 * To generate an Application Password in WordPress:
 *   WP Admin → Users → Edit User → Application Passwords → Add New
 *
 * ENDPOINT
 * ────────
 * POST {wordpress_url}/wp-json/wp/v2/posts
 *
 * FIELDS SENT
 * ───────────
 * - title       : string — list name + date, used as the post title
 * - slug        : string — the same {list-slug}-digest-{date} pattern as SFTP files
 * - content     : string — full digest HTML rendered by digest-wp.blade.php
 * - excerpt     : string — plain-text summary line (e.g. "12 items from 3 sources")
 * - status      : string — from OutputDestination.wordpress_post_status ('publish'/'draft'/'private')
 * - categories  : int[]  — from OutputDestination.wordpressCategoryIdsArray()
 * - tags        : int[]  — from OutputDestination.wordpressTagIdsArray()
 * - date        : string — ISO 8601 datetime of the digest run
 *
 * Empty arrays for categories/tags are sent explicitly so that WordPress can
 * clear any default taxonomy assignments. If you want WP to keep its defaults,
 * remove those keys from the payload.
 *
 * RETURN VALUE
 * ────────────
 * Returns ['success' => true, 'post_id' => int, 'url' => string] on success,
 * or ['success' => false, 'message' => string] on failure.
 */
class WordPressService
{
    /**
     * Publish a digest as a new WordPress post.
     *
     * @param  OutputDestination  $dest         The WordPress output destination with credentials.
     * @param  string             $title        The post title (list name + date).
     * @param  string             $slug         The desired URL slug (no extension).
     * @param  string             $htmlContent  The rendered digest HTML (post body).
     * @param  string             $excerpt      Short plain-text summary for the excerpt field.
     * @param  Carbon             $date         The date/time to associate with the post.
     */
    public function createPost(
        OutputDestination $dest,
        string            $title,
        string            $slug,
        string            $htmlContent,
        string            $excerpt,
        Carbon            $date,
    ): array {
        // ── Build the REST API URL ────────────────────────────────────────────
        // Strip trailing slash from the base URL to prevent double-slashes.
        $apiUrl = rtrim($dest->wordpress_url, '/') . '/wp-json/wp/v2/posts';

        // ── Build the post payload ────────────────────────────────────────────
        // All taxonomy fields are always included (even as empty arrays) so that
        // WordPress does not silently inherit default category assignments.
        $payload = [
            'title'      => $title,
            'slug'       => $slug,
            'content'    => $htmlContent,
            'excerpt'    => $excerpt,
            'status'     => $dest->wordpress_post_status ?? 'publish',
            'categories' => $dest->wordpressCategoryIdsArray(),
            'tags'       => $dest->wordpressTagIdsArray(),
            // ISO 8601 format without timezone suffix — WP interprets this as
            // the site's local timezone. Use date_gmt if you prefer UTC.
            'date'       => $date->format('Y-m-d\TH:i:s'),
        ];

        Log::info('WordPressService: Posting digest to WordPress.', [
            'url'    => $apiUrl,
            'title'  => $title,
            'slug'   => $slug,
            'status' => $payload['status'],
        ]);

        try {
            // ── Make the authenticated HTTP POST request ───────────────────────
            // withBasicAuth() base64-encodes "username:password" for the
            // Authorization header — the standard HTTP Basic Auth mechanism.
            // The wordpress_app_password is already decrypted by the model's cast.
            $response = Http::withBasicAuth(
                    $dest->wordpress_username,
                    $dest->wordpress_app_password
                )
                ->acceptJson()
                ->post($apiUrl, $payload);

            // ── Handle the response ───────────────────────────────────────────

            if ($response->successful()) {
                $body = $response->json();

                Log::info('WordPressService: Post created successfully.', [
                    'post_id' => $body['id'] ?? null,
                    'url'     => $body['link'] ?? null,
                ]);

                return [
                    'success' => true,
                    'post_id' => $body['id'] ?? null,
                    'url'     => $body['link'] ?? null,
                ];
            }

            // ── Decode WordPress error responses ──────────────────────────────
            // WordPress REST API errors return JSON with 'code' and 'message' fields.
            $errorBody = $response->json();
            $errorCode = $errorBody['code']    ?? 'unknown';
            $errorMsg  = $errorBody['message'] ?? $response->body();

            Log::error('WordPressService: API returned an error.', [
                'status'     => $response->status(),
                'error_code' => $errorCode,
                'message'    => $errorMsg,
            ]);

            // ── Human-readable errors for common status codes ─────────────────
            $humanMessage = match (true) {
                $response->status() === 401 => 'Authentication failed. Check the WordPress username and Application Password.',
                $response->status() === 403 => 'Permission denied. The WordPress user may not have permission to create posts.',
                $response->status() === 404 => 'WordPress REST API not found. Check the site URL and confirm the REST API is enabled.',
                $response->status() === 422 => "WordPress rejected the post data: {$errorMsg}",
                default                     => "WordPress returned HTTP {$response->status()}: {$errorMsg}",
            };

            return [
                'success' => false,
                'message' => $humanMessage,
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // ── Network-level failure (DNS, connection refused, timeout) ───────
            Log::error('WordPressService: Could not connect to WordPress site.', [
                'url'   => $apiUrl,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Could not connect to the WordPress site. Check the URL and server availability.',
            ];

        } catch (\Throwable $e) {
            Log::error('WordPressService: Unexpected error.', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'An unexpected error occurred while publishing to WordPress: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test WordPress credentials by fetching the REST API root endpoint.
     *
     * This is a lightweight authentication check — it does not create any content.
     * A 200 response with valid JSON confirms the URL is reachable and the API is live.
     * A 401 response confirms the URL is correct but credentials are wrong.
     *
     * Returns ['success' => true] or ['success' => false, 'message' => '...']
     */
    public function testConnection(
        string $wordpressUrl,
        string $username,
        string $appPassword,
    ): array {
        // Use the /wp-json/wp/v2/users/me endpoint — it requires auth, so it
        // distinguishes "reachable but wrong credentials" from "reachable + valid".
        $testUrl = rtrim($wordpressUrl, '/') . '/wp-json/wp/v2/users/me';

        try {
            $response = Http::withBasicAuth($username, $appPassword)
                ->acceptJson()
                ->get($testUrl);

            if ($response->successful()) {
                return ['success' => true];
            }

            return match ($response->status()) {
                401 => [
                    'success' => false,
                    'message' => 'Authentication failed. Check the username and Application Password.',
                ],
                403 => [
                    'success' => false,
                    'message' => 'Connected but permission denied. Ensure Application Passwords are enabled on this site.',
                ],
                404 => [
                    'success' => false,
                    'message' => 'WordPress REST API not found at this URL. Check the site URL.',
                ],
                default => [
                    'success' => false,
                    'message' => "WordPress returned HTTP {$response->status()}. Check the site URL.",
                ],
            };

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'message' => 'Could not connect to the WordPress site. Check the URL and server availability.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Unexpected error: ' . $e->getMessage(),
            ];
        }
    }
}