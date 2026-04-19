<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Publishing/StaticSiteDeliveryStrategyTest.php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use MediaPlatform\Digest\Publishing\Models\PublishedDigest;
use MediaPlatform\Digest\Publishing\Notifications\StaticSiteDigestReadyNotification;
use MediaPlatform\Digest\Publishing\Strategies\StaticSiteDeliveryStrategy;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use MediaPlatform\StaticSiteDeployHooks\Services\DeployHookTriggerResult;
use MediaPlatform\StaticSiteDeployHooks\Services\DeployHookTriggerService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

/**
 * StaticSiteDeliveryStrategyTest
 *
 * Unit-level feature tests for StaticSiteDeliveryStrategy::deliver().
 * Tests the strategy in isolation from PublishDigest.
 */

uses(RefreshDatabase::class);

// =============================================================================
// Helpers
// =============================================================================

function makeStrategy(?DeployHookTriggerService $service = null): StaticSiteDeliveryStrategy
{
    return new StaticSiteDeliveryStrategy(
        $service ?? mock(DeployHookTriggerService::class)->shouldReceive('trigger')->never()->getMock()
    );
}

function fakeBuilder(string $slug = 'test-digest-2026-04-18', string $excerpt = '1 item from 1 source'): DigestBuilderService
{
    $b = mock(DigestBuilderService::class);
    $b->shouldReceive('buildSlug')->andReturn($slug);
    $b->shouldReceive('buildExcerpt')->andReturn($excerpt);
    return $b;
}

function fakeData(ListModel $list): array
{
    return [
        'list'         => $list,
        'date'         => now(),
        'groups'       => collect([
            [
                'source_id'   => 1,
                'source_name' => 'Test Feed',
                'source_type' => 'text_based_rss_feed',
                'items'       => collect([
                    (object) [
                        'source_url'          => 'https://example.com/article-1',
                        'source_title'        => 'Test Article',
                        'source_description'  => 'Description here.',
                        'source_published_at' => now()->subHour(),
                        'summary_html'        => '<p>Summary.</p>',
                    ],
                ]),
            ],
        ]),
        'total_items'  => 1,
        'source_count' => 1,
    ];
}

// =============================================================================
// Payload persistence
// =============================================================================

it('creates a PublishedDigest record with correct payload structure', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create();

    $strategy = makeStrategy();
    $result   = $strategy->deliver($list, fakeData($list), fakeBuilder());

    expect($result)->toBeTrue();

    $digest = PublishedDigest::where('list_id', $list->id)->first();
    expect($digest)->not->toBeNull();
    expect($digest->slug)->toBe('test-digest-2026-04-18');
    expect($digest->total_items)->toBe(1);
    expect($digest->source_count)->toBe(1);
    expect($digest->payload)->toBeArray();
    expect($digest->payload[0]['source_name'])->toBe('Test Feed');
    expect($digest->payload[0]['items'][0]['source_url'])->toBe('https://example.com/article-1');
    expect($digest->payload[0]['items'][0]['source_description'])->toBe('Description here.');
});

it('includes source_description in payload items', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create();

    $strategy = makeStrategy();
    $strategy->deliver($list, fakeData($list), fakeBuilder());

    $digest = PublishedDigest::where('list_id', $list->id)->first();
    expect($digest->payload[0]['items'][0])->toHaveKey('source_description');
    expect($digest->payload[0]['items'][0]['source_description'])->toBe('Description here.');
});

// =============================================================================
// Deploy hooks
// =============================================================================

it('fires all enabled deploy hooks for the list', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create();

    DeployHook::factory()->create([
        'triggerable_type' => 'digest_list',
        'triggerable_id'   => $list->id,
        'enabled'          => true,
        'label'            => 'Hook 1',
    ]);

    DeployHook::factory()->create([
        'triggerable_type' => 'digest_list',
        'triggerable_id'   => $list->id,
        'enabled'          => true,
        'label'            => 'Hook 2',
    ]);

    $service = mock(DeployHookTriggerService::class);
    $service->shouldReceive('trigger')->twice()->andReturnUsing(function (DeployHook $h) {
        return DeployHookTriggerResult::success($h, 200, 'build-id');
    });

    $strategy = new StaticSiteDeliveryStrategy($service);
    $strategy->deliver($list, fakeData($list), fakeBuilder());
});

it('skips disabled deploy hooks', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create();

    DeployHook::factory()->create([
        'triggerable_type' => 'digest_list',
        'triggerable_id'   => $list->id,
        'enabled'          => true,
    ]);

    DeployHook::factory()->create([
        'triggerable_type' => 'digest_list',
        'triggerable_id'   => $list->id,
        'enabled'          => false,
    ]);

    $service = mock(DeployHookTriggerService::class);
    $service->shouldReceive('trigger')->once()->andReturnUsing(function (DeployHook $h) {
        return DeployHookTriggerResult::success($h, 200);
    });

    $strategy = new StaticSiteDeliveryStrategy($service);
    $strategy->deliver($list, fakeData($list), fakeBuilder());
});

it('records deploy_hook_fired_at on the PublishedDigest', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create();

    DeployHook::factory()->create([
        'triggerable_type' => 'digest_list',
        'triggerable_id'   => $list->id,
        'enabled'          => true,
    ]);

    $service = mock(DeployHookTriggerService::class);
    $service->shouldReceive('trigger')->andReturnUsing(function (DeployHook $h) {
        return DeployHookTriggerResult::success($h, 200);
    });

    $strategy = new StaticSiteDeliveryStrategy($service);
    $strategy->deliver($list, fakeData($list), fakeBuilder());

    $digest = PublishedDigest::where('list_id', $list->id)->first();
    expect($digest->deploy_hook_fired_at)->not->toBeNull();
});

it('handles deploy hook failure gracefully and still returns true', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create();

    DeployHook::factory()->create([
        'triggerable_type' => 'digest_list',
        'triggerable_id'   => $list->id,
        'enabled'          => true,
    ]);

    $service = mock(DeployHookTriggerService::class);
    $service->shouldReceive('trigger')->andReturnUsing(function (DeployHook $h) {
        return DeployHookTriggerResult::failure($h, 500, 'Provider error');
    });

    $strategy = new StaticSiteDeliveryStrategy($service);
    $result   = $strategy->deliver($list, fakeData($list), fakeBuilder());

    expect($result)->toBeTrue();
    $this->assertDatabaseHas('published_digests', ['list_id' => $list->id]);
});

// =============================================================================
// Retention pruning
// =============================================================================

it('does not prune records (pruning is handled by DigestRetentionService)', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create(['retention_count' => 3]);

    PublishedDigest::factory()->forList($list)->create(['slug' => 'day-1', 'digest_date' => '2026-04-10']);
    PublishedDigest::factory()->forList($list)->create(['slug' => 'day-2', 'digest_date' => '2026-04-11']);
    PublishedDigest::factory()->forList($list)->create(['slug' => 'day-3', 'digest_date' => '2026-04-12']);

    $strategy = makeStrategy();
    $strategy->deliver($list, fakeData($list), fakeBuilder('day-4'));

    // All 4 records should exist — this strategy doesn't prune
    expect(PublishedDigest::where('list_id', $list->id)->count())->toBe(4);
});

it('does not prune when under retention_count', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create(['retention_count' => 10]);

    PublishedDigest::factory()->forList($list)->create(['slug' => 'existing']);

    $strategy = makeStrategy();
    $strategy->deliver($list, fakeData($list), fakeBuilder('new-one'));

    expect(PublishedDigest::where('list_id', $list->id)->count())->toBe(2);
});

// =============================================================================
// Notifications
// =============================================================================

it('sends StaticSiteDigestReadyNotification when notify_by_email is true', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create(['notify_by_email' => true]);

    $strategy = makeStrategy();
    $strategy->deliver($list, fakeData($list), fakeBuilder());

    Notification::assertSentTo($user, StaticSiteDigestReadyNotification::class);
});

it('does not send notification when notify_by_email is false', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create(['notify_by_email' => false]);

    $strategy = makeStrategy();
    $strategy->deliver($list, fakeData($list), fakeBuilder());

    Notification::assertNotSentTo($user, StaticSiteDigestReadyNotification::class);
});