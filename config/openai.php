<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    |
    | Your OpenAI API key, used to authenticate requests to the OpenAI API.
    | Find your key at https://platform.openai.com/api-keys
    */

    'api_key' => env('OPENAI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    */

    'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 60),

];