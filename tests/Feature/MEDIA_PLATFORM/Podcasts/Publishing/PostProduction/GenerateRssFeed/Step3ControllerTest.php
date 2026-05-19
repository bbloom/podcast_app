<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\GenerateRssFeed;

use App\Models\User;
use Aws\S3\S3Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Services\GenerateRssFeedResult;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Services\RssFeedGeneratorService;
use Tests\TestCase;

class Step3ControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEpisode(User $user, PodcastEpisodeStatus $status = PodcastEpisodeStatus::ready_to_generate_rss_feed): PodcastEpisode
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
        ]);
    }

    private function withStep1Session(PodcastEpisode $episode): array
    {
        return ['wizard.generate_rss_feed.podcast_episode_id' => $episode->id];
    }

    private function mockGeneratorSuccess(): void
    {
        $this->instance(
            RssFeedGeneratorService::class,
            \Mockery::mock(RssFeedGeneratorService::class, function ($mock) {
                $mock->shouldReceive('generate')
                    ->once()
                    ->andReturn(GenerateRssFeedResult::success('<rss version="2.0"></rss>'));
                $mock->shouldReceive('getFileName')
                    ->andReturn('rss_test_show.xml');
            })
        );
    }

    private function mockGeneratorFailure(): void
    {
        $this->instance(
            RssFeedGeneratorService::class,
            \Mockery::mock(RssFeedGeneratorService::class, function ($mock) {
                $mock->shouldReceive('generate')
                    ->once()
                    ->andReturn(GenerateRssFeedResult::failure('No eligible episodes found.'));
                $mock->shouldReceive('getFileName')
                    ->andReturn('rss_test_show.xml');
            })
        );
    }

    private function mockS3Success(): void
    {
        $this->instance(
            S3Client::class,
            \Mockery::mock(S3Client::class, function ($mock) {
                $mock->shouldReceive('putObject')->andReturn([]);
            })
        );
    }

    // -------------------------------------------------------------------------
    // show (GET) — access guards
    // -------------------------------------------------------------------------

    public function test_show_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->get(route('post_production.generate_rss_feed.step3', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_show_redirects_another_users_episode_to_index(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode($other);

        $this->actingAs($user)
            ->withSession($this->withStep1Session($episode))
            ->get(route('post_production.generate_rss_feed.step3', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.index'));
    }

    public function test_show_redirects_wrong_status_to_index(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->withSession($this->withStep1Session($episode))
            ->get(route('post_production.generate_rss_feed.step3', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.index'));
    }

    public function test_show_redirects_to_step1_when_session_missing(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.step3', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.step1', $episode));
    }

    // -------------------------------------------------------------------------
    // show (GET) — generation failure
    // -------------------------------------------------------------------------

    public function test_show_redirects_to_step2_when_generation_fails(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->mockGeneratorFailure();

        $this->actingAs($user)
            ->withSession($this->withStep1Session($episode))
            ->get(route('post_production.generate_rss_feed.step3', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.step2', $episode));
    }

    // -------------------------------------------------------------------------
    // show (GET) — does not change episode status
    // -------------------------------------------------------------------------

    public function test_show_does_not_change_episode_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->mockGeneratorSuccess();

        $this->actingAs($user)
            ->withSession($this->withStep1Session($episode))
            ->get(route('post_production.generate_rss_feed.step3', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed->value,
        ]);
    }
}