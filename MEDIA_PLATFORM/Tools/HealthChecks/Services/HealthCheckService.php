<?php

namespace MediaPlatform\Tools\HealthChecks\Services;

use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use MediaPlatform\Digest\Processing\Services\LlmService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;

class HealthCheckService
{
    private const USE_CASE_SLUG = 'digest-processing';

    // -------------------------------------------------------------------------
    // Minimum thresholds for PHP upload-related ini settings.
    // These reflect the requirements of the podcast post-production pipeline,
    // where WAV and MP3 files can reach 500 MB or more.
    // -------------------------------------------------------------------------
    private const MIN_UPLOAD_BYTES     = 524_288_000; // 500 MB in bytes
    private const MIN_POST_BYTES       = 524_288_000; // 500 MB in bytes
    private const MIN_MEMORY_BYTES     = 1_073_741_824; // 1 GB in bytes
    private const MIN_EXECUTION_SECS   = 300;           // 5 minutes

    private AlertService $alertService;
    private LlmService $llmService;
    private array $results = [];

    public function __construct(AlertService $alertService, LlmService $llmService)
    {
        $this->alertService = $alertService;
        $this->llmService   = $llmService;
    }

    /**
     * Run all health checks. Returns an array of results.
     */
    public function runAll(): array
    {
        $this->results = [];

        $this->checkDatabase();
        $this->checkQueueConnection();
        $this->checkLlmConfiguration();
        $this->checkLlmApiKey();
        $this->checkLlmModelAvailability();
        $this->checkYoutubeApiKey();
        $this->checkPythonScript();
        $this->checkPythonDependencies();
        $this->checkDiskSpace();
        $this->checkPhpUploadLimits();
        $this->checkFailedJobs();

        $this->alertService->sendPendingNotifications();

        return $this->results;
    }

    // ─── Individual Checks ───────────────────────────────────

    private function checkDatabase(): void
    {
        try {
            DB::select('SELECT 1');
            $this->pass('database', 'infrastructure', 'Database connection OK');
        } catch (\Throwable $e) {
            $this->fail(3, 'infrastructure', 'Database unreachable',
                'Cannot connect to database. Error: ' . $e->getMessage() . '. Check Postgres connection and credentials.');
        }
    }

    private function checkQueueConnection(): void
    {
        try {
            $connection = config('queue.default');

            if ($connection === 'database') {
                DB::table('jobs')->limit(1)->get();
            }

            if ($connection === 'redis') {
                Queue::size();
            }

            $this->pass('queue', 'queue', "Queue connection OK ({$connection} driver)");
        } catch (\Throwable $e) {
            $this->fail(3, 'queue', 'Queue connection failed',
                'Cannot connect to queue. Driver: ' . config('queue.default') . '. Error: ' . $e->getMessage());
        }
    }

    /**
     * Check that a language model is configured for content summarisation.
     */
    private function checkLlmConfiguration(): void
    {
        $info = $this->llmService->resolveInfo(self::USE_CASE_SLUG);

        if (! $info) {
            $this->fail(3, 'gemini', 'No language model configured for content summarisation',
                'No enabled language model found with the "digest-processing" use case. ' .
                'Go to Language Models admin, enable a model, and attach the digest-processing use case.');
            return;
        }

        $this->pass('llm_config', 'gemini',
            "LLM configured: {$info['provider_name']} / {$info['model_name']} ({$info['model_slug']})");
    }

    /**
     * Check that the configured provider's API key is valid.
     */
    private function checkLlmApiKey(): void
    {
        $info = $this->llmService->resolveInfo(self::USE_CASE_SLUG);

        if (! $info) {
            // Already flagged by checkLlmConfiguration
            return;
        }

        try {
            $healthy = $this->llmService->healthCheck(self::USE_CASE_SLUG);

            if ($healthy) {
                $this->pass('llm_key', 'gemini',
                    "{$info['provider_name']} API key valid (model: {$info['model_slug']})");
            } else {
                $this->fail(2, 'gemini', "{$info['provider_name']} health check returned unexpected response",
                    "The API responded but did not return the expected result. This may be transient.");
            }
        } catch (\Throwable $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'auth') || str_contains($message, '401') || str_contains($message, 'API key')) {
                $this->fail(3, 'gemini', "{$info['provider_name']} API key invalid",
                    "Authentication failed with {$info['provider_name']}. Update the API key in .env.");
            } else {
                $this->fail(2, 'gemini', "{$info['provider_name']} API unreachable",
                    "Could not connect to {$info['provider_name']}. Error: {$message}. This may be transient.");
            }
        }
    }

    /**
     * Check that the configured model is still available from the provider.
     */
    private function checkLlmModelAvailability(): void
    {
        $info = $this->llmService->resolveInfo(self::USE_CASE_SLUG);

        if (! $info) {
            return;
        }

        try {
            $available = $this->llmService->isModelAvailable(self::USE_CASE_SLUG);

            if ($available) {
                $this->pass('llm_model', 'gemini',
                    "{$info['provider_name']} model '{$info['model_slug']}' is available");
            } else {
                $this->fail(3, 'gemini', "{$info['provider_name']} model deprecated or unavailable",
                    "Model '{$info['model_slug']}' was not found. " .
                    "Go to Language Models admin — disable this model and enable a replacement with the digest-processing use case.");
            }
        } catch (\Throwable $e) {
            $this->fail(2, 'gemini', "Cannot verify {$info['provider_name']} model availability",
                "Failed to check model list. Error: {$e->getMessage()}. This may be transient.");
        }
    }

    private function checkYoutubeApiKey(): void
    {
        try {
            $response = Http::timeout(10)->get('https://www.googleapis.com/youtube/v3/channels', [
                'part' => 'id',
                'id'   => 'UC_x5XG1OV2P6uZZ5FSM9Ttw',
                'key'  => config('youtube.api_key'),
            ]);

            if ($response->status() === 403) {
                $body   = $response->json();
                $reason = $body['error']['errors'][0]['reason'] ?? 'unknown';

                if ($reason === 'quotaExceeded') {
                    $this->fail(2, 'youtube', 'YouTube API quota exceeded',
                        'Daily quota has been exhausted. YouTube processing paused until quota resets at midnight Pacific.');
                } else {
                    $this->fail(3, 'youtube', 'YouTube API key invalid',
                        'Received HTTP 403 from YouTube API. Reason: ' . $reason . '. Update YOUTUBE_API_KEY in .env.');
                }
                return;
            }

            if ($response->successful()) {
                $this->pass('youtube_key', 'youtube', 'YouTube API key valid');
            } else {
                $this->fail(2, 'youtube', 'YouTube API returned unexpected status',
                    'YouTube API returned HTTP ' . $response->status() . '. This may be transient.');
            }
        } catch (\Throwable $e) {
            $this->fail(2, 'youtube', 'YouTube API unreachable',
                'Could not connect to YouTube API. Error: ' . $e->getMessage());
        }
    }

    private function checkPythonScript(): void
    {
        $scriptPath = base_path('scripts/get_transcript.py');

        if (file_exists($scriptPath)) {
            $this->pass('python_script', 'youtube', 'Python transcript script exists');
        } else {
            $this->fail(3, 'youtube', 'Python transcript script missing',
                "Expected script at {$scriptPath}. Restore the file from version control or check deployment.");
        }
    }

    private function checkPythonDependencies(): void
    {
        try {
            $result = Process::timeout(15)->run(
                '/usr/bin/python3 -c "import yt_dlp; import youtube_transcript_api; print(\'OK\')"'
            );

            if ($result->successful() && str_contains($result->output(), 'OK')) {
                $this->pass('python_deps', 'youtube', 'Python dependencies installed');
            } else {
                $this->fail(3, 'youtube', 'Python dependencies missing',
                    'yt-dlp or youtube-transcript-api not installed. Run: pip install yt-dlp youtube-transcript-api');
            }
        } catch (\Throwable $e) {
            $this->fail(3, 'youtube', 'Python environment check failed',
                'Could not verify Python dependencies. Error: ' . $e->getMessage());
        }
    }

    private function checkDiskSpace(): void
    {
        try {
            $freeBytes = disk_free_space('/');
            $freeMb    = round($freeBytes / 1024 / 1024);

            if ($freeMb > 500) {
                $this->pass('disk_space', 'infrastructure', "Disk space OK ({$freeMb} MB free)");
            } elseif ($freeMb > 100) {
                $this->fail(2, 'infrastructure', 'Disk space low',
                    "Only {$freeMb} MB free. Consider cleaning up old logs, temp files, or increasing disk size.");
            } else {
                $this->fail(3, 'infrastructure', 'Disk space critical',
                    "Only {$freeMb} MB free. Processing blocked to prevent data corruption. Free up space immediately.");
            }
        } catch (\Throwable $e) {
            $this->fail(2, 'infrastructure', 'Cannot check disk space',
                'disk_free_space() failed. Error: ' . $e->getMessage());
        }
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  checkPhpUploadLimits()                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Check that PHP ini settings are sufficient for large podcast file uploads.
     *
     * The podcast post-production pipeline handles WAV and MP3 files that can
     * reach 500 MB or more. Four ini settings must be configured generously:
     *
     *   - upload_max_filesize  — max size of a single uploaded file
     *   - post_max_size        — max size of an entire POST request body
     *   - memory_limit         — PHP memory available during the request
     *   - max_execution_time   — max seconds before PHP kills the request
     *                            (0 = unlimited, which is acceptable)
     *
     * All four are Tier 3 — if any are too low, large file uploads will fail
     * silently or mid-transfer, which is confusing and unrecoverable without
     * a config fix.
     *
     * Configure these in your Docker php.ini or FrankenPHP configuration.
     */
    private function checkPhpUploadLimits(): void
    {
        // ── upload_max_filesize ───────────────────────────────────────────────
        $uploadMaxBytes = $this->iniToBytes(ini_get('upload_max_filesize'));
        $uploadMaxHuman = ini_get('upload_max_filesize');

        if ($uploadMaxBytes >= self::MIN_UPLOAD_BYTES) {
            $this->pass('php_upload_max_filesize', 'infrastructure',
                "upload_max_filesize OK ({$uploadMaxHuman})");
        } else {
            $this->fail(3, 'infrastructure', 'PHP upload_max_filesize too low',
                "Current value: {$uploadMaxHuman}. Must be at least 500M for podcast file uploads. " .
                "Update upload_max_filesize in your php.ini or FrankenPHP config and restart the server.");
        }

        // ── post_max_size ─────────────────────────────────────────────────────
        $postMaxBytes = $this->iniToBytes(ini_get('post_max_size'));
        $postMaxHuman = ini_get('post_max_size');

        if ($postMaxBytes >= self::MIN_POST_BYTES) {
            $this->pass('php_post_max_size', 'infrastructure',
                "post_max_size OK ({$postMaxHuman})");
        } else {
            $this->fail(3, 'infrastructure', 'PHP post_max_size too low',
                "Current value: {$postMaxHuman}. Must be at least 500M for podcast file uploads. " .
                "Update post_max_size in your php.ini or FrankenPHP config and restart the server.");
        }

        // ── memory_limit ──────────────────────────────────────────────────────
        $memoryLimitBytes = $this->iniToBytes(ini_get('memory_limit'));
        $memoryLimitHuman = ini_get('memory_limit');

        // -1 means unlimited — treat as a pass.
        if ($memoryLimitBytes === -1 || $memoryLimitBytes >= self::MIN_MEMORY_BYTES) {
            $this->pass('php_memory_limit', 'infrastructure',
                "memory_limit OK ({$memoryLimitHuman})");
        } else {
            $this->fail(3, 'infrastructure', 'PHP memory_limit too low',
                "Current value: {$memoryLimitHuman}. Must be at least 1G for large podcast file uploads. " .
                "Update memory_limit in your php.ini or FrankenPHP config and restart the server.");
        }

        // ── max_execution_time ────────────────────────────────────────────────
        $maxExecution = (int) ini_get('max_execution_time');

        // 0 means unlimited — treat as a pass.
        if ($maxExecution === 0 || $maxExecution >= self::MIN_EXECUTION_SECS) {
            $label = $maxExecution === 0 ? 'unlimited' : "{$maxExecution}s";
            $this->pass('php_max_execution_time', 'infrastructure',
                "max_execution_time OK ({$label})");
        } else {
            $this->fail(3, 'infrastructure', 'PHP max_execution_time too low',
                "Current value: {$maxExecution}s. Must be at least 300 seconds (5 minutes) for large podcast file uploads. " .
                "Update max_execution_time in your php.ini or FrankenPHP config and restart the server.");
        }
    }

     /**
     * Check whether the failed_jobs table contains any unprocessed failures.
     *
     * A non-empty failed_jobs table means one or more queued jobs have failed
     * permanently and were not retried successfully. This is a Tier 2 alert —
     * the system continues running but the failed jobs need human attention.
     *
     * Auto-resolves on the next health check run once the table is empty.
     * Can also be resolved immediately via the "Flush Failed Jobs" UI action.
     */
    public function checkFailedJobs(): void
    {
        $count = DB::table('failed_jobs')->count();

        if ($count === 0) {
            $this->pass('failed_jobs', 'queue', 'No failed jobs');
            return;
        }

        $latestFailedAt = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->value('failed_at');

        $this->fail(
            2,
            'queue',
            'Failed jobs detected',
            "{$count} failed " . str('job')->plural($count) . " in the queue. " .
            "Most recent failure: {$latestFailedAt}. " .
            "Go to Health Checks in the Admin UI and flush the failed jobs, or SSH in and run: php artisan queue:flush"
        );
    }

    // ─── Result Helpers ──────────────────────────────────────

    private function pass(string $check, string $category, string $message): void
    {
        $this->results[] = [
            'check'   => $check,
            'status'  => 'pass',
            'message' => $message,
        ];

        $this->alertService->autoResolveFor($category);
        Log::debug("HealthCheck PASS: {$check} — {$message}");
    }

    private function fail(int $tier, string $category, string $title, string $message): void
    {
        $this->results[] = [
            'check'   => $category,
            'status'  => 'fail',
            'tier'    => $tier,
            'title'   => $title,
            'message' => $message,
        ];

        AdminAlert::raiseIfNew($tier, $category, $title, $message);
        Log::warning("HealthCheck FAIL (Tier {$tier}): {$title} — {$message}");
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  iniToBytes()                                                          │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Converts a PHP ini shorthand value (e.g. "2M", "512K", "1G") to bytes.
     *
     * Returns -1 if the value is "-1" (unlimited).
     * PHP's shorthand suffixes: K = kilobytes, M = megabytes, G = gigabytes.
     *
     * @param  string  $value  Raw ini value, e.g. "500M"
     * @return int  Value in bytes, or -1 for unlimited.
     */
    public function iniToBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '-1') {
            return -1;
        }

        $suffix = strtoupper(substr($value, -1));
        $number = (int) $value;

        return match ($suffix) {
            'G'     => $number * 1_073_741_824,
            'M'     => $number * 1_048_576,
            'K'     => $number * 1_024,
            default => $number,
        };
    }
}