<?php

namespace MediaPlatform\Digest\Publishing\Strategies;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use MediaPlatform\Digest\Publishing\Contracts\DigestDeliveryStrategy;
use MediaPlatform\Digest\Publishing\Models\PublishedDigest;
use MediaPlatform\Digest\Publishing\Notifications\StaticSiteDigestReadyNotification;
use MediaPlatform\StaticSiteDeployHooks\Services\DeployHookTriggerService;
use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use Illuminate\Support\Facades\Log;

/**
 * StaticSiteDeliveryStrategy — persists digest data and fires deploy hooks.
 *
 * DELIVERY FLOW
 * ─────────────
 * 1. Build the JSON payload from the structured digest data.
 * 2. Persist a PublishedDigest record.
 * 3. Fire all enabled deploy hooks attached to the list.
 * 4. Optionally send a notification email.
 *
 * NOTE: Retention pruning of old published_digests records is handled
 * centrally by DigestRetentionService, called from PublishDigest after
 * markAsIncluded(). This strategy does not prune.
 *
 * The published digest record is the durable artifact. Deploy hook failure
 * is logged but does not fail the delivery — the data is persisted and can
 * be served by the API. A manual trigger can be used to retry the build.
 */
class StaticSiteDeliveryStrategy implements DigestDeliveryStrategy
{
    public function __construct(
        private DeployHookTriggerService $triggerService,
    ) {}

    public function deliver(ListModel $list, array $digestData, DigestBuilderService $builder): bool
    {
        Log::info("StaticSiteDeliveryStrategy: Starting delivery for list '{$list->name}'.");

        // ── Build payload ─────────────────────────────────────────────────────
        $slug    = $builder->buildSlug($list, $digestData['date']);
        $excerpt = $builder->buildExcerpt($digestData);
        $payload = $this->buildPayload($digestData);

        // ── Persist PublishedDigest ───────────────────────────────────────────
        try {
            $publishedDigest = PublishedDigest::create([
                'list_id'      => $list->id,
                'user_id'      => $list->user_id,
                'slug'         => $slug,
                'digest_date'  => $digestData['date']->toDateString(),
                'total_items'  => $digestData['total_items'],
                'source_count' => $digestData['source_count'],
                'payload'      => $payload,
            ]);

            Log::info("StaticSiteDeliveryStrategy: PublishedDigest created.", [
                'id'   => $publishedDigest->id,
                'slug' => $slug,
            ]);
        } catch (\Throwable $e) {
            Log::error("StaticSiteDeliveryStrategy: Failed to persist PublishedDigest.", [
                'list_id' => $list->id,
                'error'   => $e->getMessage(),
            ]);

            AdminAlert::raiseIfNew(
                tier: 2,
                category: 'infrastructure',
                title: "Static site digest failed for list '{$list->name}'",
                message: "Could not persist published digest for list ID {$list->id}: {$e->getMessage()}",
            );

            return false;
        }

        // ── Fire deploy hooks ─────────────────────────────────────────────────
        $this->fireDeployHooks($list, $publishedDigest);

        // ── Send notification if enabled ──────────────────────────────────────
        if ($list->notify_by_email) {
            try {
                $list->user->notify(new StaticSiteDigestReadyNotification(
                    list:    $list,
                    slug:    $slug,
                    excerpt: $excerpt,
                ));
                Log::info("StaticSiteDeliveryStrategy: Notification sent to {$list->user->email}.");
            } catch (\Throwable $e) {
                Log::warning("StaticSiteDeliveryStrategy: Failed to send notification.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return true;
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Build the JSON payload from the structured digest data.
     *
     * Extracts the fields that the API will serve to the static site generator.
     * The payload is an array of source groups, each containing an array of items.
     * Source HTML in summary_html is preserved — the static site controls rendering.
     */
    private function buildPayload(array $digestData): array
    {
        $groups = [];

        foreach ($digestData['groups'] as $group) {
            $items = [];

            foreach ($group['items'] as $item) {
                $items[] = [
                    'source_url'          => $item->source_url,
                    'source_title'        => $item->source_title ?? 'Untitled',
                    'source_description'  => $item->source_description ?? null,
                    'source_published_at' => $item->source_published_at
                        ? \Illuminate\Support\Carbon::parse($item->source_published_at)->toIso8601String()
                        : null,
                    'summary_html'        => $item->summary_html,
                ];
            }

            $groups[] = [
                'source_name' => $group['source_name'],
                'source_type' => $group['source_type'],
                'items'       => $items,
            ];
        }

        return $groups;
    }

    /**
     * Fire all enabled deploy hooks attached to this list.
     * Records the outcome on the PublishedDigest record.
     * Deploy hook failure is logged but does not fail the delivery.
     */
    private function fireDeployHooks(ListModel $list, PublishedDigest $publishedDigest): void
    {
        $hooks = $list->deployHooks()->where('enabled', true)->get();

        if ($hooks->isEmpty()) {
            Log::warning("StaticSiteDeliveryStrategy: No enabled deploy hooks for list '{$list->name}'.");
            return;
        }

        $allSucceeded = true;

        foreach ($hooks as $hook) {
            $result = $this->triggerService->trigger($hook);

            if (! $result->succeeded()) {
                $allSucceeded = false;
                Log::warning("StaticSiteDeliveryStrategy: Deploy hook '{$hook->label}' failed.", [
                    'hook_id' => $hook->id,
                    'error'   => $result->errorMessage(),
                ]);
            }
        }

        $publishedDigest->update([
            'deploy_hook_fired_at' => now(),
        ]);

        if (! $allSucceeded) {
            AdminAlert::raiseIfNew(
                tier: 2,
                category: 'infrastructure',
                title: "Deploy hook failure for list '{$list->name}'",
                message: "One or more deploy hooks failed for list ID {$list->id}. The digest data is persisted and can be served by the API. Use the manual trigger to retry.",
            );
        }

        Log::info("StaticSiteDeliveryStrategy: Deploy hooks fired for list '{$list->name}'.", [
            'hook_count' => $hooks->count(),
            'all_ok'     => $allSucceeded,
        ]);
    }
}