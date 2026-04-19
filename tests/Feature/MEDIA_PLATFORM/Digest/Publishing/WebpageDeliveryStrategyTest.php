<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Publishing/WebpageDeliveryStrategyTest.php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\SftpService;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use MediaPlatform\Digest\Publishing\Notifications\DigestReadyNotification;
use MediaPlatform\Digest\Publishing\Strategies\WebpageDeliveryStrategy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

/**
 * WebpageDeliveryStrategyTest
 *
 * Tests WebpageDeliveryStrategy::deliver() in isolation.
 */

uses(RefreshDatabase::class);

// =============================================================================
// Helpers
// =============================================================================

function webpageDigestData(ListModel $list): array
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
                        'source_url'          => 'https://example.com/article',
                        'source_title'        => 'Test',
                        'source_description'  => 'Desc.',
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

function webpageBuilder(): DigestBuilderService
{
    $b = mock(DigestBuilderService::class);
    $b->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
    $b->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    return $b;
}

function webpageDest(User $user): OutputDestination
{
    return OutputDestination::factory()->forUser($user)->create([
        'type'      => 'sftp',
        'host'      => 'sftp.example.com',
        'port'      => 22,
        'username'  => 'deploy',
        'auth_type' => 'password',
        'password'  => 'secret',
        'path'      => '/digests',
        'base_url'  => 'https://example.com/digests',
    ]);
}

// =============================================================================
// Tests
// =============================================================================

it('uploads HTML via SFTP and returns true', function () {
    $user = User::factory()->create();
    $dest = webpageDest($user);
    $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create(['notify_by_email' => false]);

    $sftp = mock(SftpService::class);
    $sftp->shouldReceive('upload')->once()->andReturn(['success' => true, 'path' => '/digests/test']);

    $strategy = new WebpageDeliveryStrategy($sftp);
    $result   = $strategy->deliver($list, webpageDigestData($list), webpageBuilder());

    expect($result)->toBeTrue();
});

it('returns false when output destination is missing', function () {
    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create([
        'output_type'           => 'webpage',
        'output_destination_id' => null,
    ]);

    $sftp = mock(SftpService::class);
    $sftp->shouldNotReceive('upload');

    $strategy = new WebpageDeliveryStrategy($sftp);
    $result   = $strategy->deliver($list, webpageDigestData($list), webpageBuilder());

    expect($result)->toBeFalse();
});

it('returns false when SFTP upload fails', function () {
    $user = User::factory()->create();
    $dest = webpageDest($user);
    $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create();

    $sftp = mock(SftpService::class);
    $sftp->shouldReceive('upload')->andReturn(['success' => false, 'message' => 'Connection refused']);

    $strategy = new WebpageDeliveryStrategy($sftp);
    $result   = $strategy->deliver($list, webpageDigestData($list), webpageBuilder());

    expect($result)->toBeFalse();
});

it('raises AdminAlert on SFTP failure', function () {
    $user = User::factory()->create();
    $dest = webpageDest($user);
    $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create();

    $sftp = mock(SftpService::class);
    $sftp->shouldReceive('upload')->andReturn(['success' => false, 'message' => 'Timeout']);

    $strategy = new WebpageDeliveryStrategy($sftp);
    $strategy->deliver($list, webpageDigestData($list), webpageBuilder());

    $this->assertDatabaseHas('admin_alerts', [
        'category' => 'sftp',
        'tier'     => 2,
    ]);
});

it('sends DigestReadyNotification when notify_by_email is true', function () {
    Notification::fake();

    $user = User::factory()->create();
    $dest = webpageDest($user);
    $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create(['notify_by_email' => true]);

    $sftp = mock(SftpService::class);
    $sftp->shouldReceive('upload')->andReturn(['success' => true, 'path' => '/digests/test']);

    $strategy = new WebpageDeliveryStrategy($sftp);
    $strategy->deliver($list, webpageDigestData($list), webpageBuilder());

    Notification::assertSentTo($user, DigestReadyNotification::class);
});

it('does not send notification when notify_by_email is false', function () {
    Notification::fake();

    $user = User::factory()->create();
    $dest = webpageDest($user);
    $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create(['notify_by_email' => false]);

    $sftp = mock(SftpService::class);
    $sftp->shouldReceive('upload')->andReturn(['success' => true, 'path' => '/digests/test']);

    $strategy = new WebpageDeliveryStrategy($sftp);
    $strategy->deliver($list, webpageDigestData($list), webpageBuilder());

    Notification::assertNotSentTo($user, DigestReadyNotification::class);
});