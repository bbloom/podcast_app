<?php

namespace MediaPlatform\Digest\Processing\Jobs;

use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Processing\Services\ProcessingGate;
use MediaPlatform\Digest\Processing\Services\SourceJobResolver;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessList — dispatches a batch of source-processing jobs for a single list,
 * then triggers PublishDigest after the batch completes.
 *
 * PIPELINE POSITION
 * ─────────────────
 * DispatchDueLists → ProcessList → Bus::batch([source jobs...]) → PublishDigest
 *
 * Each source job (ProcessYoutubeSource, ProcessPodcastSource,
 * ProcessTextBasedRssSource) runs independently and writes summaries to the DB.
 * Only when the entire batch finishes (all jobs done, some may have failed) does
 * PublishDigest run. Partial batch failures do not prevent digest delivery —
 * whatever summaries exist will be included.
 *
 * GATE CHECKS
 * ───────────
 * Individual source jobs are skipped if their subsystem has a Tier 3 blocking
 * alert. ProcessingGate is checked per source, not per batch — so a blocked
 * YouTube subsystem does not stop Podcast or RSS sources from running.
 *
 * DUPLICATE PREVENTION
 * ────────────────────
 * A Cache lock keyed on the list ID prevents concurrent runs of the same list.
 * This guards against the scheduler overlapping with a manual dispatch, or a
 * user triggering processing:dispatch multiple times in quick succession.
 * The lock TTL matches $timeout (600 s). If the job finishes sooner, the lock
 * is released immediately in the finally block.
 */
class ProcessList implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 600;

    public function __construct(
        public ListModel $list
    ) {}

    /**
     * Resolve all enabled, non-suspended sources for this list, dispatch them
     * as a batch, and register the batch callbacks.
     */
    public function handle(ProcessingGate $gate): void
    {
        $lock = Cache::lock('process-list-' . $this->list->id, 600);

        if (! $lock->get()) {
            Log::info("ProcessList: Skipping list '{$this->list->name}' (ID {$this->list->id}) — already running.");
            return;
        }

        try {
            $this->process($gate);
        } finally {
            $lock->release();
        }
    }

    // =========================================================================
    // Private
    // =========================================================================

    private function process(ProcessingGate $gate): void
    {
        Log::info("ProcessList: Starting list '{$this->list->name}' (ID {$this->list->id}).");

        $listSources = DB::table('list_sources')
            ->where('list_id', $this->list->id)
            ->where('enabled', true)
            ->where('suspended', false)
            ->get();

        if ($listSources->isEmpty()) {
            Log::info("ProcessList: List '{$this->list->name}' has no enabled sources. Skipping.");
            return;
        }

        $jobs    = [];
        $skipped = 0;

        foreach ($listSources as $listSource) {
            $subsystem = $gate->subsystemForSourceType($listSource->sourceable_type);

            if (! $gate->canProcess($subsystem)) {
                Log::info("ProcessList: Skipping list_source {$listSource->id} — '{$subsystem}' subsystem blocked.");
                $skipped++;
                continue;
            }

            try {
                $jobs[] = SourceJobResolver::resolve($listSource);
            } catch (\InvalidArgumentException $e) {
                Log::warning("ProcessList: {$e->getMessage()} for list_source {$listSource->id}. Skipping.");
                $skipped++;
            }
        }

        if (empty($jobs)) {
            Log::info("ProcessList: No processable sources for list '{$this->list->name}'.");
            return;
        }

        $listId   = $this->list->id;
        $listName = $this->list->name;

        Log::info("ProcessList: Dispatching batch of " . count($jobs) . " jobs for list '{$listName}' ({$skipped} skipped).");

        Bus::batch($jobs)
            ->then(function (Batch $batch) use ($listId, $listName) {
                Log::info("ProcessList: Batch complete for list '{$listName}' (ID {$listId}). Dispatching PublishDigest.");
                PublishDigest::dispatch($listId);
            })
            ->catch(function (Batch $batch, \Throwable $e) use ($listId, $listName) {
                Log::error("ProcessList: Batch failure in list '{$listName}' (ID {$listId}).", [
                    'failed_jobs' => $batch->failedJobs,
                    'total_jobs'  => $batch->totalJobs,
                    'error'       => $e->getMessage(),
                ]);

                AdminAlert::raiseIfNew(
                    tier: 2,
                    category: 'infrastructure',
                    title: "Batch processing failures for list '{$listName}'",
                    message: "{$batch->failedJobs} of {$batch->totalJobs} jobs failed. Error: {$e->getMessage()}",
                );
            })
            ->finally(function (Batch $batch) use ($listId, $listName) {
                Log::info("ProcessList: Batch finalised for list '{$listName}' (ID {$listId}).", [
                    'total'  => $batch->totalJobs,
                    'failed' => $batch->failedJobs,
                ]);
            })
            ->name("process-list-{$listId}")
            ->dispatch();
    }
}