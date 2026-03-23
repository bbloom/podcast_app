<?php

namespace MediaPlatform\Digest\Processing\Jobs;

use MediaPlatform\Enums\OutputType;
use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\SftpService;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\WordPressService;
use MediaPlatform\Digest\Publishing\Mail\DigestMailable;
use MediaPlatform\Digest\Publishing\Notifications\DigestEmptyNotification;
use MediaPlatform\Digest\Publishing\Notifications\DigestReadyNotification;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use MediaPlatform\Digest\Processing\Services\ProcessingGate;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * PublishDigest — the final stage of the content processing pipeline.
 *
 * WHAT IT DOES
 * ────────────
 * After all source-processing jobs (YouTube, Podcast, RSS) complete for a list,
 * ProcessList's batch ->then() callback dispatches this job. It:
 *
 *   1. Checks the ProcessingGate for publish-blocking alerts
 *   2. Loads the list and its output destination
 *   3. Uses DigestBuilderService to query pending summaries
 *   4. If nothing to publish → sends DigestEmptyNotification → exits
 *   5. Renders the appropriate Blade view (email / webpage / wordpress)
 *   6. Delivers via the correct channel:
 *        email     → sends DigestMailable
 *        webpage   → SftpService::upload(), then optional DigestReadyNotification
 *        wordpress → WordPressService::createPost()
 *   7. Calls DigestBuilderService::markAsIncluded() AFTER successful delivery
 *   8. Updates lists.last_run_at
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
        ProcessingGate       $gate,
        DigestBuilderService $builder,
        SftpService          $sftp,
        WordPressService     $wordpress,
    ): void {
        Log::info("PublishDigest: Starting for list ID {$this->listId}.");

        // ── Load the list ─────────────────────────────────────────────────────
        $list = ListModel::with('outputDestination', 'user')->find($this->listId);

        if (! $list) {
            Log::warning("PublishDigest: List ID {$this->listId} not found. Aborting.");
            return;
        }

        // ── Gate check for publish-blocking alerts ────────────────────────────
        // Only applies to SFTP and WordPress — email is never blocked by infra alerts.
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

        // ── Route to the correct delivery channel ─────────────────────────────
        $success = match ($list->output_type) {
            OutputType::Email     => $this->deliverEmail($list, $digestData),
            OutputType::Webpage   => $this->deliverWebpage($list, $digestData, $builder, $sftp),
            OutputType::Wordpress => $this->deliverWordpress($list, $digestData, $builder, $wordpress),
        };

        if (! $success) {
            // Delivery failed — do NOT mark summaries as included. They will
            // be retried. Log already written inside each deliver* method.
            Log::error("PublishDigest: Delivery failed for list '{$list->name}'. Summaries will be retried.");
            return;
        }

        // ── Mark summaries as included ONLY after successful delivery ─────────
        $builder->markAsIncluded();

        // ── Update the list's last run timestamp ──────────────────────────────
        $this->updateLastRunAt($list);

        Log::info("PublishDigest: Complete for list '{$list->name}' (ID {$list->id}).");
    }

    // =========================================================================
    // Delivery channels
    // =========================================================================

    /**
     * Deliver the digest as an email to the list owner.
     * The digest HTML is the full email body — no separate notification is sent.
     */
    private function deliverEmail(ListModel $list, array $digestData): bool
    {
        Log::info("PublishDigest: Delivering email digest for list '{$list->name}'.");

        try {
            Mail::to($list->user->email)
                ->send(new DigestMailable($list, $digestData));

            Log::info("PublishDigest: Email digest sent to {$list->user->email}.");
            return true;

        } catch (\Throwable $e) {
            Log::error("PublishDigest: Failed to send email digest.", [
                'list_id' => $list->id,
                'error'   => $e->getMessage(),
            ]);

            AdminAlert::raiseIfNew(
                tier: 2,
                category: 'infrastructure',
                title: "Digest email failed for list '{$list->name}'",
                message: "Could not send email digest for list ID {$list->id}: {$e->getMessage()}",
            );

            return false;
        }
    }

    /**
     * Render the digest as a standalone HTML page and upload it via SFTP.
     * Optionally sends a DigestReadyNotification if notify_by_email is true.
     */
    private function deliverWebpage(
        ListModel            $list,
        array                $digestData,
        DigestBuilderService $builder,
        SftpService          $sftp,
    ): bool {
        $dest = $list->outputDestination;

        if (! $dest) {
            Log::error("PublishDigest: Webpage list '{$list->name}' has no output destination.");
            return false;
        }

        // ── Build slug and render the page HTML ───────────────────────────────
        $slug     = $builder->buildSlug($list, $digestData['date']);
        $excerpt  = $builder->buildExcerpt($digestData);
        $html     = view('media_platform.digest.digest-webpage', [
            'digestData' => $digestData,
            'list'       => $list,
            'slug'       => $slug,
        ])->render();

        Log::info("PublishDigest: Uploading webpage digest via SFTP.", [
            'list'     => $list->name,
            'filename' => $slug,
            'dest_id'  => $dest->id,
        ]);

        // ── Upload via SFTP ───────────────────────────────────────────────────
        $result = $sftp->upload($dest, $slug, $html);

        if (! $result['success']) {
            Log::error("PublishDigest: SFTP upload failed for list '{$list->name}'.", [
                'message' => $result['message'],
            ]);

            AdminAlert::raiseIfNew(
                tier: 2,
                category: 'sftp',
                title: "SFTP upload failed for list '{$list->name}'",
                message: $result['message'],
            );

            return false;
        }

        Log::info("PublishDigest: Webpage uploaded to {$result['path']}.");

        // ── Send "digest ready" notification if enabled ───────────────────────
        if ($list->notify_by_email) {
            try {
                $list->user->notify(new DigestReadyNotification($list, $dest, $slug, $excerpt));
                Log::info("PublishDigest: DigestReadyNotification sent to {$list->user->email}.");
            } catch (\Throwable $e) {
                // Notification failure is non-fatal — the digest was already uploaded.
                // Log it, but return true so summaries are still marked as included.
                Log::warning("PublishDigest: Failed to send DigestReadyNotification.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return true;
    }

    /**
     * Publish the digest as a WordPress post via the REST API.
     * No notification is sent — the post is publicly accessible.
     */
    private function deliverWordpress(
        ListModel            $list,
        array                $digestData,
        DigestBuilderService $builder,
        WordPressService     $wordpress,
    ): bool {
        $dest = $list->outputDestination;

        if (! $dest) {
            Log::error("PublishDigest: WordPress list '{$list->name}' has no output destination.");
            return false;
        }

        // ── Build all the fields for the WordPress post ───────────────────────
        $slug    = $builder->buildSlug($list, $digestData['date']);
        $excerpt = $builder->buildExcerpt($digestData);
        $title   = $list->name . ' — ' . $digestData['date']->format('D, M j Y');

        // Render the WordPress-specific view (clean HTML fragment, no shell).
        $html = view('media_platform.digest.digest-wp', [
            'digestData' => $digestData,
            'list'       => $list,
        ])->render();

        Log::info("PublishDigest: Publishing WordPress post for list '{$list->name}'.", [
            'slug'   => $slug,
            'dest'   => $dest->wordpress_url,
            'status' => $dest->wordpress_post_status ?? 'publish',
        ]);

        // ── Post to WordPress ─────────────────────────────────────────────────
        $result = $wordpress->createPost(
            dest:        $dest,
            title:       $title,
            slug:        $slug,
            htmlContent: $html,
            excerpt:     $excerpt,
            date:        $digestData['date'],
        );

        if (! $result['success']) {
            Log::error("PublishDigest: WordPress post failed for list '{$list->name}'.", [
                'message' => $result['message'],
            ]);

            AdminAlert::raiseIfNew(
                tier: 2,
                category: 'infrastructure',
                title: "WordPress publish failed for list '{$list->name}'",
                message: $result['message'],
            );

            return false;
        }

        Log::info("PublishDigest: WordPress post created.", [
            'post_id' => $result['post_id'],
            'url'     => $result['url'],
        ]);

        return true;
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
            message: "Digest delivery failed after all retries. Last error: {$e->getMessage()}",
        );
    }
}