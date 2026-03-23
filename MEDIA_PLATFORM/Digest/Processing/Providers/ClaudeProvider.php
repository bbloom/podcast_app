<?php

namespace MediaPlatform\Digest\Processing\Providers;

use MediaPlatform\Digest\Processing\Contracts\LlmProviderInterface;
use MediaPlatform\Digest\Processing\Exceptions\LlmAuthenticationException;
use MediaPlatform\Digest\Processing\Exceptions\LlmException;
use MediaPlatform\Digest\Processing\Exceptions\LlmRateLimitException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeProvider implements LlmProviderInterface
{
    public function generateContent(string $modelSlug, string $prompt): string
    {
        $apiKey     = config('anthropic.api_key');
        $apiVersion = config('anthropic.api_version');

        if (! $apiKey) {
            throw new LlmAuthenticationException(
                'Anthropic API key not configured. Set ANTHROPIC_API_KEY in .env.',
                providerSlug: 'anthropic',
                modelSlug: $modelSlug,
            );
        }

        try {
            $response = Http::timeout(config('anthropic.request_timeout', 60))
                ->withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version'  => $apiVersion,
                    'Content-Type'       => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model'      => $modelSlug,
                    'max_tokens' => 4096,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if ($response->status() === 401) {
                throw new LlmAuthenticationException(
                    'Anthropic API key invalid or expired.',
                    providerSlug: 'anthropic',
                    modelSlug: $modelSlug,
                );
            }

            if ($response->status() === 429) {
                throw new LlmRateLimitException(
                    'Anthropic rate limit exceeded.',
                    providerSlug: 'anthropic',
                    modelSlug: $modelSlug,
                );
            }

            if ($response->failed()) {
                $error = $response->json('error.message', 'Unknown error');
                throw new LlmException(
                    "Anthropic error: {$error}",
                    providerSlug: 'anthropic',
                    modelSlug: $modelSlug,
                );
            }

            return $response->json('content.0.text', '');

        } catch (LlmException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new LlmException(
                "Anthropic request failed: {$e->getMessage()}",
                providerSlug: 'anthropic',
                modelSlug: $modelSlug,
                previous: $e,
            );
        }
    }

    public function healthCheck(string $modelSlug): bool
    {
        try {
            $result = $this->generateContent($modelSlug, 'Reply with exactly: OK');
            return str_contains(strtolower($result), 'ok');
        } catch (\Throwable $e) {
            Log::warning("ClaudeProvider health check failed: {$e->getMessage()}");
            return false;
        }
    }

    public function isModelAvailable(string $modelSlug): bool
    {
        try {
            $apiKey = config('anthropic.api_key');

            $response = Http::timeout(10)
                ->withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version'  => config('anthropic.api_version'),
                ])
                ->get('https://api.anthropic.com/v1/models');

            if ($response->failed()) {
                return false;
            }

            $models = collect($response->json('data', []))->pluck('id');

            return $models->contains($modelSlug);

        } catch (\Throwable $e) {
            Log::warning("ClaudeProvider model availability check failed: {$e->getMessage()}");
            return false;
        }
    }
}