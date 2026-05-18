<?php

// =============================================================================
// DraftPreProductionWizardTest
//
// Feature tests for the Draft Pre-Production wizard.
//
// Path: tests/Feature/MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/PreProduction/
// =============================================================================

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PodcastEpisodeDrafts\PreProduction;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Enums\PodcastEpisodeDraftStatus;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Models\PodcastEpisodeDraft;
use Tests\TestCase;

class DraftPreProductionWizardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private PodcastShow $show;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->show = PodcastShow::factory()->create([
            'user_id' => $this->user->id,
            'title'   => 'The Bob Bloom Show',
        ]);
        $this->actingAs($this->user);
    }

    private function createDraft(array $overrides = []): PodcastEpisodeDraft
    {
        return PodcastEpisodeDraft::factory()->forUser($this->user)->create(
            array_merge(['podcast_show_id' => $this->show->id], $overrides)
        );
    }

    private function sessionWithDraft(PodcastEpisodeDraft $draft): void
    {
        session(['wizard.draft_pre_production.draft_id' => $draft->id]);
    }

    // =========================================================================
    // STEP 1 — Select draft
    // =========================================================================

    public function test_step1_renders(): void
    {
        $this->get(route('draft_pre_production.step1'))
            ->assertOk()
            ->assertSee('Draft Pre-Production');
    }

    public function test_step1_shows_only_drafting_status_drafts(): void
    {
        $drafting = $this->createDraft(['title' => 'Still Drafting']);
        $complete = $this->createDraft([
            'title'  => 'Already Complete',
            'status' => PodcastEpisodeDraftStatus::ready_to_create_production_episode,
        ]);

        $this->get(route('draft_pre_production.step1'))
            ->assertOk()
            ->assertSee('Still Drafting')
            ->assertDontSee('Already Complete');
    }

    public function test_step1_stores_draft_in_session(): void
    {
        $draft = $this->createDraft();

        $this->post(route('draft_pre_production.step1.store'), [
            'podcast_episode_draft_id' => $draft->id,
        ])->assertRedirect(route('draft_pre_production.step2'));

        $this->assertEquals($draft->id, session('wizard.draft_pre_production.draft_id'));
    }

    public function test_step1_validates_draft_exists(): void
    {
        $this->post(route('draft_pre_production.step1.store'), [
            'podcast_episode_draft_id' => 99999,
        ])->assertSessionHasErrors('podcast_episode_draft_id');
    }

    public function test_step1_rejects_another_users_draft(): void
    {
        $otherUser = User::factory()->create();
        $otherShow = PodcastShow::factory()->create(['user_id' => $otherUser->id]);
        $otherDraft = PodcastEpisodeDraft::factory()->forUser($otherUser)->create([
            'podcast_show_id' => $otherShow->id,
        ]);

        $this->post(route('draft_pre_production.step1.store'), [
            'podcast_episode_draft_id' => $otherDraft->id,
        ])->assertRedirect(route('draft_pre_production.step1'))
          ->assertSessionHas('error');
    }

    public function test_step1_redirects_unauthenticated_users(): void
    {
        auth()->logout();

        $this->get(route('draft_pre_production.step1'))
            ->assertRedirect(route('login'));
    }

    // =========================================================================
    // STEP 2 — Title, episode number, date
    // =========================================================================

    public function test_step2_renders_with_session(): void
    {
        $draft = $this->createDraft();
        $this->sessionWithDraft($draft);

        $this->get(route('draft_pre_production.step2'))
            ->assertOk()
            ->assertSee('Step 2 of 4');
    }

    public function test_step2_redirects_without_session(): void
    {
        $this->get(route('draft_pre_production.step2'))
            ->assertRedirect(route('draft_pre_production.step1'));
    }

    public function test_step2_persists_title_episode_number_date(): void
    {
        $draft = $this->createDraft();
        $this->sessionWithDraft($draft);

        $this->post(route('draft_pre_production.step2.store'), [
            'title'          => 'Finalized Title',
            'episode_number' => 42,
            'date'           => '2026-07-15',
        ])->assertRedirect(route('draft_pre_production.step3'));

        $this->assertDatabaseHas('podcast_episode_drafts', [
            'id'             => $draft->id,
            'title'          => 'Finalized Title',
            'episode_number' => 42,
        ]);
    }

    public function test_step2_validates_required_fields(): void
    {
        $draft = $this->createDraft();
        $this->sessionWithDraft($draft);

        $this->post(route('draft_pre_production.step2.store'), [
            'title'          => '',
            'episode_number' => '',
            'date'           => '',
        ])->assertSessionHasErrors(['title', 'episode_number', 'date']);
    }

    public function test_step2_validates_episode_number_is_positive(): void
    {
        $draft = $this->createDraft();
        $this->sessionWithDraft($draft);

        $this->post(route('draft_pre_production.step2.store'), [
            'title'          => 'Test',
            'episode_number' => 0,
            'date'           => '2026-07-15',
        ])->assertSessionHasErrors('episode_number');
    }

    // =========================================================================
    // STEP 3 — Draft/script
    // =========================================================================

    public function test_step3_renders_with_session(): void
    {
        $draft = $this->createDraft(['title' => 'My Draft']);
        $this->sessionWithDraft($draft);

        $this->get(route('draft_pre_production.step3'))
            ->assertOk()
            ->assertSee('Step 3 of 4');
    }

    public function test_step3_redirects_without_session(): void
    {
        $this->get(route('draft_pre_production.step3'))
            ->assertRedirect(route('draft_pre_production.step1'));
    }

    public function test_step3_persists_draft_content(): void
    {
        $draft = $this->createDraft();
        $this->sessionWithDraft($draft);

        $this->post(route('draft_pre_production.step3.store'), [
            'draft' => '# Episode Script\n\nHello listeners...',
        ])->assertRedirect(route('draft_pre_production.step4'));

        $this->assertDatabaseHas('podcast_episode_drafts', [
            'id'    => $draft->id,
            'draft' => '# Episode Script\n\nHello listeners...',
        ]);
    }

    public function test_step3_validates_draft_is_required(): void
    {
        $draft = $this->createDraft();
        $this->sessionWithDraft($draft);

        $this->post(route('draft_pre_production.step3.store'), [
            'draft' => '',
        ])->assertSessionHasErrors('draft');
    }

    // =========================================================================
    // STEP 4 — Website content → mark complete
    // =========================================================================

    public function test_step4_renders_with_session(): void
    {
        $draft = $this->createDraft();
        $this->sessionWithDraft($draft);

        $this->get(route('draft_pre_production.step4'))
            ->assertOk()
            ->assertSee('Step 4 of 4');
    }

    public function test_step4_redirects_without_session(): void
    {
        $this->get(route('draft_pre_production.step4'))
            ->assertRedirect(route('draft_pre_production.step1'));
    }

    public function test_step4_persists_website_content_and_marks_complete(): void
    {
        $draft = $this->createDraft();
        $this->sessionWithDraft($draft);

        $this->post(route('draft_pre_production.step4.store'), [
            'website_content' => 'This episode covers Laravel testing.',
            'website_excerpt' => 'Laravel testing.',
        ])->assertRedirect(route('podcast_episode_drafts.show', $draft));

        $this->assertDatabaseHas('podcast_episode_drafts', [
            'id'              => $draft->id,
            'website_content' => 'This episode covers Laravel testing.',
            'website_excerpt' => 'Laravel testing.',
            'status'          => PodcastEpisodeDraftStatus::ready_to_create_production_episode->value,
        ]);
    }

    public function test_step4_clears_session(): void
    {
        $draft = $this->createDraft();
        $this->sessionWithDraft($draft);

        $this->post(route('draft_pre_production.step4.store'), [
            'website_content' => 'Content here.',
        ]);

        $this->assertNull(session('wizard.draft_pre_production'));
    }

    public function test_step4_validates_website_content_is_required(): void
    {
        $draft = $this->createDraft();
        $this->sessionWithDraft($draft);

        $this->post(route('draft_pre_production.step4.store'), [
            'website_content' => '',
        ])->assertSessionHasErrors('website_content');
    }

    public function test_step4_validates_website_content_max_length(): void
    {
        $draft = $this->createDraft();
        $this->sessionWithDraft($draft);

        $this->post(route('draft_pre_production.step4.store'), [
            'website_content' => str_repeat('x', 10001),
        ])->assertSessionHasErrors('website_content');
    }

    public function test_step4_validates_website_excerpt_max_length(): void
    {
        $draft = $this->createDraft();
        $this->sessionWithDraft($draft);

        $this->post(route('draft_pre_production.step4.store'), [
            'website_content' => 'Valid content.',
            'website_excerpt' => str_repeat('x', 256),
        ])->assertSessionHasErrors('website_excerpt');
    }

    // =========================================================================
    // FULL FLOW
    // =========================================================================

    public function test_full_wizard_flow_from_step1_to_completion(): void
    {
        $draft = $this->createDraft([
            'title'          => 'Working Title',
            'episode_number' => 1,
        ]);

        // Step 1 — select draft
        $this->post(route('draft_pre_production.step1.store'), [
            'podcast_episode_draft_id' => $draft->id,
        ])->assertRedirect(route('draft_pre_production.step2'));

        // Step 2 — finalize title, number, date
        $this->post(route('draft_pre_production.step2.store'), [
            'title'          => 'Finalized Production Title',
            'episode_number' => 7,
            'date'           => '2026-08-01',
        ])->assertRedirect(route('draft_pre_production.step3'));

        // Step 3 — finalize script
        $this->post(route('draft_pre_production.step3.store'), [
            'draft' => '# My Episode\n\nThis is the full script.',
        ])->assertRedirect(route('draft_pre_production.step4'));

        // Step 4 — finalize website content
        $this->post(route('draft_pre_production.step4.store'), [
            'website_content' => 'A great episode about testing.',
            'website_excerpt' => 'Testing episode.',
        ])->assertRedirect(route('podcast_episode_drafts.show', $draft));

        // Verify final state
        $draft->refresh();
        $this->assertEquals('Finalized Production Title', $draft->title);
        $this->assertEquals(7, $draft->episode_number);
        $this->assertEquals('A great episode about testing.', $draft->website_content);
        $this->assertEquals(PodcastEpisodeDraftStatus::ready_to_create_production_episode, $draft->status);
        $this->assertNull(session('wizard.draft_pre_production'));
    }
}