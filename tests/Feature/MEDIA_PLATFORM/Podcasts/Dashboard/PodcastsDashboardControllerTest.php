<?php
// =============================================================================
// If a PodcastsDashboardControllerTest already exists, merge the two new test
// methods below into that class rather than replacing the file wholesale.
// The class boilerplate here is provided for the case where no test exists yet.
// =============================================================================

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class PodcastsDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeEpisode(User $user, array $overrides = []): PodcastEpisodePlanning
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);
        return PodcastEpisodePlanning::factory()->create(array_merge([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // $hasPendingScratch advisory
    // -------------------------------------------------------------------------

    public function test_dashboard_passes_has_pending_scratch_true_when_episode_has_scratch(): void
    {
        $user = User::factory()->create();
        $this->makeEpisode($user, ['script_scratch' => 'Some AI content.']);

        $this->actingAs($user)
            ->get(route('podcasts.dashboard'))
            ->assertOk()
            ->assertViewHas('hasPendingScratch', true);
    }

    public function test_dashboard_passes_has_pending_scratch_false_when_no_scratch(): void
    {
        $user = User::factory()->create();
        $this->makeEpisode($user, ['script_scratch' => null]);

        $this->actingAs($user)
            ->get(route('podcasts.dashboard'))
            ->assertOk()
            ->assertViewHas('hasPendingScratch', false);
    }

    public function test_dashboard_passes_has_pending_scratch_false_when_no_episodes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('podcasts.dashboard'))
            ->assertOk()
            ->assertViewHas('hasPendingScratch', false);
    }

    public function test_dashboard_does_not_count_another_users_scratch_as_pending(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        // Other user has scratch — current user does not.
        $this->makeEpisode($other, ['script_scratch' => 'Other user scratch.']);

        $this->actingAs($user)
            ->get(route('podcasts.dashboard'))
            ->assertOk()
            ->assertViewHas('hasPendingScratch', false);
    }
}