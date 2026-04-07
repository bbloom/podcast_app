<?php

namespace MediaPlatform\PodcastStudio\PostProduction\GenerateRssFeed\Services;

// =============================================================================
// GenerateRssFeedResult
//
// Immutable value object returned by RssFeedGeneratorService::generate().
//
// Callers always check $result->ok() first:
//
//   Wizard controller:
//     if (! $result->ok()) {
//         return redirect()->back()->with('error', $result->error());
//     }
//     $xml = $result->xml();
//
//   Artisan command:
//     if (! $result->ok()) {
//         $this->error($result->error());
//         return;
//     }
//     $xml = $result->xml();
//
// Constructed only via the named static factories — never via new directly.
// =============================================================================

class GenerateRssFeedResult
{
    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------

    /** @var bool Whether generation succeeded. */
    private bool $ok;

    /** @var string|null The generated XML string. Null on failure. */
    private ?string $xml;

    /** @var string|null The error message. Null on success. */
    private ?string $error;


    // -------------------------------------------------------------------------
    // Constructor — private, use named factories below.
    // -------------------------------------------------------------------------

    private function __construct(bool $ok, ?string $xml, ?string $error)
    {
        $this->ok    = $ok;
        $this->xml   = $xml;
        $this->error = $error;
    }


    // -------------------------------------------------------------------------
    // Named static factories
    // -------------------------------------------------------------------------

    /**
     * Create a successful result carrying the generated XML string.
     */
    public static function success(string $xml): self
    {
        return new self(true, $xml, null);
    }

    /**
     * Create a failure result carrying a human-readable error message.
     */
    public static function failure(string $error): self
    {
        return new self(false, null, $error);
    }


    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Whether the generation succeeded.
     */
    public function ok(): bool
    {
        return $this->ok;
    }

    /**
     * The generated XML string.
     * Only call this after confirming ok() === true.
     *
     * @throws \LogicException if called on a failed result.
     */
    public function xml(): string
    {
        if (! $this->ok) {
            throw new \LogicException(
                'Cannot call xml() on a failed GenerateRssFeedResult. Check ok() first.'
            );
        }

        return $this->xml;
    }

    /**
     * The human-readable error message.
     * Only meaningful when ok() === false.
     */
    public function error(): ?string
    {
        return $this->error;
    }
}