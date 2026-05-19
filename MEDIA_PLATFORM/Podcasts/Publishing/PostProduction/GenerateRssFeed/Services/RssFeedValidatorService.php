<?php

namespace MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Services;

// Models
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;

// Laravel
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// =============================================================================
// RssFeedValidatorService
//
// Validates that all fields required for RSS feed generation are present and
// non-empty on the episode and its parent show.
//
// Philosophy:
//   - Existence checks only — is the field populated? If null or empty, block.
//   - No format validation or business rule re-implementation.
//   - One exception: rss_feed_enabled must be true (boolean check).
//   - One warning: itunes_pubdate in the past (non-blocking).
//   - R2 download + getID3 comparison for enclosure_length and duration.
//     If R2 download fails, r2DownloadFailed = true — Step 2 surfaces inline
//     input fields for manual confirmation instead.
//
// R2 skip: if session key wizard.generate_rss_feed.enclosure_manually_verified_{id}
// is present, the R2 download check is skipped entirely — the manual confirmation
// on Step 2 is accepted as validation.
//
// Returns a RssFeedValidatorResult value object.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/GenerateRssFeed/Services/
// =============================================================================

class RssFeedValidatorService
{
    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Validate all fields required for RSS feed generation.
     *
     * Checks show-level fields, episode-level fields, and optionally
     * verifies the production MP3 via R2 download + getID3.
     *
     * @param  PodcastEpisode  $episode
     * @param  PodcastShow     $show
     * @return RssFeedValidatorResult
     */
    public function validate(PodcastEpisode $episode, PodcastShow $show): RssFeedValidatorResult
    {
        $failures        = [];
        $warnings        = [];
        $r2DownloadFailed = false;

        // ---------------------------------------------------------------------
        // Show-level field checks
        // ---------------------------------------------------------------------
        $failures = array_merge($failures, $this->validateShowFields($show));

        // ---------------------------------------------------------------------
        // Episode-level field checks
        // ---------------------------------------------------------------------
        [$episodeFailures, $episodeWarnings] = $this->validateEpisodeFields($episode);
        $failures = array_merge($failures, $episodeFailures);
        $warnings = array_merge($warnings, $episodeWarnings);

        // ---------------------------------------------------------------------
        // R2 enclosure verification
        // Skipped if the user has manually confirmed these values on Step 2.
        // ---------------------------------------------------------------------
        $manuallyVerified = session('wizard.generate_rss_feed.enclosure_manually_verified_' . $episode->id, false);

        if (! $manuallyVerified) {
            [$r2Failures, $r2DownloadFailed] = $this->validateEnclosureViaR2($episode);
            $failures = array_merge($failures, $r2Failures);
        }

        return new RssFeedValidatorResult($failures, $warnings, $r2DownloadFailed);
    }


    // =========================================================================
    // SHOW-LEVEL VALIDATION
    // =========================================================================

    /**
     * Validate all required show-level fields.
     * Returns an array of failure entries.
     *
     * @return array<int, array{field: string, message: string}>
     */
    private function validateShowFields(PodcastShow $show): array
    {
        $failures = [];

        // ── Required show fields ──────────────────────────────────────────────
        $fields = [
            'rss_link'               => 'RSS feed URL',
            'description'            => 'Description',
            'itunes_image'           => 'iTunes image URL',
            'itunes_language'        => 'Language',
            'itunes_category_primary' => 'Primary category',
            'itunes_author'          => 'Author',
            'itunes_link'            => 'Website link',
            'itunes_email'           => 'Owner email',
            'itunes_name'            => 'Owner name',
            'itunes_title'           => 'iTunes title',
            'itunes_type'            => 'Podcast type (episodic/serial)',
        ];

        foreach ($fields as $field => $label) {
            if ($this->blank($show->$field)) {
                $failures[] = [
                    'field'   => $field,
                    'message' => "Show field \"{$label}\" is missing or empty.",
                ];
            }
        }

        return $failures;
    }


    // =========================================================================
    // EPISODE-LEVEL VALIDATION
    // =========================================================================

    /**
     * Validate all required episode-level fields.
     * Returns [$failures, $warnings] — both arrays of entries.
     *
     * @return array{array<int, array{field: string, message: string}>, array<int, array{field: string, message: string}>}
     */
    private function validateEpisodeFields(PodcastEpisode $episode): array
    {
        $failures = [];
        $warnings = [];

        // ── rss_feed_enabled — must be true ───────────────────────────────────
        if (! $episode->rss_feed_enabled) {
            $failures[] = [
                'field'   => 'rss_feed_enabled',
                'message' => '"Include in RSS Feed" is set to No. Set it to Yes to include this episode.',
            ];
        }

        // ── Required episode fields ───────────────────────────────────────────
        $fields = [
            'title'                   => 'Title',
            'itunes_enclosure_url'    => 'Media file URL',
            'itunes_enclosure_length' => 'Media file size',
            'itunes_enclosure_type'   => 'Media file type',
            'itunes_guid'             => 'GUID',
            'itunes_description'      => 'Description',
            'itunes_duration'         => 'Duration',
            'itunes_link'             => 'Website link',
            'itunes_episode'          => 'Episode number',
            'itunes_episode_type'     => 'Episode type',
        ];

        foreach ($fields as $field => $label) {
            if ($this->blank($episode->$field)) {
                $failures[] = [
                    'field'   => $field,
                    'message' => "Episode field \"{$label}\" is missing or empty.",
                ];
            }
        }

        // ── itunes_pubdate — required, but past date is a warning only ────────
        if ($this->blank($episode->itunes_pubdate)) {
            $failures[] = [
                'field'   => 'itunes_pubdate',
                'message' => 'Publish date is not set.',
            ];
        } elseif ($episode->itunes_pubdate->isPast()) {
            $warnings[] = [
                'field'   => 'itunes_pubdate',
                'message' => 'Publish date is in the past ('
                    . $episode->itunes_pubdate->format('M j, Y H:i')
                    . '). If this is intentional, proceed. If it looks like a typo, edit the episode before continuing.',
            ];
        }

        return [$failures, $warnings];
    }


    // =========================================================================
    // R2 ENCLOSURE VERIFICATION
    // =========================================================================

    /**
     * Download the production MP3 from R2 and compare filesize and duration
     * against the stored itunes_enclosure_length and itunes_duration values.
     *
     * Returns [$failures, $r2DownloadFailed]:
     *   - $failures         — mismatch failures if the download succeeded
     *   - $r2DownloadFailed — true if the download itself failed (network/auth)
     *
     * @return array{array<int, array{field: string, message: string}>, bool}
     */
    private function validateEnclosureViaR2(PodcastEpisode $episode): array
    {
        $failures        = [];
        $r2DownloadFailed = false;

        // ── Attempt R2 download ───────────────────────────────────────────────
        $enclosureUrl = $episode->itunes_enclosure_url;

        if ($this->blank($enclosureUrl)) {
            // No URL to download from — field failure already caught above.
            return [$failures, $r2DownloadFailed];
        }

        try {
            $response = Http::timeout(30)->get($enclosureUrl);

            if (! $response->successful()) {
                Log::warning('RssFeedValidatorService: R2 download returned non-success status.', [
                    'episode_id' => $episode->id,
                    'url'        => $enclosureUrl,
                    'status'     => $response->status(),
                ]);

                $r2DownloadFailed = true;
                return [$failures, $r2DownloadFailed];
            }

            // ── Write to a temporary file for getID3 ─────────────────────────
            $tmpPath = sys_get_temp_dir() . '/rss_validate_' . $episode->id . '.mp3';
            file_put_contents($tmpPath, $response->body());

        } catch (\Throwable $e) {
            Log::warning('RssFeedValidatorService: R2 download threw an exception.', [
                'episode_id' => $episode->id,
                'url'        => $enclosureUrl,
                'error'      => $e->getMessage(),
            ]);

            $r2DownloadFailed = true;
            return [$failures, $r2DownloadFailed];
        }

        // ── Run getID3 ────────────────────────────────────────────────────────
        try {
            $getID3 = new \getID3();
            $info   = $getID3->analyze($tmpPath);

            // ── Compare filesize ──────────────────────────────────────────────
            $actualFilesize  = $info['filesize']          ?? filesize($tmpPath);
            $storedFilesize  = (int) $episode->itunes_enclosure_length;

            // Allow a 1% tolerance for minor encoding differences.
            $filesizeTolerance = $actualFilesize * 0.01;

            if (abs($actualFilesize - $storedFilesize) > $filesizeTolerance) {
                $failures[] = [
                    'field'   => 'itunes_enclosure_length',
                    'message' => 'File size mismatch. '
                        . 'Stored: ' . number_format($storedFilesize) . ' bytes. '
                        . 'Actual: ' . number_format($actualFilesize) . ' bytes. '
                        . 'Please correct the value below.',
                ];
            }

            // ── Compare duration ──────────────────────────────────────────────
            if (! empty($info['playtime_seconds'])) {
                $actualSeconds = (int) round($info['playtime_seconds']);
                $storedSeconds = $this->durationToSeconds($episode->itunes_duration);

                // Allow a 3-second tolerance.
                if (abs($actualSeconds - $storedSeconds) > 3) {
                    $failures[] = [
                        'field'   => 'itunes_duration',
                        'message' => 'Duration mismatch. '
                            . 'Stored: ' . $episode->itunes_duration . '. '
                            . 'Actual: ' . $this->secondsToDuration($actualSeconds) . '. '
                            . 'Please correct the value below.',
                    ];
                }
            }

        } catch (\Throwable $e) {
            Log::warning('RssFeedValidatorService: getID3 analysis failed.', [
                'episode_id' => $episode->id,
                'error'      => $e->getMessage(),
            ]);

            // getID3 failure is treated as an R2 download failure —
            // surface manual confirmation fields on Step 2.
            $r2DownloadFailed = true;

        } finally {
            // ── Always clean up the temporary file ────────────────────────────
            if (isset($tmpPath) && file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }

        return [$failures, $r2DownloadFailed];
    }


    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Return true when a value is null, an empty string, or not set.
     */
    private function blank(mixed $value): bool
    {
        if (is_null($value))  return true;
        if ($value === '')    return true;
        if (! isset($value))  return true;

        return false;
    }

    /**
     * Convert a duration string (HH:MM:SS or MM:SS) to total seconds.
     * Returns 0 if the format is unrecognised.
     */
    private function durationToSeconds(mixed $duration): int
    {
        if ($this->blank($duration)) {
            return 0;
        }

        $parts = explode(':', (string) $duration);

        return match (count($parts)) {
            3 => ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) $parts[2],
            2 => ((int) $parts[0] * 60) + (int) $parts[1],
            1 => (int) $parts[0],
            default => 0,
        };
    }

    /**
     * Convert total seconds to a duration string.
     * Formats as H:MM:SS when >= 1 hour, or MM:SS when under 1 hour.
     */
    private function secondsToDuration(int $seconds): string
    {
        $hours   = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);
        $secs    = $seconds % 60;

        if ($hours > 0) {
            return $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($secs, 2, '0', STR_PAD_LEFT);
        }

        return str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($secs, 2, '0', STR_PAD_LEFT);
    }
}