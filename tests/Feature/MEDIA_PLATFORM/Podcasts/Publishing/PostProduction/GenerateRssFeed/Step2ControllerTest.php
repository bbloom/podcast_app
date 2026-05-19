<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\GenerateRssFeed;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Services\RssFeedValidatorResult;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Services\RssFeedValidatorService;
use Tests\TestCase;

class Step2ControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a fully populated show and episode belonging to the given user,
     * with all fields required for validation pre-filled.
     */
    private function fullyPopulatedEpisode(User $user): PodcastEpisode
    {
        $show = PodcastShow::factory()->create([
            'user_id'                 => $user->id,
            'rss_link'                => 'https://example.com/feed.xml',
            'description'             => 'A great podcast show.',
            'itunes_image'            => 'https://example.com/art.jpg',
            'itunes_language'         => 'en',
            'itunes_category_primary' => 'Technology',
            'itunes_author'           => 'Bob Bloom',
            'itunes_link'             => 'https://example.com',
            'itunes_email'            => 'bob@example.com',
            'itunes_name'             => 'Bob Bloom',
            'itunes_title'            => 'The Bob Bloom Show',
            'itunes_type'             => 'episodic',
        ]);

        return PodcastEpisode::factory()->create([
            'user_id'                  => $user->id,
            'podcast_show_id'          => $show->id,
            'status'                   => PodcastEpisodeStatus::ready_to_generate_rss_feed,
            'rss_feed_enabled'         => true,
            'title'                    => '#42 - My Episode',
            'itunes_enclosure_url'     => 'https://example.com/audio/ep42.mp3',
            'itunes_enclosure_length'  => '47823104',
            'itunes_enclosure_type'    => 'audio/mpeg',
            'itunes_guid'              => 'abc123-def4-5678-ghij-klmnopqrstuv',
            'itunes_description'       => 'Episode description.',
            'itunes_duration'          => '01:15:32',
            'itunes_link'              => 'https://example.com/episode/ep42',
            'itunes_episode'           => 42,
            'itunes_episode_type'      => 'full',
            'itunes_pubdate'           => now()->addDays(3),
        ]);
    }

    /**
     * Mock the validator to return a passing result.
     */
    private function mockValidatorPassing(): void
    {
        $this->instance(
            RssFeedValidatorService::class,
            \Mockery::mock(RssFeedValidatorService::class, function ($mock) {
                $mock->shouldReceive('validate')
                    ->once()
                    ->andReturn(new RssFeedValidatorResult([], [], false));
            })
        );
    }

    /**
     * Mock the validator to return a failing result.
     */
    private function mockValidatorFailing(array $failures = []): void
    {
        if (empty($failures)) {
            $failures = [['field' => 'itunes_description', 'message' => 'Description is missing.']];
        }

        $this->instance(
            RssFeedValidatorService::class,
            \Mockery::mock(RssFeedValidatorService::class, function ($mock) use ($failures) {
                $mock->shouldReceive('validate')
                    ->once()
                    ->andReturn(new RssFeedValidatorResult($failures, [], false));
            })
        );
    }

    /**
     * Mock the validator to return an R2 download failure.
     */
    private function mockValidatorR2Failed(): void
    {
        $this->instance(
            RssFeedValidatorService::class,
            \Mockery::mock(RssFeedValidatorService::class, function ($mock) {
                $mock->shouldReceive('validate')
                    ->once()
                    ->andReturn(new RssFeedValidatorResult([], [], true));
            })
        );
    }

    /**
     * Set the wizard session state as if Step 1 was completed.
     */
    private function withStep1Session(PodcastEpisode $episode): array
    {
        return ['wizard.generate_rss_feed.podcast_episode_id' => $episode->id];
    }

    // -------------------------------------------------------------------------
    // show (GET) — access guards
    // -------------------------------------------------------------------------

    public function test_show_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->fullyPopulatedEpisode($user);

        $this->get(route('post_production.generate_rss_feed.step2', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_show_redirects_another_users_episode_to_index(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->fullyPopulatedEpisode($other);

        $this->actingAs($user)
            ->withSession($this->withStep1Session($episode))
            ->get(route('post_production.generate_rss_feed.step2', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.index'));
    }

    public function test_show_redirects_wrong_status_to_index(): void
    {
        $user    = User::factory()->create();
        $show    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $episode = PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::ready_for_auphonic,
        ]);

        $this->actingAs($user)
            ->withSession($this->withStep1Session($episode))
            ->get(route('post_production.generate_rss_feed.step2', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.index'));
    }

    public function test_show_redirects_to_step1_when_session_missing(): void
    {
        $user    = User::factory()->create();
        $episode = $this->fullyPopulatedEpisode($user);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.step2', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.step1', $episode));
    }

    // -------------------------------------------------------------------------
    // show (GET) — validation outcomes
    // -------------------------------------------------------------------------

    public function test_show_redirects_to_step3_when_validation_passes(): void
    {
        $user    = User::factory()->create();
        $episode = $this->fullyPopulatedEpisode($user);

        $this->mockValidatorPassing();

        $this->actingAs($user)
            ->withSession($this->withStep1Session($episode))
            ->get(route('post_production.generate_rss_feed.step2', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.step3', $episode));
    }

    public function test_show_renders_failures_when_validation_fails(): void
    {
        $user    = User::factory()->create();
        $episode = $this->fullyPopulatedEpisode($user);

        $this->mockValidatorFailing();

        $this->actingAs($user)
            ->withSession($this->withStep1Session($episode))
            ->get(route('post_production.generate_rss_feed.step2', $episode))
            ->assertOk()
            ->assertSee('Validation Failures')
            ->assertSee('Description is missing.');
    }

    public function test_show_renders_r2_manual_form_when_download_fails(): void
    {
        $user    = User::factory()->create();
        $episode = $this->fullyPopulatedEpisode($user);

        $this->mockValidatorR2Failed();

        $this->actingAs($user)
            ->withSession($this->withStep1Session($episode))
            ->get(route('post_production.generate_rss_feed.step2', $episode))
            ->assertOk()
            ->assertSee('Enclosure Verification')
            ->assertSee('Could not reach R2');
    }

    // -------------------------------------------------------------------------
    // store (POST) — R2 manual confirmation
    // -------------------------------------------------------------------------

    public function test_store_saves_enclosure_values_and_sets_session_flag(): void
    {
        $user    = User::factory()->create();
        $episode = $this->fullyPopulatedEpisode($user);

        $this->actingAs($user)
            ->withSession($this->withStep1Session($episode))
            ->post(route('post_production.generate_rss_feed.step2.store', $episode), [
                'itunes_enclosure_length' => '47823104',
                'itunes_duration'         => '01:15:32',
            ])
            ->assertRedirect(route('post_production.generate_rss_feed.step2', $episode))
            ->assertSessionHas('wizard.generate_rss_feed.enclosure_manually_verified_' . $episode->id, true);

        $this->assertDatabaseHas('podcast_episodes', [
            'id'                      => $episode->id,
            'itunes_enclosure_length' => '47823104',
            'itunes_duration'         => '01:15:32',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $user    = User::factory()->create();
        $episode = $this->fullyPopulatedEpisode($user);

        $this->actingAs($user)
            ->withSession($this->withStep1Session($episode))
            ->post(route('post_production.generate_rss_feed.step2.store', $episode), [])
            ->assertSessionHasErrors(['itunes_enclosure_length', 'itunes_duration']);
    }

    public function test_store_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->fullyPopulatedEpisode($user);

        $this->post(route('post_production.generate_rss_feed.step2.store', $episode), [
            'itunes_enclosure_length' => '47823104',
            'itunes_duration'         => '01:15:32',
        ])->assertRedirect(route('login'));
    }

    public function test_store_redirects_another_users_episode_to_index(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->fullyPopulatedEpisode($other);

        $this->actingAs($user)
            ->withSession($this->withStep1Session($episode))
            ->post(route('post_production.generate_rss_feed.step2.store', $episode), [
                'itunes_enclosure_length' => '47823104',
                'itunes_duration'         => '01:15:32',
            ])
            ->assertRedirect(route('post_production.generate_rss_feed.index'));
    }
}