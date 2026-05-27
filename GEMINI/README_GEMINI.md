# The `Gemini/` Folder

## Why does this folder exist?

This folder is a **local replacement** for the `google-gemini-php/laravel` package, which was removed during the upgrade to Laravel 13.

## Background

The application uses the Google Gemini API for AI-powered content summarisation. Previously this was provided by two Composer packages:

- `google-gemini-php/laravel` — the Laravel wrapper (service provider, facade, config)
- `google-gemini-php/client` — the underlying PHP client

When upgrading to Laravel 13, `google-gemini-php/laravel` became a blocker. Its `composer.json` constrains `laravel/framework` to `^9.0|^10.0|^11.0|^12.0` and the package had not been updated to include `^13.0` at the time of our upgrade.

Rather than forking the package or waiting for an upstream release, the Laravel wrapper layer was recreated here in the project. The underlying client package (`google-gemini-php/client`) has no Laravel version constraint and continues to be installed via Composer as normal.

## What is in this folder

```
Gemini/
    Facades/
        Gemini.php        — Laravel facade providing static access to Gemini\Client
    ServiceProvider.php   — Registers Gemini\Client in the service container
```

These two files replicate exactly what `google-gemini-php/laravel` provided. The namespaces are intentionally identical to the original package:

- `Gemini\Laravel\ServiceProvider`
- `Gemini\Laravel\Facades\Gemini`

This means **no other code in the application needed to change**. All existing `use Gemini\Laravel\Facades\Gemini` imports continue to resolve correctly.

## Why is this folder in the project root (not in `app/`)?

Laravel's `app/` directory is mapped to the `App\` namespace by Composer's PSR-4 autoloader. Placing these files there would require the namespace `App\Gemini\Laravel\...`, which would break all existing imports.

By placing the folder at the project root and registering a dedicated PSR-4 mapping in `composer.json`, the `Gemini\` namespace root resolves correctly:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Gemini\\Laravel\\": "Gemini/"
    }
}
```

## Config

The Gemini client is configured via `config/gemini.php`, which is unchanged from the original vendor-published file. The relevant environment variables are:

| Variable | Default | Purpose |
|---|---|---|
| `GEMINI_API_KEY` | *(required)* | API key from Google AI Studio |
| `GEMINI_BASE_URL` | `https://generativelanguage.googleapis.com/v1beta/` | API base URL |
| `GEMINI_REQUEST_TIMEOUT` | `30` | HTTP timeout in seconds |

## When can this folder be removed?

If `google-gemini-php/laravel` is updated to support Laravel 13 (or whichever version is current), this folder can be removed and the original package reinstated. The steps would be:

1. Remove the `"Gemini\\Laravel\\": "Gemini/"` entry from `composer.json` autoload
2. Delete this `Gemini/` folder
3. Add `"google-gemini-php/laravel": "^2.0"` (or later) back to `composer.json`
4. Remove `\Gemini\Laravel\ServiceProvider::class` from `bootstrap/providers.php` (the package will auto-discover its own provider)
5. Run `composer update` and `composer dump-autoload`
6. Run the test suite to confirm nothing regressed