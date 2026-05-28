<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Publishing/StaticSiteDeliveryStrategyTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Publishing;

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
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaticSiteDeliveryStrategyTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeStrategy(?DeployHookTriggerService $service = null): StaticSiteDeliveryStrategy
    {
        return new StaticSiteDeliveryStrategy(
            $service ?? mock(DeployHookTriggerService::class)->shouldReceive('trigger')->never()->getMock()
        );
    }

    private function fakeBuilder(
        string $slug    = 'test-digest-2026-04-18',
        string $excerpt = '1 item from 1 source',
    ): DigestBuilderService {
        $b = mock(DigestBuilderService::class);
        $b->shouldReceive('buildSlug')->andReturn($slug);
        $b->shouldReceive('buildExcerpt')->andReturn($excerpt);
        return $b;
    }

    private function fakeData(ListModel $list): array
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

    // =========================================================================
    // Payload persistence
    // =========================================================================

    #[Test]
    public function creates_a_PublishedDigest_record_with_correct_payload_structure(): void
    {
        Notification::fake();

        $user     = User::factory()->create();
        $list     = ListModel::factory()->forUser($user)->staticSite()->create();
        $result   = $this->makeStrategy()->deliver($list, $this->fakeData($list), $this->fakeBuilder());
        $digest   = PublishedDigest::where('list_id', $list->id)->first();

        $this->assertTrue($result);
        $this->assertNotNull($digest);
        $this->assertSame('test-digest-2026-04-18', $digest->slug);
        $this->assertSame(1, $digest->total_items);
        $this->assertSame(1, $digest->source_count);
        $this->assertIsArray($digest->payload);
        $this->assertSame('Test Feed', $digest->payload[0]['source_name']);
        $this->assertSame('https://example.com/article-1', $digest->payload[0]['items'][0]['source_url']);
        $this->assertSame('Description here.', $digest->payload[0]['items'][0]['source_description']);
    }

    #[Test]
    public function includes_source_description_in_payload_items(): void
    {
        Notification::fake();

        $user   = User::factory()->create();
        $list   = ListModel::factory()->forUser($user)->staticSite()->create();
        $this->makeStrategy()->deliver($list, $this->fakeData($list), $this->fakeBuilder());
        $digest = PublishedDigest::where('list_id', $list->id)->first();

        $this->assertArrayHasKey('source_description', $digest->payload[0]['items'][0]);
        $this->assertSame('Description here.', $digest->payload[0]['items'][0]['source_description']);
    }

    // =========================================================================
    // Deploy hooks
    // =========================================================================

    #[Test]
    public function fires_all_enabled_deploy_hooks_for_the_list(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create();

        DeployHook::factory()->create(['triggerable_type' => 'digest_list', 'triggerable_id' => $list->id, 'enabled' => true, 'label' => 'Hook 1']);
        DeployHook::factory()->create(['triggerable_type' => 'digest_list', 'triggerable_id' => $list->id, 'enabled' => true, 'label' => 'Hook 2']);

        $service = mock(DeployHookTriggerService::class);
        $service->shouldReceive('trigger')->twice()->andReturnUsing(
            fn (DeployHook $h) => DeployHookTriggerResult::success($h, 200, 'build-id')
        );

        (new StaticSiteDeliveryStrategy($service))->deliver($list, $this->fakeData($list), $this->fakeBuilder());
    }

    #[Test]
    public function skips_disabled_deploy_hooks(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create();

        DeployHook::factory()->create(['triggerable_type' => 'digest_list', 'triggerable_id' => $list->id, 'enabled' => true]);
        DeployHook::factory()->create(['triggerable_type' => 'digest_list', 'triggerable_id' => $list->id, 'enabled' => false]);

        $service = mock(DeployHookTriggerService::class);
        $service->shouldReceive('trigger')->once()->andReturnUsing(
            fn (DeployHook $h) => DeployHookTriggerResult::success($h, 200)
        );

        (new StaticSiteDeliveryStrategy($service))->deliver($list, $this->fakeData($list), $this->fakeBuilder());
    }

    #[Test]
    public function records_deploy_hook_fired_at_on_the_PublishedDigest(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create();

        DeployHook::factory()->create(['triggerable_type' => 'digest_list', 'triggerable_id' => $list->id, 'enabled' => true]);

        $service = mock(DeployHookTriggerService::class);
        $service->shouldReceive('trigger')->andReturnUsing(
            fn (DeployHook $h) => DeployHookTriggerResult::success($h, 200)
        );

        (new StaticSiteDeliveryStrategy($service))->deliver($list, $this->fakeData($list), $this->fakeBuilder());

        $this->assertNotNull(PublishedDigest::where('list_id', $list->id)->first()->deploy_hook_fired_at);
    }

    #[Test]
    public function handles_deploy_hook_failure_gracefully_and_still_returns_true(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create();

        DeployHook::factory()->create(['triggerable_type' => 'digest_list', 'triggerable_id' => $list->id, 'enabled' => true]);

        $service = mock(DeployHookTriggerService::class);
        $service->shouldReceive('trigger')->andReturnUsing(
            fn (DeployHook $h) => DeployHookTriggerResult::failure($h, 500, 'Provider error')
        );

        $result = (new StaticSiteDeliveryStrategy($service))->deliver($list, $this->fakeData($list), $this->fakeBuilder());

        $this->assertTrue($result);
        $this->assertDatabaseHas('published_digests', ['list_id' => $list->id]);
    }

    // =========================================================================
    // Retention pruning
    // =========================================================================

    #[Test]
    public function does_not_prune_records_pruning_is_handled_by_DigestRetentionService(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create(['retention_count' => 3]);

        PublishedDigest::factory()->forList($list)->create(['slug' => 'day-1', 'digest_date' => '2026-04-10']);
        PublishedDigest::factory()->forList($list)->create(['slug' => 'day-2', 'digest_date' => '2026-04-11']);
        PublishedDigest::factory()->forList($list)->create(['slug' => 'day-3', 'digest_date' => '2026-04-12']);

        $this->makeStrategy()->deliver($list, $this->fakeData($list), $this->fakeBuilder('day-4'));

        $this->assertSame(4, PublishedDigest::where('list_id', $list->id)->count());
    }

    #[Test]
    public function does_not_prune_when_under_retention_count(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create(['retention_count' => 10]);

        PublishedDigest::factory()->forList($list)->create(['slug' => 'existing']);

        $this->makeStrategy()->deliver($list, $this->fakeData($list), $this->fakeBuilder('new-one'));

        $this->assertSame(2, PublishedDigest::where('list_id', $list->id)->count());
    }

    // =========================================================================
    // Notifications
    // =========================================================================

    #[Test]
    public function sends_StaticSiteDigestReadyNotification_when_notify_by_email_is_true(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create(['notify_by_email' => true]);

        $this->makeStrategy()->deliver($list, $this->fakeData($list), $this->fakeBuilder());

        Notification::assertSentTo($user, StaticSiteDigestReadyNotification::class);
    }

    #[Test]
    public function does_not_send_notification_when_notify_by_email_is_false(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create(['notify_by_email' => false]);

        $this->makeStrategy()->deliver($list, $this->fakeData($list), $this->fakeBuilder());

        Notification::assertNotSentTo($user, StaticSiteDigestReadyNotification::class);
    }
}