<?php

// tests/Feature/MEDIA_PLATFORM/API/v1/DigestApiControllerTest.php

use MediaPlatform\API\v1\Models\ApiClient;
use MediaPlatform\API\v1\Models\ApiControl;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\Digest\Publishing\Models\PublishedDigest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * DigestApiControllerTest
 *
 * Tests the GET /api/v1/digests endpoint.
 *
 * TEST GROUPS
 * ───────────
 *   1. API disabled — 503
 *   2. Authentication — 403
 *   3. Missing header — 400
 *   4. List not found — 404
 *   5. Happy path — 200, response structure
 *   6. Ordering and limits
 *   7. api_fetched_at tracking
 */

uses(RefreshDatabase::class);

// =============================================================================
// Helpers
// =============================================================================

function enableApi(): void
{
    ApiControl::instance()->enable();
}

function createApiClient(string $domain = 'testdigest.com'): array
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

function apiHeaders(string $domain, string $token, string $listName): array
{
    return [
        'Authorization'    => 'Bearer ' . $token,
        'RequestingDomain' => $domain,
        'X-Digest-List'    => $listName,
    ];
}

// =============================================================================
// GROUP 1: API disabled
// =============================================================================

it('returns 503 when API is disabled', function () {
    ApiControl::instance()->disable();

    [$domain, $token] = createApiClient();

    $this->getJson('/api/v1/digests', apiHeaders($domain, $token, 'Test List'))
        ->assertStatus(503);
});

// =============================================================================
// GROUP 2: Authentication
// =============================================================================

it('returns 403 with invalid bearer token', function () {
    enableApi();
    [$domain] = createApiClient();

    $this->getJson('/api/v1/digests', [
        'Authorization'    => 'Bearer wrong-token',
        'RequestingDomain' => $domain,
        'X-Digest-List'    => 'Test List',
    ])->assertForbidden();
});

it('returns 403 with invalid domain header', function () {
    enableApi();
    [, $token] = createApiClient();

    $this->getJson('/api/v1/digests', [
        'Authorization'    => 'Bearer ' . $token,
        'RequestingDomain' => 'wrong-domain.com',
        'X-Digest-List'    => 'Test List',
    ])->assertForbidden();
});

it('returns 403 when both headers are missing', function () {
    enableApi();
    createApiClient();

    $this->getJson('/api/v1/digests')
        ->assertForbidden();
});

// =============================================================================
// GROUP 3: Missing X-Digest-List header
// =============================================================================

it('returns 400 when X-Digest-List header is missing', function () {
    enableApi();
    [$domain, $token] = createApiClient();

    $this->getJson('/api/v1/digests', [
        'Authorization'    => 'Bearer ' . $token,
        'RequestingDomain' => $domain,
    ])->assertStatus(400)
      ->assertJsonFragment(['error' => 'Missing X-Digest-List header.']);
});

// =============================================================================
// GROUP 4: List not found
// =============================================================================

it('returns 404 when list name does not exist', function () {
    enableApi();
    [$domain, $token] = createApiClient();

    $this->getJson('/api/v1/digests', apiHeaders($domain, $token, 'Nonexistent List'))
        ->assertNotFound();
});

it('returns 404 when list exists but is not static_site type', function () {
    enableApi();
    [$domain, $token] = createApiClient();

    $user = User::factory()->create();
    ListModel::factory()->forUser($user)->create([
        'name'        => 'Email List',
        'output_type' => OutputType::Email,
    ]);

    $this->getJson('/api/v1/digests', apiHeaders($domain, $token, 'Email List'))
        ->assertNotFound();
});

// =============================================================================
// GROUP 5: Happy path
// =============================================================================

it('returns 200 with correct JSON structure', function () {
    enableApi();
    [$domain, $token] = createApiClient();

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

    $response = $this->getJson('/api/v1/digests', apiHeaders($domain, $token, 'Tech Digest'));

    $response->assertOk()
        ->assertJsonStructure([
            'list' => ['name', 'description'],
            'digests' => [
                '*' => ['slug', 'date', 'total_items', 'source_count', 'groups'],
            ],
        ])
        ->assertJsonFragment(['name' => 'Tech Digest'])
        ->assertJsonFragment(['slug' => 'tech-digest-2026-04-18']);
});

it('returns empty digests array when no published digests exist', function () {
    enableApi();
    [$domain, $token] = createApiClient();

    $user = User::factory()->create();
    ListModel::factory()->forUser($user)->staticSite()->create(['name' => 'Empty List']);

    $response = $this->getJson('/api/v1/digests', apiHeaders($domain, $token, 'Empty List'));

    $response->assertOk()
        ->assertJsonFragment(['digests' => []]);
});

// =============================================================================
// GROUP 6: Ordering and limits
// =============================================================================

it('returns digests ordered by date descending', function () {
    enableApi();
    [$domain, $token] = createApiClient();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create(['name' => 'Ordered List']);

    PublishedDigest::factory()->forList($list)->create(['slug' => 'older', 'digest_date' => '2026-04-10']);
    PublishedDigest::factory()->forList($list)->create(['slug' => 'newer', 'digest_date' => '2026-04-18']);

    $response = $this->getJson('/api/v1/digests', apiHeaders($domain, $token, 'Ordered List'));

    $digests = $response->json('digests');
    expect($digests[0]['slug'])->toBe('newer');
    expect($digests[1]['slug'])->toBe('older');
});

it('respects retention_count limit', function () {
    enableApi();
    [$domain, $token] = createApiClient();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create([
        'name'            => 'Limited List',
        'retention_count' => 2,
    ]);

    PublishedDigest::factory()->forList($list)->create(['slug' => 'day-1', 'digest_date' => '2026-04-10']);
    PublishedDigest::factory()->forList($list)->create(['slug' => 'day-2', 'digest_date' => '2026-04-11']);
    PublishedDigest::factory()->forList($list)->create(['slug' => 'day-3', 'digest_date' => '2026-04-12']);

    $response = $this->getJson('/api/v1/digests', apiHeaders($domain, $token, 'Limited List'));

    $digests = $response->json('digests');
    expect($digests)->toHaveCount(2);
    expect($digests[0]['slug'])->toBe('day-3');
    expect($digests[1]['slug'])->toBe('day-2');
});

it('returns only digests for the requested list', function () {
    enableApi();
    [$domain, $token] = createApiClient();

    $user  = User::factory()->create();
    $list1 = ListModel::factory()->forUser($user)->staticSite()->create(['name' => 'List A']);
    $list2 = ListModel::factory()->forUser($user)->staticSite()->create(['name' => 'List B']);

    PublishedDigest::factory()->forList($list1)->create(['slug' => 'list-a-digest']);
    PublishedDigest::factory()->forList($list2)->create(['slug' => 'list-b-digest']);

    $response = $this->getJson('/api/v1/digests', apiHeaders($domain, $token, 'List A'));

    $digests = $response->json('digests');
    expect($digests)->toHaveCount(1);
    expect($digests[0]['slug'])->toBe('list-a-digest');
});

// =============================================================================
// GROUP 7: api_fetched_at tracking
// =============================================================================

it('updates api_fetched_at on returned records', function () {
    enableApi();
    [$domain, $token] = createApiClient();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create(['name' => 'Tracked List']);

    $digest = PublishedDigest::factory()->forList($list)->create(['slug' => 'tracked']);

    expect($digest->api_fetched_at)->toBeNull();

    $this->getJson('/api/v1/digests', apiHeaders($domain, $token, 'Tracked List'))
        ->assertOk();

    $digest->refresh();
    expect($digest->api_fetched_at)->not->toBeNull();
});