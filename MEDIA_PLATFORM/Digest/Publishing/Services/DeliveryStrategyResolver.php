<?php

namespace MediaPlatform\Digest\Publishing\Services;

use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\Digest\Publishing\Contracts\DigestDeliveryStrategy;
use MediaPlatform\Digest\Publishing\Strategies\EmailDeliveryStrategy;
use MediaPlatform\Digest\Publishing\Strategies\StaticSiteDeliveryStrategy;
use MediaPlatform\Digest\Publishing\Strategies\WebpageDeliveryStrategy;

/**
 * DeliveryStrategyResolver — resolves the correct delivery strategy for an output type.
 *
 * Each output type maps to a single strategy class. The strategy is resolved
 * from the Laravel container so that its dependencies (SftpService,
 * DeployHookTriggerService, etc.) are automatically injected.
 */
class DeliveryStrategyResolver
{
    /**
     * Resolve the delivery strategy for the given output type.
     *
     * @throws \InvalidArgumentException If the output type has no registered strategy.
     */
    public function resolve(OutputType $type): DigestDeliveryStrategy
    {
        return match ($type) {
            OutputType::Email      => app(EmailDeliveryStrategy::class),
            OutputType::Webpage    => app(WebpageDeliveryStrategy::class),
            OutputType::StaticSite => app(StaticSiteDeliveryStrategy::class),
        };
    }
}