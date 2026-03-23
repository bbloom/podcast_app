<?php

namespace MediaPlatform\Digest\Processing\Services;

use MediaPlatform\Digest\Processing\Contracts\LlmProviderInterface;
use MediaPlatform\Digest\Processing\Exceptions\LlmException;
use MediaPlatform\Digest\Processing\Providers\ClaudeProvider;
use MediaPlatform\Digest\Processing\Providers\GeminiProvider;
use MediaPlatform\Digest\Processing\Providers\OpenAIProvider;
use Illuminate\Support\Facades\Log;

class LlmService
{
    /**
     * Provider instances, keyed by provider slug.
     */
    private array $providers = [];

    /**
     * Send a prompt to the language model configured for the given use case.
     *
     * @param  string  $useCaseSlug  e.g. 'digest-processing'
     * @param  string  $prompt       The full prompt text
     * @return string  The model's response text
     *
     * @throws LlmException If no model is configured or the provider call fails
     */
    public function generateContent(string $useCaseSlug, string $prompt): string
    {
        $model = LlmResolver::resolveModel($useCaseSlug);

        if (! $model) {
            throw new LlmException(
                "No enabled language model found for use case '{$useCaseSlug}'.",
            );
        }

        $providerSlug = $model->provider->slug;
        $modelSlug    = $model->slug;

        $provider = $this->resolveProvider($providerSlug);

        $response = $provider->generateContent($modelSlug, $prompt);

        // Guard against absurdly long responses
        if (strlen($response) > 10000) {
            $response = substr($response, 0, 10000) . '... <em>(truncated)</em>';
        }

        return $response;
    }

    /**
     * Run a health check for the model configured for the given use case.
     */
    public function healthCheck(string $useCaseSlug): bool
    {
        $model = LlmResolver::resolveModel($useCaseSlug);

        if (! $model) {
            return false;
        }

        $provider = $this->resolveProvider($model->provider->slug);

        return $provider->healthCheck($model->slug);
    }

    /**
     * Check if the model configured for a use case is still available.
     */
    public function isModelAvailable(string $useCaseSlug): bool
    {
        $model = LlmResolver::resolveModel($useCaseSlug);

        if (! $model) {
            return false;
        }

        $provider = $this->resolveProvider($model->provider->slug);

        return $provider->isModelAvailable($model->slug);
    }

    /**
     * Get the currently configured provider slug and model slug for a use case.
     * Useful for health check reporting.
     */
    public function resolveInfo(string $useCaseSlug): ?array
    {
        $model = LlmResolver::resolveModel($useCaseSlug);

        if (! $model) {
            return null;
        }

        return [
            'provider_slug' => $model->provider->slug,
            'provider_name' => $model->provider->name,
            'model_slug'    => $model->slug,
            'model_name'    => $model->name,
        ];
    }

    /**
     * Resolve a provider instance by slug. Cached for the lifetime of this request.
     */
    private function resolveProvider(string $providerSlug): LlmProviderInterface
    {
        if (! isset($this->providers[$providerSlug])) {
            $this->providers[$providerSlug] = match ($providerSlug) {
                'google'    => new GeminiProvider(),
                'openai'    => new OpenAIProvider(),
                'anthropic' => new ClaudeProvider(),
                default     => throw new LlmException(
                    "Unknown LLM provider: '{$providerSlug}'. Register it in LlmService::resolveProvider().",
                ),
            };
        }

        return $this->providers[$providerSlug];
    }
}
