<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\PrepareForPublishingWizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use MediaPlatform\Podcasts\Links\Models\PodcastLink;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step3ControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a fully-configured episode with session — all fields needed
     * for the population methods to derive values without errors.
     */
    private function makeEpisodeWithSession(User $user): array
    {
        $show = PodcastShow::factory()->create([
            'user_id'                 => $user->id,
            'title'                   => 'The Bob Bloom Show',
            'slug'                    => 'bob-bloom-show',
            'storage_audio_files_url' => 'https://cdn.example.com/audio/',
            'itunes_link'             => 'https://bobbloomshow.com',
        ]);

        $episode = PodcastEpisodePlanning::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'title'           => 'My Episode Title',
            'episode_number'  => 42,
            'scheduled_date'  => '2026-10-01',
            'website_content' => 'Episode content.',
            'website_excerpt' => 'Short excerpt.',
        ]);

        session(['wizard.prepare_for_publishing.episode_id' => $episode->id]);

        return [$episode, $show];
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_renders_with_valid_session(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.publish.step3'))
            ->assertOk();
    }

    public function test_show_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.wizard.publish.step3'))
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_episodes_planning.wizard.publish.step3'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // store — happy path
    // -------------------------------------------------------------------------

    public function test_store_creates_published_episode(): void
    {
        $user = User::factory()->create();
        [$episode] = $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.publish.step3.store'));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'user_id'        => $user->id,
            'itunes_episode' => 42,
        ]);
    }

    public function test_store_migrates_guests_to_published_episode(): void
    {
        $user = User::factory()->create();
        [$episode] = $this->makeEpisodeWithSession($user);

        $guest = PodcastGuest::factory()->create();
        $episode->guests()->attach($guest->id);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.publish.step3.store'));

        $published = PodcastEpisode::where('user_id', $user->id)->first();
        $this->assertNotNull($published);
        $this->assertTrue($published->guests()->where('podcast_guests.id', $guest->id)->exists());
    }

    public function test_store_migrates_links_to_published_episode(): void
    {
        $user = User::factory()->create();
        [$episode] = $this->makeEpisodeWithSession($user);

        $link = PodcastLink::factory()->create(['user_id' => $user->id]);
        $episode->links()->attach($link->id);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.publish.step3.store'));

        $published = PodcastEpisode::where('user_id', $user->id)->first();
        $this->assertNotNull($published);
        $this->assertTrue($published->links()->where('podcast_links.id', $link->id)->exists());
    }

    public function test_store_hard_deletes_planning_record(): void
    {
        $user = User::factory()->create();
        [$episode] = $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.publish.step3.store'));

        $this->assertDatabaseMissing('podcast_episodes_planning', ['id' => $episode->id]);
    }

    public function test_store_clears_wizard_session(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.publish.step3.store'));

        $this->assertNull(session('wizard.prepare_for_publishing.episode_id'));
    }

    public function test_store_redirects_to_published_episode_show(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.publish.step3.store'))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    // -------------------------------------------------------------------------
    // store — session / auth guards
    // -------------------------------------------------------------------------

    public function test_store_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_episodes_planning.wizard.publish.step3.store'))
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }

    public function test_store_redirects_unauthenticated_users(): void
    {
        $this->post(route('podcast_episodes_planning.wizard.publish.step3.store'))
            ->assertRedirect(route('login'));
    }
}