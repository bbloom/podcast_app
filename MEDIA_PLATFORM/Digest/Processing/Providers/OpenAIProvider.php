<?php

namespace MediaPlatform\Digest\Processing\Providers;

use MediaPlatform\Digest\Processing\Contracts\LlmProviderInterface;
use MediaPlatform\Digest\Processing\Exceptions\LlmAuthenticationException;
use MediaPlatform\Digest\Processing\Exceptions\LlmException;
use MediaPlatform\Digest\Processing\Exceptions\LlmRateLimitException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider implements LlmProviderInterface
{
    public function generateContent(string $modelSlug, string $prompt): string
    {
        $apiKey = config('openai.api_key');

        if (! $apiKey) {
            throw new LlmAuthenticationException(
                'OpenAI API key not configured. Set OPENAI_API_KEY in .env.',
                providerSlug: 'openai',
                modelSlug: $modelSlug,
            );
        }

        try {
            $response = Http::timeout(config('openai.request_timeout', 60))
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type'  => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'    => $modelSlug,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if ($response->status() === 401) {
                throw new LlmAuthenticationException(
                    'OpenAI API key invalid or expired.',
                    providerSlug: 'openai',
                    modelSlug: $modelSlug,
                );
            }

            if ($response->status() === 429) {
                throw new LlmRateLimitException(
                    'OpenAI rate limit exceeded.',
                    providerSlug: 'openai',
                    modelSlug: $modelSlug,
                );
            }

            if ($response->failed()) {
                $error = $response->json('error.message', 'Unknown error');
                throw new LlmException(
                    "OpenAI error: {$error}",
                    providerSlug: 'openai',
                    modelSlug: $modelSlug,
                );
            }

            return $response->json('choices.0.message.content', '');

        } catch (LlmException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new LlmException(
                "OpenAI request failed: {$e->getMessage()}",
                providerSlug: 'openai',
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
            Log::warning("OpenAIProvider health check failed: {$e->getMessage()}");
            return false;
        }
    }

    public function isModelAvailable(string $modelSlug): bool
    {
        try {
            $apiKey = config('openai.api_key');

            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => "Bearer {$apiKey}"])
                ->get('https://api.openai.com/v1/models');

            if ($response->failed()) {
                return false;
            }

            $models = collect($response->json('data', []))->pluck('id');

            return $models->contains($modelSlug);

        } catch (\Throwable $e) {
            Log::warning("OpenAIProvider model availability check failed: {$e->getMessage()}");
            return false;
        }
    }
}