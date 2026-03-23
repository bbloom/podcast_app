<?php

namespace MediaPlatform\Digest\Processing\Podcasts\Jobs;

use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use MediaPlatform\Digest\Processing\Models\ListSourceTracking;
use MediaPlatform\Digest\Processing\Podcasts\Services\PodcastContentProcessor;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPodcastSource implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries    = 3;
    public array $backoff = [30, 60, 120];
    public int $timeout  = 300;

    public function __construct(
        public int $listSourceId
    ) {}

    public function handle(PodcastContentProcessor $processor): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $listSource = DB::table('list_sources')->find($this->listSourceId);

        if (! $listSource || ! $listSource->enabled || $listSource->suspended) {
            Log::info("ProcessPodcastSource: list_source {$this->listSourceId} not found, disabled, or suspended. Skipping.");
            return;
        }

        Log::info("ProcessPodcastSource: Processing list_source {$this->listSourceId}.");

        $stats = $processor->process($listSource);

        Log::info("ProcessPodcastSource: Complete for list_source {$this->listSourceId}.", $stats);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("ProcessPodcastSource: All retries exhausted for list_source {$this->listSourceId}.", [
            'error' => $e->getMessage(),
        ]);

        $tracking = ListSourceTracking::findOrCreateFor($this->listSourceId);
        $tracking->recordFailure("Job failed after all retries: {$e->getMessage()}");

        if ($tracking->shouldSuspend()) {
            DB::table('list_sources')->where('id', $this->listSourceId)->update(['suspended' => true]);

            AdminAlert::raiseIfNew(
                tier: 2,
                category: 'podcast',
                title: "Podcast source auto-suspended (list_source {$this->listSourceId})",
                message: "list_source {$this->listSourceId} suspended after {$tracking->consecutive_failures} consecutive failures. Last error: {$e->getMessage()}",
            );
        }
    }
}
