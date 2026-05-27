<?php

namespace Gemini\Laravel;

use Gemini;
use Gemini\Client;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Registers the Gemini client in the Laravel service container,
 * replicating what google-gemini-php/laravel provided natively.
 * This exists because that package does not support Laravel 13+.
 */
class ServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    /** Register the Gemini client singleton. */
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'gemini');

        $this->app->singleton(Client::class, function () {
            return Gemini::factory()
                ->withApiKey(config('gemini.api_key', ''))
                ->withBaseUrl(config('gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta/'))
                ->withHttpClient(new \GuzzleHttp\Client([
                    'timeout' => config('gemini.request_timeout', 30),
                ]))
                ->make();
        });

        $this->app->alias(Client::class, 'gemini');
    }

    /** Publish the config file. */
    public function boot(): void
    {
        $this->publishes([
            $this->stubPath() => config_path('gemini.php'),
        ], 'gemini-config');
    }

    /** Only resolve when the client is actually needed. */
    public function provides(): array
    {
        return [Client::class, 'gemini'];
    }

    /**
     * Path to the live config file in the application's config/ directory.
     * Falls back to the stub if the file has not been published yet.
     */
    private function configPath(): string
    {
        $published = config_path('gemini.php');

        return file_exists($published) ? $published : $this->stubPath();
    }

    /** Path to the bundled config stub inside this package. */
    private function stubPath(): string
    {
        return base_path('Gemini/config/gemini.php');
    }
}