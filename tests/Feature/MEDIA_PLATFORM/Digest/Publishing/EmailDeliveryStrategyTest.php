<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Publishing/EmailDeliveryStrategyTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Publishing;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use MediaPlatform\Digest\Publishing\Mail\DigestMailable;
use MediaPlatform\Digest\Publishing\Strategies\EmailDeliveryStrategy;
use MediaPlatform\Digest\Enums\OutputType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailDeliveryStrategyTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function emailDigestData(ListModel $list): array
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

    // =========================================================================
    // Tests
    // =========================================================================

    #[Test]
    public function sends_DigestMailable_to_list_owner(): void
    {
        Mail::fake();

        $user     = User::factory()->create();
        $list     = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);
        $strategy = new EmailDeliveryStrategy();
        $builder  = mock(DigestBuilderService::class);

        $result = $strategy->deliver($list, $this->emailDigestData($list), $builder);

        $this->assertTrue($result);
        Mail::assertSent(DigestMailable::class, fn ($mail) => $mail->hasTo($user->email));
    }

    #[Test]
    public function returns_false_on_mail_failure(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP failure'));

        $user     = User::factory()->create();
        $list     = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);
        $strategy = new EmailDeliveryStrategy();
        $builder  = mock(DigestBuilderService::class);

        $result = $strategy->deliver($list, $this->emailDigestData($list), $builder);

        $this->assertFalse($result);
    }

    #[Test]
    public function raises_AdminAlert_on_mail_failure(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Connection refused'));

        $user     = User::factory()->create();
        $list     = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);
        $strategy = new EmailDeliveryStrategy();
        $builder  = mock(DigestBuilderService::class);

        $strategy->deliver($list, $this->emailDigestData($list), $builder);

        $this->assertDatabaseHas('admin_alerts', [
            'category' => 'infrastructure',
            'tier'     => 2,
        ]);
    }
}