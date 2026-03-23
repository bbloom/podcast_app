<?php

namespace MediaPlatform\Digest\Processing\Services;

use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;
use fivefilters\Readability\Readability;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArticleExtractorService
{
    /**
     * Fetch a URL and extract the main article text using Readability.
     *
     * Returns plain text suitable for passing to an LLM prompt,
     * capped at 8,000 characters. Returns null if the page cannot
     * be fetched or no article content can be extracted.
     */
    public function fetchArticleText(string $url): ?string
    {
        $html = $this->fetchHtml($url);

        if ($html === null) {
            return null;
        }

        return $this->extractText($html, $url);
    }

    /**
     * Fetch a URL and extract the main article content as clean HTML.
     *
     * Useful for future use cases where you want to preserve formatting
     * rather than stripping to plain text. Returns null on failure.
     */
    public function fetchArticleHtml(string $url): ?string
    {
        $html = $this->fetchHtml($url);

        if ($html === null) {
            return null;
        }

        return $this->extractHtml($html, $url);
    }

    /**
     * Extract plain text from an already-fetched HTML string.
     *
     * Useful when you have the HTML in hand (e.g. from a feed's
     * <content:encoded>) and want to run it through Readability
     * without an additional HTTP request.
     */
    public function extractText(string $html, string $url = 'http://fakehost'): ?string
    {
        $content = $this->extractHtml($html, $url);

        if ($content === null) {
            return null;
        }

        // Strip tags and normalise whitespace to produce clean plain text
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) < 100) {
            return null;
        }

        // Cap at ~8,000 characters to keep LLM prompts reasonable
        return mb_substr($text, 0, 8000);
    }

    /**
     * Extract the main article content as clean HTML from a raw HTML string.
     *
     * Returns null if Readability cannot identify a dominant content block.
     */
    public function extractHtml(string $html, string $url = 'http://fakehost'): ?string
    {
        try {
            $configuration = new Configuration([
                'originalURL'     => $url,
                'fixRelativeURLs' => true,
                'charThreshold'   => 100,
            ]);

            $readability = new Readability($configuration);
            $readability->parse($html);

            $content = $readability->getContent();

            return (! empty(trim($content))) ? $content : null;

        } catch (ParseException $e) {
            Log::info("ArticleExtractorService: Readability could not parse '{$url}': {$e->getMessage()}");
            return null;
        } catch (\Throwable $e) {
            Log::warning("ArticleExtractorService: Unexpected error parsing '{$url}': {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Perform the HTTP GET request and return raw HTML.
     * Returns null on connection failure or non-2xx response.
     */
    private function fetchHtml(string $url): ?string
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => config('app.name', 'Laravel') . ' RSS Reader',
                    'Accept'     => 'text/html,application/xhtml+xml,*/*',
                ])
                ->get($url);
        } catch (\Throwable $e) {
            Log::info("ArticleExtractorService: HTTP request failed for '{$url}': {$e->getMessage()}");
            return null;
        }

        if ($response->failed()) {
            Log::info("ArticleExtractorService: HTTP {$response->status()} for '{$url}'");
            return null;
        }

        $html = trim($response->body());

        return ! empty($html) ? $html : null;
    }
}