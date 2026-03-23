<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * CompatibilityTest
 *
 * Lightweight "canary" checks for PHP version, required extensions, and
 * Laravel version compatibility. NOT substitutes for running the full suite
 * against a new PHP or Laravel version — the feature tests are the real
 * regression net. These tests exist to:
 *
 *   1. Fire early and clearly when an environment is misconfigured.
 *   2. Document the minimum requirements in executable form.
 *   3. Flag immediately if a deployment/CI environment does not meet
 *      the app's stated requirements before a cryptic failure deeper in the stack.
 *
 * IMPORTANT: If you bump the minimum PHP version in composer.json, update
 * PHP_MINIMUM_VERSION below. Same for the Laravel version range.
 *
 * CI RECOMMENDATION
 * ─────────────────
 * Run these in a GitHub Actions matrix on PHP 8.2, 8.3, and 8.4 simultaneously.
 */

const PHP_MINIMUM_VERSION    = '8.4.0';
const LARAVEL_EXPECTED_MAJOR = 13;

// =============================================================================
// PHP Version
// =============================================================================

test('php version meets minimum requirement', function () {
    expect(version_compare(PHP_VERSION, PHP_MINIMUM_VERSION, '>='))->toBeTrue(
        sprintf(
            'PHP %s or higher is required. Running %s.',
            PHP_MINIMUM_VERSION,
            PHP_VERSION,
        )
    );
});

// =============================================================================
// Required Extensions
// =============================================================================

test('pdo extension is loaded', function () {
    expect(extension_loaded('pdo'))->toBeTrue(
        'The PDO extension is required for all database operations but is not loaded.'
    );
});

test('pdo_sqlite extension is loaded', function () {
    expect(extension_loaded('pdo_sqlite'))->toBeTrue(
        'The pdo_sqlite extension is required for in-memory test databases but is not loaded.'
    );
});

test('simplexml extension is loaded', function () {
    expect(extension_loaded('simplexml'))->toBeTrue(
        'The simplexml extension is required for RSS/Atom feed parsing but is not loaded.'
    );
});

test('mbstring extension is loaded', function () {
    expect(extension_loaded('mbstring'))->toBeTrue(
        'The mbstring extension is required for multi-byte string handling but is not loaded.'
    );
});

test('pcre extension is loaded', function () {
    expect(extension_loaded('pcre'))->toBeTrue(
        'The PCRE extension is required for regular expression operations but is not loaded.'
    );
});

test('json extension is loaded', function () {
    expect(extension_loaded('json'))->toBeTrue(
        'The JSON extension is required for parsing YouTube API responses but is not loaded.'
    );
});

// =============================================================================
// PHP Language Features
// =============================================================================

test('match expression is available', function () {
    $mode = 'summary';

    $result = match ($mode) {
        'description' => 'desc',
        'summary'     => 'sum',
        'search'      => 'srch',
        default       => 'none',
    };

    expect($result)->toBe('sum', '`match` expression did not evaluate correctly.');
});

test('named arguments are available', function () {
    $result = implode(separator: '-', array: ['a', 'b', 'c']);

    expect($result)->toBe('a-b-c', 'Named arguments did not work correctly.');
});

test('nullsafe operator is available', function () {
    $obj = null;

    $result = $obj?->nonExistentMethod();

    expect($result)->toBeNull('Nullsafe operator did not return null for a null object.');
});

test('arrow functions are available', function () {
    $double = fn ($x) => $x * 2;

    expect($double(5))->toBe(10, 'Arrow function did not evaluate correctly.');
});

test('str_contains function is available', function () {
    expect(function_exists('str_contains'))->toBeTrue(
        'str_contains() is not available. This function is required for search-term matching.'
    );

    expect(str_contains('hello world', 'world'))->toBeTrue();
    expect(str_contains('hello world', 'xyz'))->toBeFalse();
});

test('str_starts_with function is available', function () {
    expect(function_exists('str_starts_with'))->toBeTrue(
        'str_starts_with() is not available. This function is required for transcript error detection.'
    );

    expect(str_starts_with('ERROR: no captions', 'ERROR:'))->toBeTrue();
    expect(str_starts_with('Hello world', 'ERROR:'))->toBeFalse();
});

// =============================================================================
// Laravel Version
// =============================================================================

/**
 * THIS TEST IS INTENTIONALLY A CANARY, NOT A HARD BLOCKER.
 *
 * When you upgrade to Laravel 13, this test will fail. That is by design —
 * it is prompting you to review the upgrade guide, run the full suite, update
 * LARAVEL_EXPECTED_MAJOR, and commit the bump as a deliberate reviewed change.
 */
test('laravel major version matches expected', function () {
    $installed = (int) explode('.', app()->version())[0];

    expect($installed)->toBe(
        LARAVEL_EXPECTED_MAJOR,
        sprintf(
            'Laravel major version mismatch. Expected %d, found %d. ' .
            'If you have intentionally upgraded to Laravel %d, review the upgrade guide, ' .
            'run the full test suite, and then update LARAVEL_EXPECTED_MAJOR in %s.',
            LARAVEL_EXPECTED_MAJOR,
            $installed,
            $installed,
            'CompatibilityTest.php',
        )
    );
});

test('laravel version meets minimum', function () {
    expect(version_compare(app()->version(), '13.0.0', '>='))->toBeTrue(
        'Laravel 13.0.0 or higher is required. Found: ' . app()->version()
    );
});