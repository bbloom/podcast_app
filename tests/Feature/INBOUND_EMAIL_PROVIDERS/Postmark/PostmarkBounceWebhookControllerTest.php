<?php

namespace Tests\Feature\INBOUND_EMAIL_PROVIDERS\Postmark;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use MediaPlatform\Podcasts\Guests\Enums\GuestEmailDirection;
use MediaPlatform\Podcasts\Guests\Models\GuestEmail;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use Tests\TestCase;

class PostmarkBounceWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookUser     = 'pmhook';
    private string $webhookPassword = 'test-webhook-password';

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        config([
            'services.postmark_webhook.user'     => $this->webhookUser,
            'services.postmark_webhook.password' => $this->webhookPassword,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function postWithAuth(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(
            '/webhooks/postmark/bounce',
            $payload,
            ['Authorization' => 'Basic ' . base64_encode($this->webhookUser . ':' . $this->webhookPassword)],
        );
    }

    private function bouncePayload(PodcastGuest $guest, string $type = 'HardBounce'): array
    {
        return [
            'Type'        => $type,
            'Email'       => $guest->email_address,
            'Description' => 'The server was unable to deliver your message.',
            'BouncedAt'   => now()->toIso8601String(),
        ];
    }

    // =========================================================================
    // Authentication
    // =========================================================================

    public function test_rejects_request_with_missing_credentials(): void
    {
        $this->postJson('/webhooks/postmark/bounce', [])
             ->assertForbidden();
    }

    // =========================================================================
    // HardBounce
    // =========================================================================

    public function test_hard_bounce_flags_guest_record(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->postWithAuth($this->bouncePayload($guest, 'HardBounce'))
             ->assertNoContent();

        $guest->refresh();
        $this->assertTrue($guest->email_bounced);
        $this->assertNotNull($guest->email_bounced_at);
    }

    public function test_hard_bounce_stores_guest_email_row(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->postWithAuth($this->bouncePayload($guest, 'HardBounce'));

        $this->assertDatabaseHas('guest_emails', [
            'podcast_guest_id' => $guest->id,
            'direction'        => GuestEmailDirection::HardBounce->value,
        ]);
    }

    // =========================================================================
    // SoftBounce
    // =========================================================================

    public function test_soft_bounce_does_not_flag_guest_record(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->postWithAuth($this->bouncePayload($guest, 'SoftBounce'));

        $guest->refresh();
        $this->assertFalse($guest->email_bounced);
        $this->assertNull($guest->email_bounced_at);
    }

    public function test_soft_bounce_stores_guest_email_row(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->postWithAuth($this->bouncePayload($guest, 'SoftBounce'));

        $this->assertDatabaseHas('guest_emails', [
            'podcast_guest_id' => $guest->id,
            'direction'        => GuestEmailDirection::SoftBounce->value,
        ]);
    }

    // =========================================================================
    // SpamComplaint
    // =========================================================================

    public function test_spam_complaint_does_not_flag_guest_record(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->postWithAuth($this->bouncePayload($guest, 'SpamComplaint'));

        $guest->refresh();
        $this->assertFalse($guest->email_bounced);
    }

    public function test_spam_complaint_stores_guest_email_row(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->postWithAuth($this->bouncePayload($guest, 'SpamComplaint'));

        $this->assertDatabaseHas('guest_emails', [
            'podcast_guest_id' => $guest->id,
            'direction'        => GuestEmailDirection::SpamComplaint->value,
        ]);
    }

    // =========================================================================
    // Unknown sender
    // =========================================================================

    public function test_returns_204_and_discards_bounce_for_unknown_address(): void
    {
        $unknown = PodcastGuest::make(['email_address' => 'unknown@example.com']);

        $this->postWithAuth($this->bouncePayload($unknown))
             ->assertNoContent();

        $this->assertDatabaseCount('guest_emails', 0);
    }
}