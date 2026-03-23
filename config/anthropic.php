<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Anthropic API Key
    |--------------------------------------------------------------------------
    |
    | Your Anthropic API key, used to authenticate requests to the Claude API.
    | Find your key at https://console.anthropic.com/settings/keys
    */

    'api_key' => env('ANTHROPIC_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Anthropic API Version
    |--------------------------------------------------------------------------
    |
    | The Anthropic API version header sent with every request.
    | See: https://docs.anthropic.com/en/api/versioning
    */

    'api_version' => '2023-06-01',

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    */

    'request_timeout' => env('ANTHROPIC_REQUEST_TIMEOUT', 60),

];