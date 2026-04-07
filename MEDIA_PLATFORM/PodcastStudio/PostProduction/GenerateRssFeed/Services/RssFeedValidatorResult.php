<?php

namespace MediaPlatform\PodcastStudio\PostProduction\GenerateRssFeed\Services;

// =============================================================================
// RssFeedValidatorResult
//
// Immutable value object returned by RssFeedValidatorService::validate().
//
// Usage:
//   $result = $validator->validate($episode, $show);
//
//   if ($result->ok()) {
//       // All fields present, R2 verified (or manually confirmed) — proceed.
//   }
//
//   $result->failures()         — array of ['field' => '...', 'message' => '...']
//   $result->warnings()         — array of ['field' => '...', 'message' => '...']
//   $result->r2DownloadFailed() — bool, true if R2 download could not complete
//
// ok() returns true only when failures() is empty.
// Warnings and r2DownloadFailed do not block generation on their own —
// r2DownloadFailed surfaces inline input fields on Step 2 for manual confirmation.
// =============================================================================

class RssFeedValidatorResult
{
    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------

    /** @var array<int, array{field: string, message: string}> */
    private array $failures;

    /** @var array<int, array{field: string, message: string}> */
    private array $warnings;

    /** @var bool */
    private bool $r2DownloadFailed;


    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function __construct(
        array $failures,
        array $warnings,
        bool  $r2DownloadFailed
    ) {
        $this->failures         = $failures;
        $this->warnings         = $warnings;
        $this->r2DownloadFailed = $r2DownloadFailed;
    }


    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Whether validation passed.
     * True only when there are no hard failures.
     * Warnings and r2DownloadFailed do not block.
     */
    public function ok(): bool
    {
        return empty($this->failures);
    }

    /**
     * Hard failures — fields that are null or empty.
     * Each entry: ['field' => 'itunes_enclosure_url', 'message' => '...']
     *
     * @return array<int, array{field: string, message: string}>
     */
    public function failures(): array
    {
        return $this->failures;
    }

    /**
     * Non-blocking warnings — conditions worth flagging but that do not
     * prevent generation from proceeding.
     * Each entry: ['field' => 'itunes_pubdate', 'message' => '...']
     *
     * @return array<int, array{field: string, message: string}>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * Whether the R2 download failed during validation.
     * When true, Step 2 surfaces inline input fields for manual confirmation
     * of itunes_enclosure_length and itunes_duration.
     */
    public function r2DownloadFailed(): bool
    {
        return $this->r2DownloadFailed;
    }

    /**
     * Whether there are any warnings to display.
     */
    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
    }
}