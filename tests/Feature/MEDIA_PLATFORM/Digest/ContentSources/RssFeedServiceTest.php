<?php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\ContentSources;

use MediaPlatform\Digest\Services\RssFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RssFeedServiceTest extends TestCase
{
    use RefreshDatabase;

    private RssFeedService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RssFeedService();
    }

    // ── RSS 2.0 parsing ────────────────────────────────────────────────────────

    public function test_parses_rss2_feed_with_all_fields(): void
    {
        Http::fake([
            'example.com/*' => Http::response($this->rss2Feed()),
        ]);

        $result = $this->service->fetch('https://example.com/feed.xml');

        $this->assertTrue($result['success']);
        $this->assertEquals('Tech Daily',              $result['data']['title']);
        $this->assertEquals('Latest tech news.',       $result['data']['description']);
        $this->assertEquals('https://example.com',     $result['data']['site_url']);
        $this->assertEquals('https://example.com/feed.xml', $result['data']['rss_url']);
        $this->assertNull($result['data']['thumbnail']);
    }

    public function test_parses_rss2_feed_with_image_element(): void
    {
        Http::fake([
            'example.com/*' => Http::response($this->rss2FeedWithImage()),
        ]);

        $result = $this->service->fetch('https://example.com/feed.xml');

        $this->assertTrue($result['success']);
        $this->assertEquals('https://example.com/logo.png', $result['data']['thumbnail']);
    }

    public function test_parses_rss2_feed_with_missing_optional_fields(): void
    {
        Http::fake([
            'example.com/*' => Http::response($this->rss2FeedMinimal()),
        ]);

        $result = $this->service->fetch('https://example.com/feed.xml');

        $this->assertTrue($result['success']);
        $this->assertEquals('Minimal Feed', $result['data']['title']);
        $this->assertNull($result['data']['description']);
        $this->assertNull($result['data']['site_url']);
        $this->assertNull($result['data']['thumbnail']);
    }

    // ── Podcast (itunes) parsing ───────────────────────────────────────────────

    public function test_parses_podcast_feed_with_itunes_image(): void
    {
        Http::fake([
            'example.com/*' => Http::response($this->podcastFeed()),
        ]);

        $result = $this->service->fetch('https://example.com/podcast.xml');

        $this->assertTrue($result['success']);
        $this->assertEquals('My Podcast',                    $result['data']['title']);
        $this->assertEquals('A great podcast.',              $result['data']['description']);
        $this->assertEquals('https://example.com/cover.jpg', $result['data']['thumbnail']);
    }

    public function test_itunes_image_takes_priority_over_channel_image(): void
    {
        Http::fake([
            'example.com/*' => Http::response($this->podcastFeedWithBothImages()),
        ]);

        $result = $this->service->fetch('https://example.com/podcast.xml');

        $this->assertTrue($result['success']);
        $this->assertEquals('https://example.com/itunes-cover.jpg', $result['data']['thumbnail']);
    }

    public function test_falls_back_to_itunes_summary_when_description_missing(): void
    {
        Http::fake([
            'example.com/*' => Http::response($this->podcastFeedSummaryOnly()),
        ]);

        $result = $this->service->fetch('https://example.com/podcast.xml');

        $this->assertTrue($result['success']);
        $this->assertEquals('Summary as description.', $result['data']['description']);
    }

    // ── Atom parsing ───────────────────────────────────────────────────────────

    public function test_parses_atom_feed(): void
    {
        Http::fake([
            'example.com/*' => Http::response($this->atomFeed()),
        ]);

        $result = $this->service->fetch('https://example.com/atom.xml');

        $this->assertTrue($result['success']);
        $this->assertEquals('Atom Blog',                      $result['data']['title']);
        $this->assertEquals('An atom feed.',                  $result['data']['description']);
        $this->assertEquals('https://example.com',            $result['data']['site_url']);
        $this->assertEquals('https://example.com/logo.png',   $result['data']['thumbnail']);
    }

    // ── Error handling ─────────────────────────────────────────────────────────

    public function test_returns_error_on_http_failure(): void
    {
        Http::fake([
            'example.com/*' => Http::response('Not Found', 404),
        ]);

        $result = $this->service->fetch('https://example.com/missing.xml');

        $this->assertFalse($result['success']);
        $this->assertStringContains('404', $result['message']);
    }

    public function test_returns_error_on_empty_response(): void
    {
        Http::fake([
            'example.com/*' => Http::response(''),
        ]);

        $result = $this->service->fetch('https://example.com/empty.xml');

        $this->assertFalse($result['success']);
        $this->assertStringContains('empty', $result['message']);
    }

    public function test_returns_error_on_invalid_xml(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><body>Not XML</body></html>'),
        ]);

        $result = $this->service->fetch('https://example.com/page.html');

        $this->assertFalse($result['success']);
        $this->assertStringContains('does not appear to be', $result['message']);
    }

    public function test_returns_error_on_malformed_xml(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<<<not valid xml at all'),
        ]);

        $result = $this->service->fetch('https://example.com/bad.xml');

        $this->assertFalse($result['success']);
        $this->assertStringContains('valid XML', $result['message']);
    }

    public function test_preserves_original_url_as_rss_url(): void
    {
        Http::fake([
            'example.com/*' => Http::response($this->rss2Feed()),
        ]);

        $url    = 'https://example.com/my-custom-feed.xml';
        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
        $this->assertEquals($url, $result['data']['rss_url']);
    }

    public function test_titles_default_to_untitled_feed(): void
    {
        Http::fake([
            'example.com/*' => Http::response($this->rss2FeedNoTitle()),
        ]);

        $result = $this->service->fetch('https://example.com/feed.xml');

        $this->assertTrue($result['success']);
        $this->assertEquals('Untitled Feed', $result['data']['title']);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertStringContainsString($needle, $haystack);
    }

    // ── XML fixtures ───────────────────────────────────────────────────────────

    private function rss2Feed(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <title>Tech Daily</title>
            <link>https://example.com</link>
            <description>Latest tech news.</description>
            <item><title>Article 1</title></item>
          </channel>
        </rss>
        XML;
    }

    private function rss2FeedWithImage(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <title>Tech Daily</title>
            <link>https://example.com</link>
            <description>Latest tech news.</description>
            <image>
              <url>https://example.com/logo.png</url>
              <title>Tech Daily</title>
              <link>https://example.com</link>
            </image>
          </channel>
        </rss>
        XML;
    }

    private function rss2FeedMinimal(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <title>Minimal Feed</title>
          </channel>
        </rss>
        XML;
    }

    private function rss2FeedNoTitle(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <title></title>
            <link>https://example.com</link>
          </channel>
        </rss>
        XML;
    }

    private function podcastFeed(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
          <channel>
            <title>My Podcast</title>
            <link>https://example.com</link>
            <description>A great podcast.</description>
            <itunes:image href="https://example.com/cover.jpg"/>
          </channel>
        </rss>
        XML;
    }

    private function podcastFeedWithBothImages(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
          <channel>
            <title>Dual Image Podcast</title>
            <link>https://example.com</link>
            <description>Has both image types.</description>
            <itunes:image href="https://example.com/itunes-cover.jpg"/>
            <image>
              <url>https://example.com/channel-logo.png</url>
              <title>Dual Image Podcast</title>
              <link>https://example.com</link>
            </image>
          </channel>
        </rss>
        XML;
    }

    private function podcastFeedSummaryOnly(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
          <channel>
            <title>Summary Podcast</title>
            <link>https://example.com</link>
            <itunes:summary>Summary as description.</itunes:summary>
          </channel>
        </rss>
        XML;
    }

    private function atomFeed(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns="http://www.w3.org/2005/Atom">
          <title>Atom Blog</title>
          <subtitle>An atom feed.</subtitle>
          <link href="https://example.com" rel="alternate"/>
          <link href="https://example.com/atom.xml" rel="self"/>
          <logo>https://example.com/logo.png</logo>
          <entry>
            <title>Post 1</title>
          </entry>
        </feed>
        XML;
    }
}
