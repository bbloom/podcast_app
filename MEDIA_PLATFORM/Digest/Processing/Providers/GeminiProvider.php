<?php

namespace MediaPlatform\Digest\Processing\Providers;

use MediaPlatform\Digest\Processing\Contracts\LlmProviderInterface;
use MediaPlatform\Digest\Processing\Exceptions\LlmAuthenticationException;
use MediaPlatform\Digest\Processing\Exceptions\LlmException;
use MediaPlatform\Digest\Processing\Exceptions\LlmRateLimitException;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiProvider implements LlmProviderInterface
{
    public function generateContent(string $modelSlug, string $prompt): string
    {
        try {
            $response = Gemini::generativeModel(model: $modelSlug)
                ->generateContent($prompt);

            return $response->text();

        } catch (\Throwable $e) {
            $message = $e->getMessage();

            if (str_contains($message, '401') || str_contains($message, 'API key')) {
                throw new LlmAuthenticationException(
                    'Gemini API key invalid or expired.',
                    providerSlug: 'google',
                    modelSlug: $modelSlug,
                    previous: $e,
                );
            }

            if (str_contains($message, '429') || str_contains($message, 'rate')) {
                throw new LlmRateLimitException(
                    'Gemini rate limit exceeded.',
                    providerSlug: 'google',
                    modelSlug: $modelSlug,
                    previous: $e,
                );
            }

            throw new LlmException(
                "Gemini error: {$message}",
                providerSlug: 'google',
                modelSlug: $modelSlug,
                previous: $e,
            );
        }
    }

    public function healthCheck(string $modelSlug): bool
    {
        try {
            $response = Gemini::generativeModel(model: $modelSlug)
                ->generateContent('Reply with exactly: OK');

            return str_contains(strtolower($response->text()), 'ok');

        } catch (\Throwable $e) {
            Log::warning("GeminiProvider health check failed: {$e->getMessage()}");
            return false;
        }
    }

    public function isModelAvailable(string $modelSlug): bool
    {
        try {
            $apiKey = config('gemini.api_key');

            $response = Http::timeout(10)
                ->get("https://generativelanguage.googleapis.com/v1/models?key={$apiKey}");

            if ($response->failed()) {
                return false;
            }

            $models = collect($response->json('models', []))->pluck('name')->map(function ($name) {
                return str_replace('models/', '', $name);
            });

            return $models->contains($modelSlug);

        } catch (\Throwable $e) {
            Log::warning("GeminiProvider model availability check failed: {$e->getMessage()}");
            return false;
        }
    }
}
