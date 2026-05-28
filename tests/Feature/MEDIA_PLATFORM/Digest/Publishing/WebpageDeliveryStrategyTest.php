<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Publishing/WebpageDeliveryStrategyTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Publishing;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\SftpService;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use MediaPlatform\Digest\Publishing\Notifications\DigestReadyNotification;
use MediaPlatform\Digest\Publishing\Strategies\WebpageDeliveryStrategy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebpageDeliveryStrategyTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function webpageDigestData(ListModel $list): array
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

    private function fakeBuilder(): DigestBuilderService
    {
        $b = mock(DigestBuilderService::class);
        $b->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
        $b->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        return $b;
    }

    private function makeDest(User $user): OutputDestination
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

    // =========================================================================
    // Tests
    // =========================================================================

    #[Test]
    public function uploads_HTML_via_SFTP_and_returns_true(): void
    {
        $user = User::factory()->create();
        $dest = $this->makeDest($user);
        $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create(['notify_by_email' => false]);

        $sftp = mock(SftpService::class);
        $sftp->shouldReceive('upload')->once()->andReturn(['success' => true, 'path' => '/digests/test']);

        $result = (new WebpageDeliveryStrategy($sftp))->deliver($list, $this->webpageDigestData($list), $this->fakeBuilder());

        $this->assertTrue($result);
    }

    #[Test]
    public function returns_false_when_output_destination_is_missing(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->create([
            'output_type'           => 'webpage',
            'output_destination_id' => null,
        ]);

        $sftp = mock(SftpService::class);
        $sftp->shouldNotReceive('upload');

        $result = (new WebpageDeliveryStrategy($sftp))->deliver($list, $this->webpageDigestData($list), $this->fakeBuilder());

        $this->assertFalse($result);
    }

    #[Test]
    public function returns_false_when_SFTP_upload_fails(): void
    {
        $user = User::factory()->create();
        $dest = $this->makeDest($user);
        $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create();

        $sftp = mock(SftpService::class);
        $sftp->shouldReceive('upload')->andReturn(['success' => false, 'message' => 'Connection refused']);

        $result = (new WebpageDeliveryStrategy($sftp))->deliver($list, $this->webpageDigestData($list), $this->fakeBuilder());

        $this->assertFalse($result);
    }

    #[Test]
    public function raises_AdminAlert_on_SFTP_failure(): void
    {
        $user = User::factory()->create();
        $dest = $this->makeDest($user);
        $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create();

        $sftp = mock(SftpService::class);
        $sftp->shouldReceive('upload')->andReturn(['success' => false, 'message' => 'Timeout']);

        (new WebpageDeliveryStrategy($sftp))->deliver($list, $this->webpageDigestData($list), $this->fakeBuilder());

        $this->assertDatabaseHas('admin_alerts', [
            'category' => 'sftp',
            'tier'     => 2,
        ]);
    }

    #[Test]
    public function sends_DigestReadyNotification_when_notify_by_email_is_true(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $dest = $this->makeDest($user);
        $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create(['notify_by_email' => true]);

        $sftp = mock(SftpService::class);
        $sftp->shouldReceive('upload')->andReturn(['success' => true, 'path' => '/digests/test']);

        (new WebpageDeliveryStrategy($sftp))->deliver($list, $this->webpageDigestData($list), $this->fakeBuilder());

        Notification::assertSentTo($user, DigestReadyNotification::class);
    }

    #[Test]
    public function does_not_send_notification_when_notify_by_email_is_false(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $dest = $this->makeDest($user);
        $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create(['notify_by_email' => false]);

        $sftp = mock(SftpService::class);
        $sftp->shouldReceive('upload')->andReturn(['success' => true, 'path' => '/digests/test']);

        (new WebpageDeliveryStrategy($sftp))->deliver($list, $this->webpageDigestData($list), $this->fakeBuilder());

        Notification::assertNotSentTo($user, DigestReadyNotification::class);
    }
}