<?php

// tests/Feature/MEDIA_PLATFORM/API/v1/DigestApiControllerTest.php

namespace Tests\Feature\MEDIA_PLATFORM\API\v1;

use MediaPlatform\API\v1\Models\ApiClient;
use MediaPlatform\API\v1\Models\ApiControl;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\Digest\Publishing\Models\PublishedDigest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigestApiControllerTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function enableApi(): void
    {
        ApiControl::instance()->enable();
    }

    private function createApiClient(string $domain = 'testdigest.com'): array
    {
        $token = 'test-bearer-token-' . uniqid();

        ApiClient::create([
            'label'      => 'Test Client',
            'domain'     => $domain,
            'token_hash' => Hash::make($token),
            'is_active'  => true,
        ]);

        return [$domain, $token];
    }

    private function apiHeaders(string $domain, string $token, string $listName): array
    {
        return [
            'Authorization'    => 'Bearer ' . $token,
            'RequestingDomain' => $domain,
            'X-Digest-List'    => $listName,
        ];
    }

    // =========================================================================
    // GROUP 1: API disabled
    // =========================================================================

    #[Test]
    public function returns_503_when_api_is_disabled(): void
    {
        ApiControl::instance()->disable();

        [$domain, $token] = $this->createApiClient();

        $this->getJson('/api/v1/digests', $this->apiHeaders($domain, $token, 'Test List'))
             ->assertStatus(503);
    }

    // =========================================================================
    // GROUP 2: Authentication
    // =========================================================================

    #[Test]
    public function returns_403_with_invalid_bearer_token(): void
    {
        $this->enableApi();
        [$domain] = $this->createApiClient();

        $this->getJson('/api/v1/digests', [
            'Authorization'    => 'Bearer wrong-token',
            'RequestingDomain' => $domain,
            'X-Digest-List'    => 'Test List',
        ])->assertForbidden();
    }

    #[Test]
    public function returns_403_with_invalid_domain_header(): void
    {
        $this->enableApi();
        [, $token] = $this->createApiClient();

        $this->getJson('/api/v1/digests', [
            'Authorization'    => 'Bearer ' . $token,
            'RequestingDomain' => 'wrong-domain.com',
            'X-Digest-List'    => 'Test List',
        ])->assertForbidden();
    }

    #[Test]
    public function returns_403_when_both_headers_are_missing(): void
    {
        $this->enableApi();
        $this->createApiClient();

        $this->getJson('/api/v1/digests')->assertForbidden();
    }

    // =========================================================================
    // GROUP 3: Missing X-Digest-List header
    // =========================================================================

    #[Test]
    public function returns_400_when_X_Digest_List_header_is_missing(): void
    {
        $this->enableApi();
        [$domain, $token] = $this->createApiClient();

        $this->getJson('/api/v1/digests', [
            'Authorization'    => 'Bearer ' . $token,
            'RequestingDomain' => $domain,
        ])->assertStatus(400)
          ->assertJsonFragment(['error' => 'Missing X-Digest-List header.']);
    }

    // =========================================================================
    // GROUP 4: List not found
    // =========================================================================

    #[Test]
    public function returns_404_when_list_name_does_not_exist(): void
    {
        $this->enableApi();
        [$domain, $token] = $this->createApiClient();

        $this->getJson('/api/v1/digests', $this->apiHeaders($domain, $token, 'Nonexistent List'))
             ->assertNotFound();
    }

    #[Test]
    public function returns_404_when_list_exists_but_is_not_static_site_type(): void
    {
        $this->enableApi();
        [$domain, $token] = $this->createApiClient();

        $user = User::factory()->create();
        ListModel::factory()->forUser($user)->create([
            'name'        => 'Email List',
            'output_type' => OutputType::Email,
        ]);

        $this->getJson('/api/v1/digests', $this->apiHeaders($domain, $token, 'Email List'))
             ->assertNotFound();
    }

    // =========================================================================
    // GROUP 5: Happy path
    // =========================================================================

    #[Test]
    public function returns_200_with_correct_JSON_structure(): void
    {
        $this->enableApi();
        [$domain, $token] = $this->createApiClient();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create([
            'name'        => 'Tech Digest',
            'description' => 'Daily tech updates',
        ]);

        PublishedDigest::factory()->forList($list)->create([
            'slug'         => 'tech-digest-2026-04-18',
            'digest_date'  => '2026-04-18',
            'total_items'  => 5,
            'source_count' => 3,
        ]);

        $this->getJson('/api/v1/digests', $this->apiHeaders($domain, $token, 'Tech Digest'))
             ->assertOk()
             ->assertJsonStructure([
                 'list'    => ['name', 'description'],
                 'digests' => [
                     '*' => ['slug', 'date', 'total_items', 'source_count', 'groups'],
                 ],
             ])
             ->assertJsonFragment(['name' => 'Tech Digest'])
             ->assertJsonFragment(['slug' => 'tech-digest-2026-04-18']);
    }

    #[Test]
    public function returns_empty_digests_array_when_no_published_digests_exist(): void
    {
        $this->enableApi();
        [$domain, $token] = $this->createApiClient();

        $user = User::factory()->create();
        ListModel::factory()->forUser($user)->staticSite()->create(['name' => 'Empty List']);

        $this->getJson('/api/v1/digests', $this->apiHeaders($domain, $token, 'Empty List'))
             ->assertOk()
             ->assertJsonFragment(['digests' => []]);
    }

    // =========================================================================
    // GROUP 6: Ordering and limits
    // =========================================================================

    #[Test]
    public function returns_digests_ordered_by_date_descending(): void
    {
        $this->enableApi();
        [$domain, $token] = $this->createApiClient();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create(['name' => 'Ordered List']);

        PublishedDigest::factory()->forList($list)->create(['slug' => 'older', 'digest_date' => '2026-04-10']);
        PublishedDigest::factory()->forList($list)->create(['slug' => 'newer', 'digest_date' => '2026-04-18']);

        $digests = $this->getJson('/api/v1/digests', $this->apiHeaders($domain, $token, 'Ordered List'))
                        ->json('digests');

        $this->assertSame('newer', $digests[0]['slug']);
        $this->assertSame('older', $digests[1]['slug']);
    }

    #[Test]
    public function respects_retention_count_limit(): void
    {
        $this->enableApi();
        [$domain, $token] = $this->createApiClient();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create([
            'name'            => 'Limited List',
            'retention_count' => 2,
        ]);

        PublishedDigest::factory()->forList($list)->create(['slug' => 'day-1', 'digest_date' => '2026-04-10']);
        PublishedDigest::factory()->forList($list)->create(['slug' => 'day-2', 'digest_date' => '2026-04-11']);
        PublishedDigest::factory()->forList($list)->create(['slug' => 'day-3', 'digest_date' => '2026-04-12']);

        $digests = $this->getJson('/api/v1/digests', $this->apiHeaders($domain, $token, 'Limited List'))
                        ->json('digests');

        $this->assertCount(2, $digests);
        $this->assertSame('day-3', $digests[0]['slug']);
        $this->assertSame('day-2', $digests[1]['slug']);
    }

    #[Test]
    public function returns_only_digests_for_the_requested_list(): void
    {
        $this->enableApi();
        [$domain, $token] = $this->createApiClient();

        $user  = User::factory()->create();
        $list1 = ListModel::factory()->forUser($user)->staticSite()->create(['name' => 'List A']);
        $list2 = ListModel::factory()->forUser($user)->staticSite()->create(['name' => 'List B']);

        PublishedDigest::factory()->forList($list1)->create(['slug' => 'list-a-digest']);
        PublishedDigest::factory()->forList($list2)->create(['slug' => 'list-b-digest']);

        $digests = $this->getJson('/api/v1/digests', $this->apiHeaders($domain, $token, 'List A'))
                        ->json('digests');

        $this->assertCount(1, $digests);
        $this->assertSame('list-a-digest', $digests[0]['slug']);
    }

    // =========================================================================
    // GROUP 7: api_fetched_at tracking
    // =========================================================================

    #[Test]
    public function updates_api_fetched_at_on_returned_records(): void
    {
        $this->enableApi();
        [$domain, $token] = $this->createApiClient();

        $user   = User::factory()->create();
        $list   = ListModel::factory()->forUser($user)->staticSite()->create(['name' => 'Tracked List']);
        $digest = PublishedDigest::factory()->forList($list)->create(['slug' => 'tracked']);

        $this->assertNull($digest->api_fetched_at);

        $this->getJson('/api/v1/digests', $this->apiHeaders($domain, $token, 'Tracked List'))
             ->assertOk();

        $this->assertNotNull($digest->fresh()->api_fetched_at);
    }
}