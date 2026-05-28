<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/CompatibilityTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Processing;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompatibilityTest extends TestCase
{
    private const PHP_MINIMUM_VERSION    = '8.4.0';
    private const LARAVEL_EXPECTED_MAJOR = 13;

    // =========================================================================
    // PHP Version
    // =========================================================================

    #[Test]
    public function php_version_meets_minimum_requirement(): void
    {
        $this->assertTrue(
            version_compare(PHP_VERSION, self::PHP_MINIMUM_VERSION, '>='),
            sprintf('PHP %s or higher is required. Running %s.', self::PHP_MINIMUM_VERSION, PHP_VERSION),
        );
    }

    // =========================================================================
    // Required Extensions
    // =========================================================================

    #[Test]
    public function pdo_extension_is_loaded(): void
    {
        $this->assertTrue(extension_loaded('pdo'),
            'The PDO extension is required for all database operations but is not loaded.');
    }

    #[Test]
    public function pdo_sqlite_extension_is_loaded(): void
    {
        $this->assertTrue(extension_loaded('pdo_sqlite'),
            'The pdo_sqlite extension is required for in-memory test databases but is not loaded.');
    }

    #[Test]
    public function simplexml_extension_is_loaded(): void
    {
        $this->assertTrue(extension_loaded('simplexml'),
            'The simplexml extension is required for RSS/Atom feed parsing but is not loaded.');
    }

    #[Test]
    public function mbstring_extension_is_loaded(): void
    {
        $this->assertTrue(extension_loaded('mbstring'),
            'The mbstring extension is required for multi-byte string handling but is not loaded.');
    }

    #[Test]
    public function pcre_extension_is_loaded(): void
    {
        $this->assertTrue(extension_loaded('pcre'),
            'The PCRE extension is required for regular expression operations but is not loaded.');
    }

    #[Test]
    public function json_extension_is_loaded(): void
    {
        $this->assertTrue(extension_loaded('json'),
            'The JSON extension is required for parsing YouTube API responses but is not loaded.');
    }

    // =========================================================================
    // PHP Language Features
    // =========================================================================

    #[Test]
    public function match_expression_is_available(): void
    {
        $mode   = 'summary';
        $result = match ($mode) {
            'description' => 'desc',
            'summary'     => 'sum',
            'search'      => 'srch',
            default       => 'none',
        };

        $this->assertSame('sum', $result, '`match` expression did not evaluate correctly.');
    }

    #[Test]
    public function named_arguments_are_available(): void
    {
        $result = implode(separator: '-', array: ['a', 'b', 'c']);

        $this->assertSame('a-b-c', $result, 'Named arguments did not work correctly.');
    }

    #[Test]
    public function nullsafe_operator_is_available(): void
    {
        $obj    = null;
        $result = $obj?->nonExistentMethod();

        $this->assertNull($result, 'Nullsafe operator did not return null for a null object.');
    }

    #[Test]
    public function arrow_functions_are_available(): void
    {
        $double = fn ($x) => $x * 2;

        $this->assertSame(10, $double(5), 'Arrow function did not evaluate correctly.');
    }

    #[Test]
    public function str_contains_function_is_available(): void
    {
        $this->assertTrue(function_exists('str_contains'),
            'str_contains() is not available. This function is required for search-term matching.');

        $this->assertTrue(str_contains('hello world', 'world'));
        $this->assertFalse(str_contains('hello world', 'xyz'));
    }

    #[Test]
    public function str_starts_with_function_is_available(): void
    {
        $this->assertTrue(function_exists('str_starts_with'),
            'str_starts_with() is not available. This function is required for transcript error detection.');

        $this->assertTrue(str_starts_with('ERROR: no captions', 'ERROR:'));
        $this->assertFalse(str_starts_with('Hello world', 'ERROR:'));
    }

    // =========================================================================
    // Laravel Version
    // =========================================================================

    #[Test]
    public function laravel_major_version_matches_expected(): void
    {
        $installed = (int) explode('.', app()->version())[0];

        $this->assertSame(
            self::LARAVEL_EXPECTED_MAJOR,
            $installed,
            sprintf(
                'Laravel major version mismatch. Expected %d, found %d. '
                . 'If you have intentionally upgraded to Laravel %d, review the upgrade guide, '
                . 'run the full test suite, and then update LARAVEL_EXPECTED_MAJOR in %s.',
                self::LARAVEL_EXPECTED_MAJOR,
                $installed,
                $installed,
                'CompatibilityTest.php',
            ),
        );
    }

    #[Test]
    public function laravel_version_meets_minimum(): void
    {
        $this->assertTrue(
            version_compare(app()->version(), '13.0.0', '>='),
            'Laravel 13.0.0 or higher is required. Found: ' . app()->version(),
        );
    }
}