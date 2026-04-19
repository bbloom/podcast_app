<?php

namespace MediaPlatform\Digest\Processing\Jobs;

use MediaPlatform\API\v1\Models\ApiControl;
use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Publishing\Notifications\DigestEmptyNotification;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use MediaPlatform\Digest\Processing\Services\ProcessingGate;
use MediaPlatform\Digest\Publishing\Services\DeliveryStrategyResolver;
use MediaPlatform\Digest\Publishing\Services\DigestRetentionService;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PublishDigest — the final stage of the content processing pipeline.
 *
 * WHAT IT DOES
 * ────────────
 * After all source-processing jobs (YouTube, Podcast, RSS) complete for a list,
 * ProcessList's batch ->then() callback dispatches this job. It:
 *
 *   1. Auto-enables the API if the list is a static site type
 *   2. Checks the ProcessingGate for publish-blocking alerts
 *   3. Loads the list and its output destination
 *   4. Uses DigestBuilderService to query pending summaries
 *   5. If nothing to publish → sends DigestEmptyNotification → exits
 *   6. Resolves the correct delivery strategy for the output type
 *   7. Delegates delivery to the strategy
 *   8. Calls DigestBuilderService::markAsIncluded() AFTER successful delivery
 *   9. Calls DigestRetentionService::pruneForList() to enforce retention policy
 *  10. Updates lists.last_run_at
 *
 * DELIVERY STRATEGIES
 * ───────────────────
 * Each output type has its own delivery strategy implementing DigestDeliveryStrategy:
 *   - EmailDeliveryStrategy      → sends DigestMailable
 *   - WebpageDeliveryStrategy    → renders Blade, uploads via SFTP
 *   - StaticSiteDeliveryStrategy → persists JSON, fires deploy hooks
 *
 * RETENTION
 * ────────
 * After successful delivery, DigestRetentionService prunes old data:
 *   - Static site → prunes old published_digests records
 *   - Email/SFTP  → prunes old summaries where included_in_digest = true
 * The retention_count field on the list controls how many digest runs to keep.
 *
 * RETRY BEHAVIOUR
 * ───────────────
 * This job has 2 tries. If delivery fails, the summaries are NOT marked as
 * included (markAsIncluded is only called on success), so they will be
 * retried on the next attempt or the next scheduled run — whichever comes first.
 *
 * GATE CHECKS
 * ───────────
 * PublishDigest checks ProcessingGate::canPublish() which looks for Tier 3
 * alerts on the 'sftp' and 'infrastructure' categories. If blocked, the job
 * logs and returns without failure — the summaries remain pending.
 * Email output type is never blocked by the gate.
 */
class PublishDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300;

    /**
     * @param  int  $listId  The ID of the list to build and deliver a digest for.
     */
    public function __construct(
        public int $listId,
    ) {}

    // =========================================================================
    // Main handler
    // =========================================================================

    /**
     * Execute the digest publish pipeline.
     */
    public function handle(
        ProcessingGate           $gate,
        DigestBuilderService     $builder,
        DeliveryStrategyResolver $resolver,
        DigestRetentionService   $retention,
    ): void {
        Log::info("PublishDigest: Starting for list ID {$this->listId}.");

        // ── Load the list ─────────────────────────────────────────────────────
        $list = ListModel::with('outputDestination', 'user')->find($this->listId);

        if (! $list) {
            Log::warning("PublishDigest: List ID {$this->listId} not found. Aborting.");
            return;
        }

        // ── Auto-enable API for static site lists ─────────────────────────────
        if ($list->output_type === OutputType::StaticSite) {
            if (! ApiControl::getStatus()) {
                ApiControl::instance()->enable();
                Log::info("PublishDigest: API auto-enabled for static site list '{$list->name}'.");
            }
        }

        // ── Gate check for publish-blocking alerts ────────────────────────────
        // Only applies to non-email output types — email is never blocked.
        if ($list->output_type !== OutputType::Email && ! $gate->canPublish()) {
            Log::warning("PublishDigest: Publish blocked by ProcessingGate for list '{$list->name}'. Summaries remain pending.");
            return;
        }

        // ── Build the digest data structure ───────────────────────────────────
        $digestData = $builder->build($list);

        if ($digestData === null) {
            // Nothing new — notify the user and stop.
            Log::info("PublishDigest: No new summaries for list '{$list->name}'. Sending empty notification.");
            $list->user->notify(new DigestEmptyNotification($list));
            $this->updateLastRunAt($list);
            return;
        }

        // ── Resolve and execute the delivery strategy ─────────────────────────
        $strategy = $resolver->resolve($list->output_type);
        $success  = $strategy->deliver($list, $digestData, $builder);

        if (! $success) {
            // Delivery failed — do NOT mark summaries as included. They will
            // be retried. Log already written inside the strategy.
            Log::error("PublishDigest: Delivery failed for list '{$list->name}'. Summaries will be retried.");
            return;
        }

        // ── Mark summaries as included ONLY after successful delivery ─────────
        $builder->markAsIncluded();

        // ── Prune old data based on retention policy ──────────────────────────
        $retention->pruneForList($list);

        // ── Update the list's last run timestamp ──────────────────────────────
        $this->updateLastRunAt($list);

        Log::info("PublishDigest: Complete for list '{$list->name}' (ID {$list->id}).");
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Stamp the list's last_run_at timestamp. Done after every run, even when
     * the digest is empty — so the scheduler does not re-trigger within the
     * same scheduling window.
     */
    private function updateLastRunAt(ListModel $list): void
    {
        DB::table('lists')
            ->where('id', $list->id)
            ->update(['last_run_at' => now()]);
    }

    /**
     * Log the failure after all retry attempts are exhausted.
     * Raises a Tier 2 admin alert so the operator is notified.
     */
    public function failed(\Throwable $e): void
    {
        Log::error("PublishDigest: All retries exhausted for list ID {$this->listId}.", [
            'error' => $e->getMessage(),
        ]);

        AdminAlert::raiseIfNew(
            tier: 2,
            category: 'infrastructure',
            title: "PublishDigest failed for list ID {$this->listId}",
            message: "All retries exhausted. Error: {$e->getMessage()}",
        );
    }
}