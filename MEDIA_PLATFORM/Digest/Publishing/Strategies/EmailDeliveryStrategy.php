<?php

namespace MediaPlatform\Digest\Publishing\Strategies;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use MediaPlatform\Digest\Publishing\Contracts\DigestDeliveryStrategy;
use MediaPlatform\Digest\Publishing\Mail\DigestMailable;
use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * EmailDeliveryStrategy — delivers the digest as a full HTML email.
 *
 * The digest HTML is the complete email body — no separate notification is sent.
 * The Blade view (digest-email.blade.php) renders the structured data into an
 * email-safe HTML layout.
 */
class EmailDeliveryStrategy implements DigestDeliveryStrategy
{
    public function deliver(ListModel $list, array $digestData, DigestBuilderService $builder): bool
    {
        Log::info("EmailDeliveryStrategy: Delivering email digest for list '{$list->name}'.");

        try {
            Mail::to($list->user->email)
                ->send(new DigestMailable($list, $digestData));

            Log::info("EmailDeliveryStrategy: Email digest sent to {$list->user->email}.");
            return true;

        } catch (\Throwable $e) {
            Log::error("EmailDeliveryStrategy: Failed to send email digest.", [
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
}