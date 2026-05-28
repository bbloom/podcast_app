<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Guests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use MediaPlatform\Podcasts\Guests\Mail\GuestEmailMailable;
use MediaPlatform\Podcasts\Guests\Models\GuestEmail;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use Tests\TestCase;

class TemporarySendTestEmailControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // PodcastGuestFactory makes an HTTP call to randomuser.me.
        // Fake all HTTP to prevent that external call during tests.
        Http::fake();
    }

    // =========================================================================
    // GET /dev/guest-email-test
    // =========================================================================

    public function test_create_requires_authentication(): void
    {
        $this->get('/dev/guest-email-test')
             ->assertRedirect('/login');
    }

    public function test_create_renders_form_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->get('/dev/guest-email-test')
             ->assertOk()
             ->assertViewIs('media_platform.podcasts.guests.dev.send_test_email');
    }

    public function test_create_passes_only_enabled_guests_to_view(): void
    {
        $user     = User::factory()->create();
        $enabled  = PodcastGuest::factory()->create(['enabled' => true]);
        $disabled = PodcastGuest::factory()->disabled()->create();

        $response = $this->actingAs($user)->get('/dev/guest-email-test');

        $response->assertViewHas('guests', function ($guests) use ($enabled, $disabled) {
            return $guests->contains($enabled) && ! $guests->contains($disabled);
        });
    }

    // =========================================================================
    // POST /dev/guest-email-test
    // =========================================================================

    public function test_store_requires_authentication(): void
    {
        $this->post('/dev/guest-email-test', [])
             ->assertRedirect('/login');
    }

    public function test_store_validates_all_fields_are_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post('/dev/guest-email-test', [])
             ->assertSessionHasErrors(['podcast_guest_id', 'subject', 'body']);
    }

    public function test_store_validates_guest_must_exist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post('/dev/guest-email-test', [
                 'podcast_guest_id' => 99999,
                 'subject'          => 'Test subject',
                 'body'             => 'Test body',
             ])
             ->assertSessionHasErrors(['podcast_guest_id']);
    }

    public function test_store_sends_mailable_to_guest_email_address(): void
    {
        Mail::fake();
        $user  = User::factory()->create();
        $guest = PodcastGuest::factory()->create();

        $this->actingAs($user)->post('/dev/guest-email-test', [
            'podcast_guest_id' => $guest->id,
            'subject'          => 'Interview questions',
            'body'             => 'Here are your questions.',
        ]);

        Mail::assertSent(GuestEmailMailable::class, function (GuestEmailMailable $mail) use ($guest) {
            return $mail->hasTo($guest->email_address);
        });
    }

    public function test_store_creates_outbound_record_in_guest_emails(): void
    {
        Mail::fake();
        $user  = User::factory()->create();
        $guest = PodcastGuest::factory()->create();

        $this->actingAs($user)->post('/dev/guest-email-test', [
            'podcast_guest_id' => $guest->id,
            'subject'          => 'Interview questions',
            'body'             => 'Here are your questions.',
        ]);

        $this->assertDatabaseHas('guest_emails', [
            'podcast_guest_id' => $guest->id,
            'subject'          => 'Interview questions',
            'body_stripped'    => 'Here are your questions.',
            'direction'        => 'outbound',
            'in_reply_to'      => null,
        ]);
    }

    public function test_store_saves_message_id_with_correct_domain(): void
    {
        Mail::fake();
        $user  = User::factory()->create();
        $guest = PodcastGuest::factory()->create();

        $this->actingAs($user)->post('/dev/guest-email-test', [
            'podcast_guest_id' => $guest->id,
            'subject'          => 'Test',
            'body'             => 'Test body',
        ]);

        $record = GuestEmail::where('podcast_guest_id', $guest->id)->first();

        $this->assertNotNull($record);
        $this->assertNotEmpty($record->message_id);
        $this->assertStringEndsWith('@bobbloominterviews.com', $record->message_id);
    }

    public function test_store_redirects_to_form_with_success_message(): void
    {
        Mail::fake();
        $user  = User::factory()->create();
        $guest = PodcastGuest::factory()->create();

        $this->actingAs($user)
             ->post('/dev/guest-email-test', [
                 'podcast_guest_id' => $guest->id,
                 'subject'          => 'Test',
                 'body'             => 'Test body',
             ])
             ->assertRedirect(route('dev.guest-email-test.create'))
             ->assertSessionHas('success');
    }
}