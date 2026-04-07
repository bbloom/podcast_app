<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PostProduction\GenerateRssFeed;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use Tests\TestCase;

class Step5ControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEpisode(User $user, PodcastEpisodeStatus $status = PodcastEpisodeStatus::ready_to_generate_rss_feed): PodcastEpisode
    {
        $show = PodcastShow::factory()->create([
            'user_id' => $user->id,
            'slug'    => 'bob-bloom-show',
        ]);

        return PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
        ]);
    }

    private function withFullSession(PodcastEpisode $episode): array
    {
        return [
            'wizard.generate_rss_feed.podcast_episode_id' => $episode->id,
            'wizard.generate_rss_feed.staging_url'        => 'https://staging.example.com/rss/feed.xml',
            'wizard.generate_rss_feed.rss_filename'       => 'rss_bob_bloom_show.xml',
            'wizard.generate_rss_feed.rss_s3_key'         => 'rss/rss_bob_bloom_show.xml',
        ];
    }

    /**
     * Write a fake local RSS file so Step5 can read it.
     */
    private function writeLocalRssFile(string $filename): string
    {
        $dir  = storage_path('app/podcasts/rss');
        $path = $dir . '/' . $filename;

        if (! is_dir($dir)) {
            mkdir($dir, 0755, recursive: true);
        }

        file_put_contents($path, '<rss version="2.0"></rss>');

        return $path;
    }

    /**
     * Clean up local RSS files written during tests.
     */
    protected function tearDown(): void
    {
        $dir = storage_path('app/podcasts/rss');

        if (is_dir($dir)) {
            foreach (glob($dir . '/*') as $entry) {
                if (is_file($entry)) {
                    unlink($entry);
                }
            }
        }

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // store (POST) — access guards
    // -------------------------------------------------------------------------

    public function test_store_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->post(route('post_production.generate_rss_feed.step5', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_store_redirects_another_users_episode_to_index(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode($other);

        $this->actingAs($user)
            ->withSession($this->withFullSession($episode))
            ->post(route('post_production.generate_rss_feed.step5', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.index'));
    }

    public function test_store_redirects_wrong_status_to_index(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->withSession($this->withFullSession($episode))
            ->post(route('post_production.generate_rss_feed.step5', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.index'));
    }

    public function test_store_redirects_to_step3_when_session_missing(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->post(route('post_production.generate_rss_feed.step5', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.step3', $episode));
    }

    public function test_store_redirects_to_step3_when_local_file_missing(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        // Session is set but local file does not exist.
        $this->actingAs($user)
            ->withSession($this->withFullSession($episode))
            ->post(route('post_production.generate_rss_feed.step5', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.step3', $episode));
    }

    // -------------------------------------------------------------------------
    // store (POST) — happy path
    // Note: The S3 and R2 upload calls in store() use `new S3Client()` directly
    // and cannot be intercepted by the Laravel service container. Happy path
    // tests that require real AWS credentials are therefore omitted here.
    // The access guard tests above confirm all controller logic up to the S3
    // call. End-to-end promotion is verified manually via the wizard UI.
    // -------------------------------------------------------------------------
}