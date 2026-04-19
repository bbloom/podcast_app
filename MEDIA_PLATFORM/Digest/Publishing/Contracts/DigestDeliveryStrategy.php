<?php

namespace MediaPlatform\Digest\Publishing\Contracts;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;

/**
 * DigestDeliveryStrategy — contract for delivering a built digest.
 *
 * Each output type (email, webpage, static site) implements this interface.
 * The PublishDigest job resolves the correct strategy via DeliveryStrategyResolver
 * and delegates delivery to it.
 *
 * Implementations must:
 *   - Return true on successful delivery
 *   - Return false on failure (summaries will NOT be marked as included)
 *   - Handle their own error logging and AdminAlert creation
 *   - Handle notifications (e.g. DigestReadyNotification) if applicable
 */
interface DigestDeliveryStrategy
{
    /**
     * Deliver a built digest for the given list.
     *
     * @param  ListModel            $list       The list being published.
     * @param  array                $digestData Structured digest from DigestBuilderService::build().
     * @param  DigestBuilderService $builder    The builder instance (for slug/excerpt generation).
     * @return bool                             True on success, false on failure.
     */
    public function deliver(ListModel $list, array $digestData, DigestBuilderService $builder): bool;
}