<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\PrepareForPublishingWizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step2ControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeEpisodeWithSession(User $user): PodcastEpisodePlanning
    {
        $show = PodcastShow::factory()->create([
            'user_id'                  => $user->id,
            'slug'                     => 'bob-bloom-show',
            'storage_audio_files_url'  => 'https://cdn.example.com/audio/',
            'itunes_link'              => 'https://bobbloomshow.com',
            'title'                    => 'The Bob Bloom Show',
        ]);
        $episode = PodcastEpisodePlanning::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'title'           => 'My Episode',
            'episode_number'  => 10,
        ]);
        session(['wizard.prepare_for_publishing.episode_id' => $episode->id]);
        return $episode;
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_renders_with_valid_session(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.publish.step2'))
            ->assertOk();
    }

    public function test_show_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.wizard.publish.step2'))
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_episodes_planning.wizard.publish.step2'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_saves_edits_to_planning_record_and_redirects_to_step3(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.publish.step2.store'), [
                'title'           => 'Updated Title',
                'episode_number'  => 99,
                'scheduled_date'  => '2026-10-01',
                'website_content' => 'Updated content.',
                'website_excerpt' => 'Updated excerpt.',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.publish.step3'));

        $this->assertDatabaseHas('podcast_episodes_planning', [
            'id'             => $episode->id,
            'title'          => 'Updated Title',
            'episode_number' => 99,
        ]);
    }

    public function test_store_validates_required_title(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.publish.step2.store'), ['title' => ''])
            ->assertSessionHasErrors(['title']);
    }

    public function test_store_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_episodes_planning.wizard.publish.step2.store'), ['title' => 'Test'])
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }
}