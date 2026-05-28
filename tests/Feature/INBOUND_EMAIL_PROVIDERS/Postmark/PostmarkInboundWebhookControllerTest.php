<?php

namespace Tests\Feature\INBOUND_EMAIL_PROVIDERS\Postmark;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use MediaPlatform\Podcasts\Guests\Enums\GuestEmailDirection;
use MediaPlatform\Podcasts\Guests\Models\GuestEmail;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use Tests\TestCase;

class PostmarkInboundWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookUser     = 'pmhook';
    private string $webhookPassword = 'test-webhook-password';

    protected function setUp(): void
    {
        parent::setUp();

        // PodcastGuestFactory makes an HTTP call to randomuser.me — fake it.
        Http::fake();

        // Set webhook credentials in config for the duration of the test.
        config([
            'services.postmark_webhook.user'     => $this->webhookUser,
            'services.postmark_webhook.password' => $this->webhookPassword,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * POST to the inbound webhook with Basic Auth credentials.
     */
    private function postWithAuth(array $payload, ?string $user = null, ?string $password = null)
    {
        return $this->postJson(
            '/webhooks/postmark/inbound',
            $payload,
            ['Authorization' => 'Basic ' . base64_encode(($user ?? $this->webhookUser) . ':' . ($password ?? $this->webhookPassword))],
        );
    }

    /**
     * Build a minimal valid Postmark inbound payload for a given guest and optional reply-to.
     */
    private function inboundPayload(PodcastGuest $guest, ?string $inReplyTo = null): array
    {
        $headers = [
            ['Name' => 'Message-ID', 'Value' => '<reply-abc@mail.example.com>'],
        ];

        if ($inReplyTo) {
            $headers[] = ['Name' => 'In-Reply-To', 'Value' => '<' . $inReplyTo . '>'];
        }

        return [
            'From'             => $guest->full_name . ' <' . $guest->email_address . '>',
            'FromFull'         => ['Email' => $guest->email_address, 'Name' => $guest->full_name],
            'Subject'          => 'Re: Interview Questions',
            'TextBody'         => "Thanks for reaching out!\n\n> Original message",
            'StrippedTextReply'=> 'Thanks for reaching out!',
            'HtmlBody'         => '<p>Thanks for reaching out!</p>',
            'Headers'          => $headers,
        ];
    }

    // =========================================================================
    // Authentication
    // =========================================================================

    public function test_rejects_request_with_missing_credentials(): void
    {
        $this->postJson('/webhooks/postmark/inbound', [])
             ->assertForbidden();
    }

    public function test_rejects_request_with_wrong_password(): void
    {
        $this->postWithAuth([], password: 'wrong-password')
             ->assertForbidden();
    }

    public function test_rejects_request_with_wrong_user(): void
    {
        $this->postWithAuth([], user: 'wronguser')
             ->assertForbidden();
    }

    // =========================================================================
    // Successful inbound from a known guest
    // =========================================================================

    public function test_returns_204_for_valid_inbound_from_known_guest(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->postWithAuth($this->inboundPayload($guest))
             ->assertNoContent();
    }

    public function test_stores_inbound_row_for_known_guest(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->postWithAuth($this->inboundPayload($guest));

        $this->assertDatabaseHas('guest_emails', [
            'podcast_guest_id' => $guest->id,
            'direction'        => GuestEmailDirection::Inbound->value,
            'subject'          => 'Re: Interview Questions',
            'body_stripped'    => 'Thanks for reaching out!',
        ]);
    }

    public function test_stores_message_id_without_angle_brackets(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->postWithAuth($this->inboundPayload($guest));

        $record = GuestEmail::where('podcast_guest_id', $guest->id)->first();
        $this->assertNotNull($record);
        $this->assertStringNotContainsString('<', $record->message_id);
        $this->assertStringNotContainsString('>', $record->message_id);
    }

    public function test_correlates_reply_to_prior_outbound_via_in_reply_to(): void
    {
        $guest    = PodcastGuest::factory()->create();
        $outbound = GuestEmail::factory()->create(['podcast_guest_id' => $guest->id]);

        $this->postWithAuth($this->inboundPayload($guest, $outbound->message_id));

        $this->assertDatabaseHas('guest_emails', [
            'podcast_guest_id' => $guest->id,
            'direction'        => GuestEmailDirection::Inbound->value,
            'in_reply_to'      => $outbound->message_id,
        ]);
    }

    public function test_stores_null_in_reply_to_for_cold_inbound(): void
    {
        $guest = PodcastGuest::factory()->create();

        $this->postWithAuth($this->inboundPayload($guest));

        $record = GuestEmail::where('podcast_guest_id', $guest->id)->first();
        $this->assertNull($record->in_reply_to);
    }

    // =========================================================================
    // Unknown sender
    // =========================================================================

    public function test_returns_204_and_discards_inbound_from_unknown_sender(): void
    {
        $unknownGuest = PodcastGuest::make(['email_address' => 'unknown@example.com', 'full_name' => 'Unknown']);

        $this->postWithAuth($this->inboundPayload($unknownGuest))
             ->assertNoContent();

        $this->assertDatabaseCount('guest_emails', 0);
    }

    // =========================================================================
    // Unrecognised payload
    // =========================================================================

    public function test_returns_204_for_unrecognised_payload_type(): void
    {
        $this->postWithAuth(['SomeUnknownField' => 'value'])
             ->assertNoContent();

        $this->assertDatabaseCount('guest_emails', 0);
    }
}