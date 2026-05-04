<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;


// ============================================================================
// Force the testing environment when running under Pest.
//
// When `php artisan test` is run, Laravel spawns Pest as a child process.
// That child process bootstraps a fresh Laravel application — meaning this
// file runs again, in a new process, with its own $_SERVER['argv'].
//
// Inside that child process, argv[0] is always the Pest binary path:
//   vendor/pestphp/pest/bin/pest
//
// We detect this and force APP_ENV=testing before Application::configure()
// runs, ensuring Laravel loads .env.testing (which points to news_rag_test)
// instead of .env (which points to news_rag).
//
// Without this, phpunit.xml's <env name="APP_ENV" value="testing" force="true"/>
// is injected too late — the application has already bootstrapped with the
// local environment by the time PHPUnit applies its overrides.
//
// putenv()        — sets the environment for C-level getenv() calls
// $_ENV           — sets it for PHP's $_ENV superglobal
// $_SERVER        — sets it for PHP's $_SERVER superglobal
// All three are required for full coverage across Laravel's env detection.
// ============================================================================
/*if (str_contains($_SERVER['argv'][0] ?? '', 'pest')) {
    putenv('APP_ENV=testing');
    $_ENV['APP_ENV'] = 'testing';
    $_SERVER['APP_ENV'] = 'testing';
}*/

// Force the testing environment when running under Pest.
// See full explanation comment in the version that has it.
// If you MUST use bootstrap/app.php
// If you are determined to override the database via the bootstrap file, you have to override the config, 
// not just the environment name.

if (str_contains($_SERVER['argv'][0] ?? '', 'pest')) {
    putenv('APP_ENV=testing');
    $_ENV['APP_ENV'] = 'testing';
    $_SERVER['APP_ENV'] = 'testing';
}



return Application::configure(basePath: dirname(__DIR__))

    // If you are determined to override the database via the bootstrap file, you have to override the config, not just the environment name.
    ->registered(function ($app) {
        if ($app->runningUnitTests()) {
            config(['database.default' => 'pgsql']);
            config(['database.connections.pgsql.database' => 'news_rag_test']);
        }
    })

    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            '/post-production/auphonic/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
    })->create();    