<?php

namespace MediaPlatform\Digest\Processing\Contracts;

interface LlmProviderInterface
{
    /**
     * Send a prompt to the language model and return the response text.
     *
     * @param  string  $modelSlug  The model identifier (e.g. 'gemini-2.5-flash', 'gpt-4.1', 'claude-sonnet-4-6')
     * @param  string  $prompt     The full prompt text to send
     * @return string  The model's response text
     *
     * @throws \MediaPlatform\Digest\Processing\Exceptions\LlmAuthenticationException  When API key is invalid
     * @throws \MediaPlatform\Digest\Processing\Exceptions\LlmRateLimitException       When rate limited
     * @throws \MediaPlatform\Digest\Processing\Exceptions\LlmException                For all other provider errors
     */
    public function generateContent(string $modelSlug, string $prompt): string;

    /**
     * Verify the provider's API key and model are valid and reachable.
     *
     * @param  string  $modelSlug  The model to verify
     * @return bool    True if the provider and model are operational
     */
    public function healthCheck(string $modelSlug): bool;

    /**
     * Check whether a specific model is still available from this provider.
     *
     * @param  string  $modelSlug  The model to check
     * @return bool    True if the model exists in the provider's model list
     */
    public function isModelAvailable(string $modelSlug): bool;
}
