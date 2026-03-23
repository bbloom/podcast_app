<?php

namespace MediaPlatform\Digest\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class RssFeedService
{
    /**
     * Fetch and parse an RSS feed URL into a normalised array.
     *
     * Returns:
     *   ['success' => true,  'data' => [...]]
     *   ['success' => false, 'message' => '...']
     *
     * The data array contains:
     *   title       – feed / podcast title
     *   description – feed description (may be null)
     *   site_url    – the website link from <link> (may be null)
     *   rss_url     – the original feed URL that was fetched
     *   thumbnail   – cover art URL: prefers <itunes:image>, falls back to <image><url>
     */
    public function fetch(string $rssUrl): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Accept'     => 'application/rss+xml, application/xml, text/xml, */*',
                    'User-Agent' => config('app.name', 'Laravel') . ' RSS Reader',
                ])
                ->get($rssUrl);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => 'Could not reach the feed. The server returned HTTP ' . $response->status() . '.',
                ];
            }

            $body = $response->body();

            if (empty(trim($body))) {
                return [
                    'success' => false,
                    'message' => 'The feed returned an empty response.',
                ];
            }

            return $this->parse($body, $rssUrl);

        } catch (Throwable $e) {
            return $this->humanizeError($e->getMessage());
        }
    }

    /**
     * Parse raw XML into a normalised feed array.
     */
    private function parse(string $xml, string $rssUrl): array
    {
        // Suppress libxml errors so we can catch them ourselves
        $previousUseErrors = libxml_use_internal_errors(true);

        try {
            $doc = simplexml_load_string($xml);

            if ($doc === false) {
                return [
                    'success' => false,
                    'message' => 'The URL does not contain valid XML. Please check the feed URL.',
                ];
            }

            // RSS 2.0 — the most common format (podcasts + text feeds)
            if (isset($doc->channel)) {
                return [
                    'success' => true,
                    'data'    => $this->parseRss2($doc->channel, $rssUrl),
                ];
            }

            // Atom feeds
            if ($doc->getName() === 'feed') {
                return [
                    'success' => true,
                    'data'    => $this->parseAtom($doc, $rssUrl),
                ];
            }

            // RSS 1.0 (RDF)
            $namespaces = $doc->getNamespaces(true);
            if (isset($namespaces['']) && str_contains($namespaces[''], 'purl.org/rss')) {
                $rssNs = $doc->children($namespaces['']);
                if (isset($rssNs->channel)) {
                    return [
                        'success' => true,
                        'data'    => $this->parseRss1($rssNs->channel, $rssUrl),
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'The URL returned XML but it does not appear to be an RSS or Atom feed.',
            ];

        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseErrors);
        }
    }

    /**
     * Extract data from an RSS 2.0 <channel> element.
     */
    private function parseRss2(\SimpleXMLElement $channel, string $rssUrl): array
    {
        $title       = $this->text($channel->title);
        $description = $this->text($channel->description);
        $siteUrl     = $this->text($channel->link);
        $thumbnail   = null;

        // 1. Podcast cover art: <itunes:image href="...">
        $itunes = $channel->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
        if (isset($itunes->image)) {
            $thumbnail = (string) $itunes->image->attributes()['href'] ?? null;
        }

        // 2. Fallback: channel-level <image><url>
        if (! $thumbnail && isset($channel->image->url)) {
            $thumbnail = $this->text($channel->image->url);
        }

        // 3. Fallback: <media:thumbnail> at channel level
        if (! $thumbnail) {
            $media = $channel->children('http://search.yahoo.com/mrss/');
            if (isset($media->thumbnail)) {
                $thumbnail = (string) $media->thumbnail->attributes()['url'] ?? null;
            }
        }

        // Use itunes:summary as fallback description for podcasts
        if (! $description && isset($itunes->summary)) {
            $description = $this->text($itunes->summary);
        }

        return [
            'title'       => $title ?: 'Untitled Feed',
            'description' => $description,
            'site_url'    => $siteUrl,
            'rss_url'     => $rssUrl,
            'thumbnail'   => $thumbnail,
        ];
    }

    /**
     * Extract data from an Atom <feed> element.
     */
    private function parseAtom(\SimpleXMLElement $feed, string $rssUrl): array
    {
        $title       = $this->text($feed->title);
        $description = $this->text($feed->subtitle);
        $siteUrl     = null;
        $thumbnail   = null;

        // Atom links: find rel="alternate" for the site URL
        foreach ($feed->link as $link) {
            $rel = (string) ($link->attributes()['rel'] ?? 'alternate');
            if ($rel === 'alternate') {
                $siteUrl = (string) $link->attributes()['href'];
                break;
            }
        }

        // Atom icon or logo as thumbnail
        if (isset($feed->logo)) {
            $thumbnail = $this->text($feed->logo);
        } elseif (isset($feed->icon)) {
            $thumbnail = $this->text($feed->icon);
        }

        return [
            'title'       => $title ?: 'Untitled Feed',
            'description' => $description,
            'site_url'    => $siteUrl,
            'rss_url'     => $rssUrl,
            'thumbnail'   => $thumbnail,
        ];
    }

    /**
     * Extract data from an RSS 1.0 (RDF) <channel> element.
     */
    private function parseRss1(\SimpleXMLElement $channel, string $rssUrl): array
    {
        return [
            'title'       => $this->text($channel->title) ?: 'Untitled Feed',
            'description' => $this->text($channel->description),
            'site_url'    => $this->text($channel->link),
            'rss_url'     => $rssUrl,
            'thumbnail'   => null,
        ];
    }

    /**
     * Safely extract trimmed text from a SimpleXMLElement, returning null if empty.
     */
    private function text(?\SimpleXMLElement $element): ?string
    {
        if ($element === null) {
            return null;
        }

        $value = trim((string) $element);

        return $value !== '' ? $value : null;
    }

    /**
     * Convert common HTTP / network errors into user-friendly messages.
     */
    private function humanizeError(string $message): array
    {
        if (str_contains($message, 'Could not resolve host') || str_contains($message, 'cURL error 6')) {
            return [
                'success' => false,
                'message' => 'Could not resolve the host. Please check the URL.',
            ];
        }

        if (str_contains($message, 'Connection refused') || str_contains($message, 'cURL error 7')) {
            return [
                'success' => false,
                'message' => 'Connection refused. The server may be down.',
            ];
        }

        if (str_contains($message, 'timed out') || str_contains($message, 'cURL error 28')) {
            return [
                'success' => false,
                'message' => 'The request timed out. The server may be slow or unreachable.',
            ];
        }

        if (str_contains($message, 'SSL') || str_contains($message, 'cURL error 60')) {
            return [
                'success' => false,
                'message' => 'SSL certificate error. The feed URL may need https:// or the server certificate is invalid.',
            ];
        }

        return [
            'success' => false,
            'message' => 'Could not fetch the feed. Please double-check the URL and try again.',
        ];
    }
}