# Processing Pipeline — Test Suite

This folder contains feature tests for the content-processing pipeline:
the classes responsible for fetching RSS feeds and YouTube channels, running
LLM summarisation, and writing results to the `summaries` table.

---

## Files in this folder

| File | What it tests |
|---|---|
| `README.md` | This document |
| `CompatibilityTest.php` | PHP version, Laravel version, and key extension checks |
| `TextBasedRssContentProcessorTest.php` | Full coverage of `TextBasedRssContentProcessor` |
| `YoutubeContentProcessorTest.php` | Full coverage of `YoutubeContentProcessor` |

---

## Philosophy

### These are feature tests, not unit tests

Every test in this folder hits the real database (SQLite in-memory via
`RefreshDatabase`) and uses Laravel's `Http::fake()` to intercept outbound
HTTP calls. The `LlmService` is mocked at the service-container level so no
real LLM calls are made.

This approach tests the full pipeline in each test — from the processor's
`process()` method down through feed parsing, dedup, DB writes, and tracking
updates — rather than testing each private method in isolation. It gives you
confidence that the whole pipeline works together, not just its parts.

### Why no dedicated "Laravel 13 / PHP 8.x upgrade" tests?

The feature tests *are* your regression net for framework and PHP upgrades.
If you bump `laravel/framework` to `^13.0` or PHP to `8.4` and all tests
still pass, your app works on that version. No hand-written compatibility
assertion can substitute for that.

`CompatibilityTest.php` does include lightweight checks for:
- The minimum PHP version your `composer.json` requires
- The presence of PHP extensions the app depends on (`pdo`, `simplexml`,
  `mbstring`, `pcre`)
- That specific language features used heavily in this codebase are
  available (`match` expressions, named arguments, nullsafe operator,
  arrow functions, `str_contains`, `str_starts_with`)
- The Laravel version range the app was developed and tested against

These are canary checks — they fire early and clearly if an environment
is misconfigured, before a cryptic failure deeper in the stack.

### CI recommendation

The best protection against PHP and Laravel version regressions is a
**GitHub Actions matrix build**. Add a `.github/workflows/tests.yml` that
runs the test suite against PHP 8.2, 8.3, and 8.4. Then bumping a version
is safe: you see which matrix cell turns red before it touches production.

A minimal workflow skeleton:

```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.2', '8.3', '8.4']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: pdo, pdo_sqlite, simplexml, mbstring
      - run: composer install --no-interaction --prefer-dist
      - run: cp .env.testing .env || cp .env.example .env
      - run: php artisan key:generate
      - run: php artisan test --parallel
```

---

## Test database

All tests use `RefreshDatabase`, which runs migrations fresh for each test
class. The test database is SQLite in-memory (configured in
`phpunit.xml` / `.env.testing`). No Postgres-specific features (e.g.
`halfvec` columns from pgvector) are exercised in these tests.

---

## Mocking strategy

### LLM calls
`LlmService` is mocked via Mockery and bound into the container with
`app()->instance()` in `setUp()`. This means:
- No HTTP calls leave the process
- No database language-model records are needed in the seeded test data
- Each test explicitly declares what the LLM would return, keeping tests
  deterministic and readable

### HTTP (RSS feeds, YouTube API)
Laravel's `Http::fake()` intercepts all outbound HTTP calls. Each test
provides its own XML/JSON fixtures inline. This avoids any network
dependency and makes tests runnable offline.

### Filesystem (transcript script)
`Process::fake()` is used for the Python transcript subprocess in
`YoutubeContentProcessorTest`. The fake can be configured per-test to
simulate success, failure, timeout, or missing script.

---

## Adding new tests

When adding a new processor (e.g. `PodcastContentProcessor`), follow this
pattern:

1. Create `PodcastContentProcessorTest.php` in this folder.
2. Create a PHPUnit class extending `TestCase` with the `RefreshDatabase` trait — follow the pattern in `YoutubeContentProcessorTest`.

```php
namespace Tests\Feature\MEDIA_PLATFORM\Digest\Processing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PodcastContentProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // user, list, feed/channel, list_source, LLM mock + app()->instance()
    }

    #[Test]
    public function description_mode_stores_content(): void
    {
        // ...
    }
}
```

3. Copy the `setUp()` scaffolding from one of the existing test files
   (user, list, feed/channel, list_source, LLM mock + `app()->instance()`).
4. Mirror the test group structure: happy path → first run → dedup →
   feed failures → LLM failures → data integrity.
5. Add an entry to the table at the top of this README.

---

## Running the tests

```bash
# Run only this folder
php artisan test tests/Feature/Processing

# Run a single file
php artisan test tests/Feature/Processing/CompatibilityTest.php
php artisan test tests/Feature/Processing/TextBasedRssContentProcessorTest.php
php artisan test tests/Feature/Processing/YoutubeContentProcessorTest.php
php artisan test tests/Feature/Processing/PodcastContentProcessorTest.php

# Run a single test by name
php artisan test --filter "description_mode_inserts_summary_with_wrapped_description"

# Run with coverage (requires Xdebug or PCOV)
php artisan test tests/Feature/Processing --coverage
```