<?php

namespace MediaPlatform\Digest\Processing\Services;

use MediaPlatform\Configuration\LanguageModels\Models\LanguageModel;
use Illuminate\Support\Facades\Log;

class LlmResolver
{
    /**
     * Resolve the enabled language model for a given use case slug.
     * Returns the model slug string (e.g. 'gemini-2.5-flash') or null if none found.
     */
    public static function resolveModelSlug(string $useCaseSlug): ?string
    {
        $model = self::resolveModel($useCaseSlug);

        return $model?->slug;
    }

    /**
     * Resolve the full LanguageModel (with provider) for a given use case slug.
     */
    public static function resolveModel(string $useCaseSlug): ?LanguageModel
    {
        $model = LanguageModel::with('provider')
            ->whereHas('useCases', fn ($q) => $q->where('slug', $useCaseSlug))
            ->where('enabled', true)
            ->first();

        if (! $model) {
            Log::warning("LlmResolver: No enabled language model found for use case '{$useCaseSlug}'.");
        }

        return $model;
    }

    /**
     * Resolve the provider slug for a given use case.
     * Returns e.g. 'google', 'openai', 'anthropic'.
     */
    public static function resolveProviderSlug(string $useCaseSlug): ?string
    {
        $model = self::resolveModel($useCaseSlug);

        return $model?->provider?->slug;
    }
}
