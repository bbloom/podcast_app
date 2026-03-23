<?php

namespace MediaPlatform\Digest\Processing\TextBasedRss\Jobs;

use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use MediaPlatform\Digest\Processing\Models\ListSourceTracking;
use MediaPlatform\Digest\Processing\TextBasedRss\Services\TextBasedRssContentProcessor;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessTextBasedRssSource implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries    = 3;
    public array $backoff = [30, 60, 120];
    public int $timeout  = 300;

    public function __construct(
        public int $listSourceId
    ) {}

    public function handle(TextBasedRssContentProcessor $processor): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $listSource = DB::table('list_sources')->find($this->listSourceId);

        if (! $listSource || ! $listSource->enabled || $listSource->suspended) {
            Log::info("ProcessTextBasedRssSource: list_source {$this->listSourceId} not found, disabled, or suspended. Skipping.");
            return;
        }

        Log::info("ProcessTextBasedRssSource: Processing list_source {$this->listSourceId}.");

        $stats = $processor->process($listSource);

        Log::info("ProcessTextBasedRssSource: Complete for list_source {$this->listSourceId}.", $stats);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("ProcessTextBasedRssSource: All retries exhausted for list_source {$this->listSourceId}.", [
            'error' => $e->getMessage(),
        ]);

        $tracking = ListSourceTracking::findOrCreateFor($this->listSourceId);
        $tracking->recordFailure("Job failed after all retries: {$e->getMessage()}");

        if ($tracking->shouldSuspend()) {
            DB::table('list_sources')->where('id', $this->listSourceId)->update(['suspended' => true]);

            AdminAlert::raiseIfNew(
                tier: 2,
                category: 'text_based_rss',
                title: "Text-based RSS source auto-suspended (list_source {$this->listSourceId})",
                message: "list_source {$this->listSourceId} suspended after {$tracking->consecutive_failures} consecutive failures. Last error: {$e->getMessage()}",
            );
        }
    }
}
