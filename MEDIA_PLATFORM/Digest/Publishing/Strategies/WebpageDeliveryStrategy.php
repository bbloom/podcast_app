<?php

namespace MediaPlatform\Digest\Publishing\Strategies;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\SftpService;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use MediaPlatform\Digest\Publishing\Contracts\DigestDeliveryStrategy;
use MediaPlatform\Digest\Publishing\Notifications\DigestReadyNotification;
use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use Illuminate\Support\Facades\Log;

/**
 * WebpageDeliveryStrategy — renders the digest as HTML and uploads via SFTP.
 *
 * Renders the digest-webpage.blade.php Blade view into a standalone HTML page,
 * uploads it to the configured SFTP output destination, and optionally sends
 * a DigestReadyNotification email with a link to the published page.
 */
class WebpageDeliveryStrategy implements DigestDeliveryStrategy
{
    public function __construct(
        private SftpService $sftp,
    ) {}

    public function deliver(ListModel $list, array $digestData, DigestBuilderService $builder): bool
    {
        $dest = $list->outputDestination;

        if (! $dest) {
            Log::error("WebpageDeliveryStrategy: Webpage list '{$list->name}' has no output destination.");
            return false;
        }

        // ── Build slug and render the page HTML ───────────────────────────────
        $slug    = $builder->buildSlug($list, $digestData['date']);
        $excerpt = $builder->buildExcerpt($digestData);
        $html    = view('media_platform.digest.digest-webpage', [
            'digestData' => $digestData,
            'list'       => $list,
            'slug'       => $slug,
        ])->render();

        Log::info("WebpageDeliveryStrategy: Uploading webpage digest via SFTP.", [
            'list'     => $list->name,
            'filename' => $slug,
            'dest_id'  => $dest->id,
        ]);

        // ── Upload via SFTP ───────────────────────────────────────────────────
        $result = $this->sftp->upload($dest, $slug, $html);

        if (! $result['success']) {
            Log::error("WebpageDeliveryStrategy: SFTP upload failed for list '{$list->name}'.", [
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

        Log::info("WebpageDeliveryStrategy: Webpage uploaded to {$result['path']}.");

        // ── Send "digest ready" notification if enabled ───────────────────────
        if ($list->notify_by_email) {
            try {
                $list->user->notify(new DigestReadyNotification($list, $dest, $slug, $excerpt));
                Log::info("WebpageDeliveryStrategy: DigestReadyNotification sent to {$list->user->email}.");
            } catch (\Throwable $e) {
                // Notification failure is non-fatal — the digest was already uploaded.
                // Log it, but return true so summaries are still marked as included.
                Log::warning("WebpageDeliveryStrategy: Failed to send DigestReadyNotification.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return true;
    }
}