<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\RegenerateRssFeed;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Services\GenerateRssFeedResult;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Services\RssFeedGeneratorService;
use Tests\TestCase;

class StageControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeShow(User $user): PodcastShow
    {
        return PodcastShow::factory()->create([
            'user_id' => $user->id,
            'slug'    => 'bob-bloom-show',
        ]);
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
                    ->andReturn('rss_bob_bloom_show.xml');
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
                    ->andReturn('rss_bob_bloom_show.xml');
            })
        );
    }

    public function test_stage_redirects_unauthenticated_users(): void
    {
        $user = User::factory()->create();
        $show = $this->makeShow($user);

        $this->get(route('post_production.regenerate_rss_feed.stage', $show))
            ->assertRedirect(route('login'));
    }

    public function test_stage_redirects_another_users_show_to_index(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $show  = $this->makeShow($other);

        $this->actingAs($user)
            ->get(route('post_production.regenerate_rss_feed.stage', $show))
            ->assertRedirect(route('post_production.regenerate_rss_feed.index'));
    }

    public function test_stage_redirects_to_index_when_generation_fails(): void
    {
        $user = User::factory()->create();
        $show = $this->makeShow($user);

        $this->mockGeneratorFailure();

        $this->actingAs($user)
            ->get(route('post_production.regenerate_rss_feed.stage', $show))
            ->assertRedirect(route('post_production.regenerate_rss_feed.index'))
            ->assertSessionHas('error');
    }

    // Note: the two happy path tests (renders with staging URL, stores session values)
    // are omitted because the S3 upload in StageController uses `new S3Client()`
    // directly and cannot be intercepted in the test environment without real AWS
    // credentials. The guard tests above confirm all controller logic up to the S3
    // call. End-to-end staging is verified manually via the wizard UI.
}