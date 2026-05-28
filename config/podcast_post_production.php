<?php

// =============================================================================
// Podcast Post Production Configuration
// =============================================================================
//
// Credentials and settings for the podcast post-production pipeline.
//
// All values are read from .env — never call env() outside of config files,
// as doing so breaks Laravel's config caching (php artisan config:cache).
//
// Cloud storage bucket names and Auphonic preset UUIDs are NOT stored here.
//
// =============================================================================

return [

    // -------------------------------------------------------------------------
    // Amazon Web Services (S3)
    // -------------------------------------------------------------------------
    //
    // Credentials for accessing AWS S3 buckets used in post-production.
    // Bucket names are defined in the Bucket enum, not here.
    //
    'aws' => [
        'access_key_id'     => env('PODCAST_POST_PRODUCTION_AWS_ACCESS_KEY_ID'),
        'secret_access_key' => env('PODCAST_POST_PRODUCTION_AWS_SECRET_ACCESS_KEY'),
        'region'            => env('PODCAST_POST_PRODUCTION_AWS_REGION'),
    ],

    // -------------------------------------------------------------------------
    // Cloudflare R2
    // -------------------------------------------------------------------------
    //
    // Credentials for accessing Cloudflare R2 buckets used in post-production.
    // R2 is S3-compatible — bucket names are shared with the S3 enum cases.
    // The account_id is required to construct the R2 endpoint URL.
    //
    // The api_key is used for the Cloudflare REST API (e.g. checking Pages
    // build status). It is separate from the R2 access credentials above.
    //
    'cloudflare' => [
        'access_key_id'     => env('PODCAST_POST_PRODUCTION_CLOUDFLARE_ACCESS_KEY_ID'),
        'secret_access_key' => env('PODCAST_POST_PRODUCTION_CLOUDFLARE_SECRET_ACCESS_KEY'),
        'account_id'        => env('PODCAST_POST_PRODUCTION_CLOUDFLARE_ACCOUNT_ID'),
        'api_key'           => env('PODCAST_POST_PRODUCTION_CLOUDFLARE_API_TOKEN'),
    ],

    // -------------------------------------------------------------------------
    // Auphonic
    // -------------------------------------------------------------------------
    //
    // API key for the Auphonic audio post-production service.
    // Auphonic preset UUIDs are defined in the AuphonicPreset enum, not here.
    //
    // API usage example:
    //   curl https://auphonic.com/api/productions.json \
    //        -H "Authorization: bearer {api_key}"
    //
    'auphonic' => [
        'api_key' => env('PODCAST_POST_PRODUCTION_AUPHONIC_API_KEY'),
    ],

];