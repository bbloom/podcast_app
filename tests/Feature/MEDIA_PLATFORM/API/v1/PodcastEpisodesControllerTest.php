<?php

namespace Tests\Feature\MEDIA_PLATFORM\API\v1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use MediaPlatform\API\v1\Models\ApiClient;
use MediaPlatform\API\v1\Models\ApiControl;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\Tools\FooterLinks\Models\FooterLink;
use MediaPlatform\Tools\PhpServerlessProjectSponsors\Models\PhpserverlessprojectSponsor;
use App\Models\User;
use Tests\TestCase;

class PodcastEpisodesControllerTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT  = '/api/v1/podcastepisodes';
    private const DOMAIN    = 'mypodcast.com';
    private const TOKEN     = 'supersecrettoken1234567890abcdefghijklmnopqrstuvwxyz123456789012';
    private const SHOW_SLUG = 'bob-bloom-show';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Enable the API and create an active client with a known token.
     */
    private function enableApi(): void
    {
        ApiControl::instance()->enable();

        ApiClient::create([
            'label'      => 'Test Client',
            'domain'     => self::DOMAIN,
            'token_hash' => Hash::make(self::TOKEN),
            'is_active'  => true,
        ]);
    }

    /**
     * Make an authenticated request to the endpoint, optionally
     * overriding the PodcastShowSlug header.
     */
    private function authenticatedGet(?string $showSlug = self::SHOW_SLUG): \Illuminate\Testing\TestResponse
    {
        $headers = [
            'RequestingDomain' => self::DOMAIN,
            'Authorization'    => 'Bearer ' . self::TOKEN,
        ];

        if ($showSlug !== null) {
            $headers['PodcastShowSlug'] = $showSlug;
        }

        return $this->withHeaders($headers)->getJson(self::ENDPOINT);
    }

    /**
     * Create a published episode for the given show slug.
     */
    private function makePublishedEpisode(?string $showSlug = self::SHOW_SLUG): PodcastEpisode
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create([
            'user_id' => $user->id,
            'slug'    => $showSlug,
        ]);

        return PodcastEpisode::factory()->create([
            'user_id'            => $user->id,
            'podcast_show_id'    => $show->id,
            'website_enabled'    => true,
            'website_publish_on' => now()->subDay(),
        ]);
    }

    /**
     * Create a podcast show with known values for show-level API tests.
     * Returns the show.
     */
    private function makeShowWithKnownValues(): PodcastShow
    {
        $user = User::factory()->create();

        return PodcastShow::factory()->create([
            'user_id'          => $user->id,
            'slug'             => self::SHOW_SLUG,
            'title'            => 'The Bob Bloom Show',
            'description'      => 'A show about software.',
            'itunes_image'     => 'https://example.com/artwork.jpg',
            'itunes_copyright' => '© 2026 Bob Bloom',
        ]);
    }

    // -------------------------------------------------------------------------
    // 503 — API disabled
    // -------------------------------------------------------------------------

    public function test_returns_503_when_api_is_disabled(): void
    {
        $this->authenticatedGet()->assertStatus(503);
    }

    public function test_returns_503_even_with_valid_credentials_when_api_is_disabled(): void
    {
        ApiClient::create([
            'label'      => 'Test Client',
            'domain'     => self::DOMAIN,
            'token_hash' => Hash::make(self::TOKEN),
            'is_active'  => true,
        ]);

        $this->authenticatedGet()->assertStatus(503);
    }

    // -------------------------------------------------------------------------
    // 403 — Authentication failures
    // -------------------------------------------------------------------------

    public function test_returns_403_when_no_headers_sent(): void
    {
        ApiControl::instance()->enable();

        $this->getJson(self::ENDPOINT)->assertStatus(403);
    }

    public function test_returns_403_when_requesting_domain_header_is_missing(): void
    {
        $this->enableApi();

        $this->withHeaders([
            'Authorization'    => 'Bearer ' . self::TOKEN,
            'PodcastShowSlug'  => self::SHOW_SLUG,
        ])->getJson(self::ENDPOINT)->assertStatus(403);
    }

    public function test_returns_403_when_bearer_token_is_missing(): void
    {
        $this->enableApi();

        $this->withHeaders([
            'RequestingDomain' => self::DOMAIN,
            'PodcastShowSlug'  => self::SHOW_SLUG,
        ])->getJson(self::ENDPOINT)->assertStatus(403);
    }

    public function test_returns_403_when_domain_does_not_match_any_client(): void
    {
        $this->enableApi();

        $this->withHeaders([
            'RequestingDomain' => 'unknown-domain.com',
            'Authorization'    => 'Bearer ' . self::TOKEN,
            'PodcastShowSlug'  => self::SHOW_SLUG,
        ])->getJson(self::ENDPOINT)->assertStatus(403);
    }

    public function test_returns_403_when_bearer_token_is_wrong(): void
    {
        $this->enableApi();

        $this->withHeaders([
            'RequestingDomain' => self::DOMAIN,
            'Authorization'    => 'Bearer wrongtoken',
            'PodcastShowSlug'  => self::SHOW_SLUG,
        ])->getJson(self::ENDPOINT)->assertStatus(403);
    }

    public function test_returns_403_when_client_is_inactive(): void
    {
        ApiControl::instance()->enable();

        ApiClient::create([
            'label'      => 'Inactive Client',
            'domain'     => self::DOMAIN,
            'token_hash' => Hash::make(self::TOKEN),
            'is_active'  => false,
        ]);

        $this->authenticatedGet()->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // 422 — Missing PodcastShowSlug header
    // -------------------------------------------------------------------------

    public function test_returns_422_when_podcast_show_slug_header_is_missing(): void
    {
        $this->enableApi();

        $this->authenticatedGet(showSlug: null)->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // 200 — Happy path
    // -------------------------------------------------------------------------

    public function test_returns_200_with_valid_credentials_and_api_enabled(): void
    {
        $this->enableApi();

        $this->authenticatedGet()->assertOk();
    }

    public function test_response_contains_show_episodes_guests_and_sponsors_keys(): void
    {
        $this->enableApi();

        $this->authenticatedGet()
            ->assertOk()
            ->assertJsonStructure(['show', 'episodes', 'guests', 'sponsors']);
    }

    public function test_returns_only_episodes_for_requested_show(): void
    {
        $this->enableApi();

        $targetEpisode = $this->makePublishedEpisode(self::SHOW_SLUG);
        $otherEpisode  = $this->makePublishedEpisode('another-show');

        $response = $this->authenticatedGet()->assertOk();
        $episodes = $response->json('episodes');

        $slugs = array_column($episodes, 'slug');

        $this->assertContains($targetEpisode->slug, $slugs);
        $this->assertNotContains($otherEpisode->slug, $slugs);
    }

    public function test_returns_published_episodes_only(): void
    {
        $this->enableApi();

        $published = $this->makePublishedEpisode();

        // Reuse the show created by makePublishedEpisode() to avoid slug collision.
        $show = PodcastShow::where('slug', self::SHOW_SLUG)->firstOrFail();

        // Unpublished — website_enabled = false
        PodcastEpisode::factory()->create([
            'user_id'            => $published->user_id,
            'podcast_show_id'    => $show->id,
            'website_enabled'    => false,
            'website_publish_on' => now()->subDay(),
        ]);

        // Future — publish date in the future
        PodcastEpisode::factory()->create([
            'user_id'            => $published->user_id,
            'podcast_show_id'    => $show->id,
            'website_enabled'    => true,
            'website_publish_on' => now()->addDay(),
        ]);

        $episodes = $this->authenticatedGet()->assertOk()->json('episodes');

        $this->assertCount(1, $episodes);
        $this->assertEquals($published->slug, $episodes[0]['slug']);
    }

    public function test_episodes_are_ordered_newest_first(): void
    {
        $this->enableApi();

        $user = User::factory()->create();
        $show = PodcastShow::factory()->create([
            'user_id' => $user->id,
            'slug'    => self::SHOW_SLUG,
        ]);

        $older = PodcastEpisode::factory()->create([
            'user_id'            => $user->id,
            'podcast_show_id'    => $show->id,
            'website_enabled'    => true,
            'website_publish_on' => now()->subDays(10),
        ]);

        $newer = PodcastEpisode::factory()->create([
            'user_id'            => $user->id,
            'podcast_show_id'    => $show->id,
            'website_enabled'    => true,
            'website_publish_on' => now()->subDay(),
        ]);

        $episodes = $this->authenticatedGet()->assertOk()->json('episodes');

        $this->assertEquals($newer->slug, $episodes[0]['slug']);
        $this->assertEquals($older->slug, $episodes[1]['slug']);
    }

    public function test_episode_contains_expected_fields(): void
    {
        $this->enableApi();
        $this->makePublishedEpisode();

        $episode = $this->authenticatedGet()
            ->assertOk()
            ->json('episodes.0');

        foreach ([
            'title', 'slug', 'website_publish_on', 'website_content',
            'website_excerpt', 'website_meta_description', 'website_episode_notes',
            'website_attribution', 'website_featured_image', 'itunes_enclosure_url',
            'itunes_image', 'itunes_pubdate', 'itunes_duration', 'itunes_episode',
            'itunes_season', 'itunes_episode_type', 'itunes_summary',
            'guests', 'links',
        ] as $field) {
            $this->assertArrayHasKey($field, $episode, "Missing field: {$field}");
        }
    }

    public function test_episode_does_not_expose_sensitive_fields(): void
    {
        $this->enableApi();
        $this->makePublishedEpisode();

        $episode = $this->authenticatedGet()
            ->assertOk()
            ->json('episodes.0');

        foreach (['status', 'draft', 'auphonic_production_uuid', 'user_id'] as $field) {
            $this->assertArrayNotHasKey($field, $episode, "Sensitive field exposed: {$field}");
        }
    }

    public function test_episode_guests_are_slugs_only(): void
    {
        $this->enableApi();

        $episode = $this->makePublishedEpisode();
        $guest   = PodcastGuest::factory()->create(['enabled' => true]);
        $episode->guests()->attach($guest->id);

        $guestSlugs = $this->authenticatedGet()
            ->assertOk()
            ->json('episodes.0.guests');

        $this->assertContains($guest->slug, $guestSlugs);
        $this->assertIsString($guestSlugs[0]);
    }

    public function test_top_level_guests_contain_full_profile(): void
    {
        $this->enableApi();

        $guest = PodcastGuest::factory()->create(['enabled' => true]);

        $topLevelGuest = $this->authenticatedGet()
            ->assertOk()
            ->json('guests.0');

        $this->assertEquals($guest->slug, $topLevelGuest['slug']);
        $this->assertArrayHasKey('profile_full', $topLevelGuest);
        $this->assertArrayNotHasKey('email_address', $topLevelGuest);
        $this->assertArrayNotHasKey('internal_comment', $topLevelGuest);
    }

    public function test_disabled_guests_are_excluded_from_top_level(): void
    {
        $this->enableApi();

        PodcastGuest::factory()->create(['enabled' => true,  'full_name' => 'Active Guest']);
        PodcastGuest::factory()->create(['enabled' => false, 'full_name' => 'Disabled Guest']);

        $guests = $this->authenticatedGet()
            ->assertOk()
            ->json('guests');

        $names = array_column($guests, 'full_name');

        $this->assertContains('Active Guest', $names);
        $this->assertNotContains('Disabled Guest', $names);
    }

    public function test_last_used_at_is_updated_on_successful_request(): void
    {
        $this->enableApi();

        $client = ApiClient::where('domain', self::DOMAIN)->first();
        $this->assertNull($client->last_used_at);

        $this->authenticatedGet()->assertOk();

        $this->assertNotNull($client->fresh()->last_used_at);
    }

    // -------------------------------------------------------------------------
    // Show data in response
    // -------------------------------------------------------------------------

    public function test_show_contains_expected_fields(): void
    {
        $this->enableApi();
        $this->makeShowWithKnownValues();

        $show = $this->authenticatedGet()
            ->assertOk()
            ->json('show');

        $this->assertEquals('The Bob Bloom Show', $show['title']);
        $this->assertEquals('A show about software.', $show['description']);
        $this->assertEquals('https://example.com/artwork.jpg', $show['itunes_image']);
        $this->assertEquals('© 2026 Bob Bloom', $show['itunes_copyright']);
        $this->assertArrayHasKey('footer_links', $show);
    }

    public function test_show_does_not_expose_sensitive_fields(): void
    {
        $this->enableApi();
        $this->makeShowWithKnownValues();

        $show = $this->authenticatedGet()
            ->assertOk()
            ->json('show');

        foreach ([
            'id', 'user_id', 'slug', 'rss_link',
            'storage_artwork_url', 'storage_audio_files_url', 'storage_video_files_url',
            'website_enabled', 'created_at', 'updated_at',
        ] as $field) {
            $this->assertArrayNotHasKey($field, $show, "Sensitive field exposed: {$field}");
        }
    }

    public function test_show_is_null_when_slug_does_not_match(): void
    {
        $this->enableApi();

        $response = $this->authenticatedGet('nonexistent-show');

        $response->assertOk();
        $this->assertNull($response->json('show'));
    }

    // -------------------------------------------------------------------------
    // Footer links in show response
    // -------------------------------------------------------------------------

    public function test_footer_links_are_included_and_ordered_by_link_order(): void
    {
        $this->enableApi();
        $show = $this->makeShowWithKnownValues();

        FooterLink::factory()->forShow($show)->create([
            'link_name'  => 'Third',
            'link_url'   => 'https://example.com/third',
            'link_order' => 3,
        ]);
        FooterLink::factory()->forShow($show)->create([
            'link_name'  => 'First',
            'link_url'   => 'https://example.com/first',
            'link_order' => 1,
        ]);
        FooterLink::factory()->forShow($show)->create([
            'link_name'  => 'Second',
            'link_url'   => 'https://example.com/second',
            'link_order' => 2,
        ]);

        $footerLinks = $this->authenticatedGet()
            ->assertOk()
            ->json('show.footer_links');

        $this->assertCount(3, $footerLinks);
        $this->assertEquals('First', $footerLinks[0]['link_name']);
        $this->assertEquals('Second', $footerLinks[1]['link_name']);
        $this->assertEquals('Third', $footerLinks[2]['link_name']);
    }

    public function test_footer_link_contains_expected_fields_only(): void
    {
        $this->enableApi();
        $show = $this->makeShowWithKnownValues();

        FooterLink::factory()->forShow($show)->create([
            'link_name'  => 'Privacy Policy',
            'link_url'   => 'https://example.com/privacy',
            'link_order' => 1,
        ]);

        $link = $this->authenticatedGet()
            ->assertOk()
            ->json('show.footer_links.0');

        $this->assertEquals('Privacy Policy', $link['link_name']);
        $this->assertEquals('https://example.com/privacy', $link['link_url']);
        $this->assertEquals(1, $link['link_order']);

        foreach (['id', 'user_id', 'podcast_show_id', 'created_at', 'updated_at'] as $field) {
            $this->assertArrayNotHasKey($field, $link, "Internal field exposed: {$field}");
        }
    }

    public function test_show_with_no_footer_links_returns_empty_array(): void
    {
        $this->enableApi();
        $this->makeShowWithKnownValues();

        $footerLinks = $this->authenticatedGet()
            ->assertOk()
            ->json('show.footer_links');

        $this->assertIsArray($footerLinks);
        $this->assertEmpty($footerLinks);
    }

    // -------------------------------------------------------------------------
    // Bob Bloom Show archive
    // -------------------------------------------------------------------------

    public function test_bob_bloom_show_response_contains_bob_bloom_archive_key(): void
    {
        $this->enableApi();
        $this->makePublishedEpisode(self::SHOW_SLUG);

        $this->authenticatedGet(self::SHOW_SLUG)
            ->assertOk()
            ->assertJsonStructure(['show', 'episodes', 'guests', 'sponsors', 'bob_bloom_archive']);
    }

    public function test_bob_bloom_archive_contains_57_episodes(): void
    {
        $this->enableApi();
        $this->makePublishedEpisode(self::SHOW_SLUG);

        $archive = $this->authenticatedGet(self::SHOW_SLUG)
            ->assertOk()
            ->json('bob_bloom_archive');

        $this->assertCount(57, $archive);
    }

    public function test_bob_bloom_archive_episode_contains_expected_fields(): void
    {
        $this->enableApi();
        $this->makePublishedEpisode(self::SHOW_SLUG);

        $episode = $this->authenticatedGet(self::SHOW_SLUG)
            ->assertOk()
            ->json('bob_bloom_archive.0');

        foreach (['episode_number', 'title', 'date', 'duration', 'audio_url'] as $field) {
            $this->assertArrayHasKey($field, $episode, "Missing archive field: {$field}");
        }
    }

    public function test_bob_bloom_archive_episode_does_not_expose_filename(): void
    {
        $this->enableApi();
        $this->makePublishedEpisode(self::SHOW_SLUG);

        $episode = $this->authenticatedGet(self::SHOW_SLUG)
            ->assertOk()
            ->json('bob_bloom_archive.0');

        $this->assertArrayNotHasKey('filename', $episode, 'Raw filename should not be exposed');
    }

    public function test_bob_bloom_archive_audio_url_is_fully_qualified(): void
    {
        $this->enableApi();
        $this->makePublishedEpisode(self::SHOW_SLUG);

        $episode = $this->authenticatedGet(self::SHOW_SLUG)
            ->assertOk()
            ->json('bob_bloom_archive.0');

        $this->assertStringStartsWith('https://', $episode['audio_url']);
        $this->assertStringEndsWith('.mp3', $episode['audio_url']);
    }

    public function test_bob_bloom_archive_first_episode_has_correct_data(): void
    {
        $this->enableApi();
        $this->makePublishedEpisode(self::SHOW_SLUG);

        $episode = $this->authenticatedGet(self::SHOW_SLUG)
            ->assertOk()
            ->json('bob_bloom_archive.0');

        $this->assertEquals(1, $episode['episode_number']);
        $this->assertEquals('Tienda Talk With Rafael Diaz-Tushman', $episode['title']);
        $this->assertEquals('February 18, 2010', $episode['date']);
        $this->assertEquals('29m 37s', $episode['duration']);
    }

    public function test_bob_bloom_archive_is_absent_for_other_shows(): void
    {
        $this->enableApi();
        $this->makePublishedEpisode('another-show');

        $response = $this->authenticatedGet('another-show')->assertOk();

        $this->assertArrayNotHasKey('bob_bloom_archive', $response->json());
    }

    public function test_bob_bloom_archive_is_separate_from_regular_episodes(): void
    {
        $this->enableApi();
        $regularEpisode = $this->makePublishedEpisode(self::SHOW_SLUG);

        $response = $this->authenticatedGet(self::SHOW_SLUG)->assertOk();

        $episodes = $response->json('episodes');
        $archive  = $response->json('bob_bloom_archive');

        // Regular episodes should contain the database episode
        $slugs = array_column($episodes, 'slug');
        $this->assertContains($regularEpisode->slug, $slugs);

        // Archive episodes should not have a 'slug' field
        $this->assertArrayNotHasKey('slug', $archive[0]);

        // Archive episodes should have 'episode_number' which regular episodes don't
        $this->assertArrayHasKey('episode_number', $archive[0]);
    }
}