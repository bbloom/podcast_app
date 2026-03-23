<?php

namespace Tests\Support;

use Illuminate\Support\Carbon;

/**
 * FakePodcastFeedBuilder
 *
 * Builds a fake podcast RSS 2.0 XML feed string for use in tests.
 *
 * Items are always ordered newest-first, matching real-world podcast feeds.
 * Each item gets a unique URL and a published date spaced evenly apart.
 *
 * USAGE
 * ─────
 * // 50 items, newest published today, each 1 day apart:
 * $xml = FakePodcastFeedBuilder::build(50, now(), 1);
 *
 * // Prepend 5 new items before the original 50:
 * $xml = FakePodcastFeedBuilder::build(55, now(), 1);
 *
 * // Build with a specific start date:
 * $xml = FakePodcastFeedBuilder::build(50, now()->subDays(3), 1);
 */
class FakePodcastFeedBuilder
{
    /**
     * Build a fake RSS 2.0 podcast feed XML string.
     *
     * @param  int     $count      Number of items to generate.
     * @param  Carbon  $newestDate The published date of the first (newest) item.
     * @param  int     $daySpacing Days between each item's published date.
     * @param  string  $prefix     Optional prefix for episode slugs.
     * @return string              Full RSS XML string, ready for Http::response().
     */
    public static function build(
        int    $count,
        Carbon $newestDate,
        int    $daySpacing = 1,
        string $prefix     = 'episode',
    ): string {
        $items = '';

        for ($i = 0; $i < $count; $i++) {
            $publishedAt = $newestDate->copy()->subDays($i * $daySpacing)->toRfc7231String();
            $slug        = $prefix . '-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $url         = "https://podcast.example.com/episodes/{$slug}";
            $title       = "Episode {$slug} Title";
            $description = "Description for {$slug}.";
            $encoded     = "<p>Full show notes for {$slug}. Rich content here.</p>";

            $items .= <<<XML

            <item>
              <title>{$title}</title>
              <link>{$url}</link>
              <guid isPermaLink="false">{$url}</guid>
              <description>{$description}</description>
              <content:encoded><![CDATA[{$encoded}]]></content:encoded>
              <enclosure url="https://media.example.com/{$slug}.mp3" length="12345678" type="audio/mpeg"/>
              <pubDate>{$publishedAt}</pubDate>
            </item>
            XML;
        }

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"
            xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
            xmlns:content="http://purl.org/rss/1.0/modules/content/">
          <channel>
            <title>Fake Test Podcast</title>
            <link>https://podcast.example.com</link>
            <description>A fake podcast feed for testing.</description>
            {$items}
          </channel>
        </rss>
        XML;
    }

    /**
     * Build the source URL for the Nth item (1-based) with a given prefix.
     * Matches what the processor stores in source_url.
     */
    public static function sourceUrl(int $position, string $prefix = 'episode'): string
    {
        $slug = $prefix . '-' . str_pad((string) $position, 3, '0', STR_PAD_LEFT);
        return "https://podcast.example.com/episodes/{$slug}";
    }
}