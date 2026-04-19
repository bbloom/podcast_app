<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Publishing/EmailDeliveryStrategyTest.php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use MediaPlatform\Digest\Publishing\Mail\DigestMailable;
use MediaPlatform\Digest\Publishing\Strategies\EmailDeliveryStrategy;
use MediaPlatform\Digest\Enums\OutputType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

/**
 * EmailDeliveryStrategyTest
 *
 * Tests EmailDeliveryStrategy::deliver() in isolation.
 */

uses(RefreshDatabase::class);

// =============================================================================
// Helpers
// =============================================================================

function emailDigestData(ListModel $list): array
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

// =============================================================================
// Tests
// =============================================================================

it('sends DigestMailable to list owner', function () {
    Mail::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);

    $strategy = new EmailDeliveryStrategy();
    $builder  = mock(DigestBuilderService::class);
    $result   = $strategy->deliver($list, emailDigestData($list), $builder);

    expect($result)->toBeTrue();
    Mail::assertSent(DigestMailable::class, fn ($mail) => $mail->hasTo($user->email));
});

it('returns false on mail failure', function () {
    Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP failure'));

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);

    $strategy = new EmailDeliveryStrategy();
    $builder  = mock(DigestBuilderService::class);
    $result   = $strategy->deliver($list, emailDigestData($list), $builder);

    expect($result)->toBeFalse();
});

it('raises AdminAlert on mail failure', function () {
    Mail::shouldReceive('to')->andThrow(new \RuntimeException('Connection refused'));

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);

    $strategy = new EmailDeliveryStrategy();
    $builder  = mock(DigestBuilderService::class);
    $strategy->deliver($list, emailDigestData($list), $builder);

    $this->assertDatabaseHas('admin_alerts', [
        'category' => 'infrastructure',
        'tier'     => 2,
    ]);
});