<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\CreateEpisodeWizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Step1ControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_renders_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.wizard.create.step1'))
            ->assertOk();
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_episodes_planning.wizard.create.step1'))
            ->assertRedirect(route('login'));
    }
}