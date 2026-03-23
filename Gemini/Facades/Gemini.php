<?php

namespace Gemini\Laravel\Facades;

use Gemini\Client;
use Illuminate\Support\Facades\Facade;

/**
 * Facade providing static access to the Gemini\Client instance.
 * Mirrors the facade that google-gemini-php/laravel previously provided.
 *
 * @see \Gemini\Client
 */
class Gemini extends Facade
{
    /** Return the container binding key for the facade. */
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}