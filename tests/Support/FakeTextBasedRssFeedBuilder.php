<?php

namespace Tests\Support;

use Illuminate\Support\Carbon;

/**
 * FakeTextBasedRssFeedBuilder
 *
 * Builds a fake RSS 2.0 XML feed string for text-based RSS feed tests.
 *
 * Items are always ordered newest-first, matching real-world RSS feeds.
 * Each item gets a unique URL and a published date spaced evenly apart.
 *
 * USAGE
 * ─────
 * // 50 items, newest published today, each 1 day apart:
 * $xml = FakeTextBasedRssFeedBuilder::build(50, now(), 1);
 *
 * // Prepend 5 new items before the original 50:
 * $xml = FakeTextBasedRssFeedBuilder::build(55, now(), 1);
 *
 * // Build with a specific start date:
 * $xml = FakeTextBasedRssFeedBuilder::build(50, now()->subDays(3), 1);
 */
class FakeTextBasedRssFeedBuilder
{
    /**
     * Build a fake RSS 2.0 text-based feed XML string.
     *
     * @param  int     $count      Number of items to generate.
     * @param  Carbon  $newestDate The published date of the first (newest) item.
     * @param  int     $daySpacing Days between each item's published date.
     * @param  string  $prefix     Optional prefix for article slugs.
     * @return string              Full RSS XML string, ready for Http::response().
     */
    public static function build(
        int    $count,
        Carbon $newestDate,
        int    $daySpacing = 1,
        string $prefix     = 'article',
    ): string {
        $items = '';

        for ($i = 0; $i < $count; $i++) {
            $publishedAt = $newestDate->copy()->subDays($i * $daySpacing)->toRfc7231String();
            $slug        = $prefix . '-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $url         = "https://news.example.com/articles/{$slug}";
            $title       = "Article {$slug} Title";
            $description = "Description for {$slug}.";

            $items .= <<<XML

            <item>
              <title>{$title}</title>
              <link>{$url}</link>
              <description>{$description}</description>
              <pubDate>{$publishedAt}</pubDate>
            </item>
            XML;
        }

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <title>Fake Test RSS Feed</title>
            <link>https://news.example.com</link>
            <description>A fake RSS feed for testing.</description>
            {$items}
          </channel>
        </rss>
        XML;
    }

    /**
     * Build the source URL for the Nth item (1-based) with a given prefix.
     * Matches what the processor stores in source_url.
     */
    public static function sourceUrl(int $position, string $prefix = 'article'): string
    {
        $slug = $prefix . '-' . str_pad((string) $position, 3, '0', STR_PAD_LEFT);
        return "https://news.example.com/articles/{$slug}";
    }
}