<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\FinalizeScriptWizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step3ControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeEpisodeWithSession(User $user): PodcastEpisodePlanning
    {
        $show    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $episode = PodcastEpisodePlanning::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
        ]);
        session(['wizard.finalize_script.episode_id' => $episode->id]);
        return $episode;
    }

    public function test_show_renders_with_valid_session(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.finalize.step3'))
            ->assertOk();
    }

    public function test_show_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.wizard.finalize.step3'))
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }

    public function test_store_updates_title_and_redirects_to_step4(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step3.store'), [
                'title' => 'Updated Title',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step4'));

        $this->assertDatabaseHas('podcast_episodes_planning', [
            'id'    => $episode->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_store_validates_required_title(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step3.store'), ['title' => ''])
            ->assertSessionHasErrors(['title']);
    }

    /**
     * Titles that start with a digit must be rejected.
     * The episode number is added automatically on publishing — it must not
     * appear in the stored title.
     *
     * @dataProvider titlesStartingWithDigit
     */
    public function test_store_rejects_title_starting_with_a_digit(string $title): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step3.store'), [
                'title' => $title,
            ])
            ->assertSessionHasErrors(['title']);
    }

    public static function titlesStartingWithDigit(): array
    {
        return [
            'bare number'              => ['12'],
            'number dash title'        => ['12 - My Episode'],
            'number em-dash title'     => ['12 — My Episode'],
            'hash number em-dash'      => ['#12 — My Episode'],
            'hash number dash'         => ['#12 - My Episode'],
            'number colon title'       => ['12: My Episode'],
            'number with no separator' => ['12My Episode'],
            'single digit'             => ['1 Episode'],
        ];
    }

    public function test_store_accepts_title_starting_with_a_word(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step3.store'), [
                'title' => 'Ten Things I Learned',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step4'));

        $this->assertDatabaseHas('podcast_episodes_planning', [
            'id'    => $episode->id,
            'title' => 'Ten Things I Learned',
        ]);
    }

    public function test_store_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_episodes_planning.wizard.finalize.step3.store'), ['title' => 'Test'])
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }
}