<?php

namespace Tests\Feature\MEDIA_PLATFORM\API\v1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use MediaPlatform\API\v1\Models\ApiClient;
use MediaPlatform\API\v1\Models\ApiControl;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastGuest;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
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

    public function test_response_contains_episodes_guests_and_sponsors_keys(): void
    {
        $this->enableApi();

        $this->authenticatedGet()
            ->assertOk()
            ->assertJsonStructure(['episodes', 'guests', 'sponsors']);
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
}