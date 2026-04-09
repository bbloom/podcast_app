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

    private const ENDPOINT = '/api/v1/podcastepisodes';
    private const DOMAIN   = 'mypodcast.com';
    private const TOKEN    = 'supersecrettoken1234567890abcdefghijklmnopqrstuvwxyz123456789012';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Enable the API and create an active client with a known token.
     * Returns the plain-text token for use in request headers.
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
     * Make an authenticated request to the endpoint.
     */
    private function authenticatedGet(): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'RequestingDomain' => self::DOMAIN,
            'Authorization'    => 'Bearer ' . self::TOKEN,
        ])->getJson(self::ENDPOINT);
    }

    /**
     * Create a published episode attached to a show owned by a user.
     */
    private function makePublishedEpisode(): PodcastEpisode
    {
        $user  = User::factory()->create();
        $show  = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'           => $user->id,
            'podcast_show_id'   => $show->id,
            'website_enabled'   => true,
            'website_publish_on' => now()->subDay(),
        ]);
    }

    // -------------------------------------------------------------------------
    // 503 — API disabled
    // -------------------------------------------------------------------------

    public function test_returns_503_when_api_is_disabled(): void
    {
        // ApiControl row does not exist yet — instance() creates it disabled.
        $this->authenticatedGet()->assertStatus(503);
    }

    public function test_returns_503_even_with_valid_credentials_when_api_is_disabled(): void
    {
        // Create the client but do NOT enable the API.
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
            'Authorization' => 'Bearer ' . self::TOKEN,
        ])->getJson(self::ENDPOINT)->assertStatus(403);
    }

    public function test_returns_403_when_bearer_token_is_missing(): void
    {
        $this->enableApi();

        $this->withHeaders([
            'RequestingDomain' => self::DOMAIN,
        ])->getJson(self::ENDPOINT)->assertStatus(403);
    }

    public function test_returns_403_when_domain_does_not_match_any_client(): void
    {
        $this->enableApi();

        $this->withHeaders([
            'RequestingDomain' => 'unknown-domain.com',
            'Authorization'    => 'Bearer ' . self::TOKEN,
        ])->getJson(self::ENDPOINT)->assertStatus(403);
    }

    public function test_returns_403_when_bearer_token_is_wrong(): void
    {
        $this->enableApi();

        $this->withHeaders([
            'RequestingDomain' => self::DOMAIN,
            'Authorization'    => 'Bearer wrongtoken',
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

    public function test_returns_published_episodes_only(): void
    {
        $this->enableApi();

        $published   = $this->makePublishedEpisode();
        $user        = User::factory()->create();
        $show        = PodcastShow::factory()->create(['user_id' => $user->id]);

        // Unpublished — website_enabled = false
        PodcastEpisode::factory()->create([
            'user_id'           => $user->id,
            'podcast_show_id'   => $show->id,
            'website_enabled'   => false,
            'website_publish_on' => now()->subDay(),
        ]);

        // Future — publish date in the future
        PodcastEpisode::factory()->create([
            'user_id'           => $user->id,
            'podcast_show_id'   => $show->id,
            'website_enabled'   => true,
            'website_publish_on' => now()->addDay(),
        ]);

        $response = $this->authenticatedGet()->assertOk();

        $episodes = $response->json('episodes');

        $this->assertCount(1, $episodes);
        $this->assertEquals($published->slug, $episodes[0]['slug']);
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
        // Ensure it's a flat array of strings, not objects
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