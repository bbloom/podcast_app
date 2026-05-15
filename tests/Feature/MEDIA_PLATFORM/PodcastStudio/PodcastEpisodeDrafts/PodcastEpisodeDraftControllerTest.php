<?php

// =============================================================================
// PodcastEpisodeDraftControllerTest
//
// Feature tests for the podcast episode drafts CRUD and Create Draft wizard.
//
// Path: tests/Feature/MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/
// =============================================================================

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PodcastEpisodeDrafts;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Models\PodcastLink;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Models\PodcastEpisodeDraft;
use Tests\TestCase;

class PodcastEpisodeDraftControllerTest extends TestCase
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

    /**
     * Helper: create a draft for the test user.
     */
    private function createDraft(array $overrides = []): PodcastEpisodeDraft
    {
        return PodcastEpisodeDraft::factory()->forUser($this->user)->create(
            array_merge(['podcast_show_id' => $this->show->id], $overrides)
        );
    }

    /**
     * Helper: valid draft payload.
     */
    private function draftPayload(array $overrides = []): array
    {
        return array_merge([
            'podcast_show_id' => $this->show->id,
            'title'           => 'Test Draft Title',
            'date'            => '2026-06-01',
            'episode_number'  => 5,
            'draft'           => '# My Draft\n\nSome content here.',
            'website_content' => 'This episode covers testing.',
            'website_excerpt' => 'A test episode.',
            'guest_notes'     => null,
            'comments'        => null,
            'basecamp_url'    => null,
        ], $overrides);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function test_index_renders_successfully(): void
    {
        $this->createDraft(['title' => 'Visible Draft']);

        $this->get(route('podcast_episode_drafts.index'))
            ->assertOk()
            ->assertSee('Visible Draft');
    }

    public function test_index_shows_only_authenticated_users_drafts(): void
    {
        $otherUser = User::factory()->create();
        $otherShow = PodcastShow::factory()->create(['user_id' => $otherUser->id]);

        PodcastEpisodeDraft::factory()->forUser($otherUser)->create([
            'podcast_show_id' => $otherShow->id,
            'title'           => 'Their Draft',
        ]);

        $this->createDraft(['title' => 'My Draft']);

        $this->get(route('podcast_episode_drafts.index'))
            ->assertOk()
            ->assertSee('My Draft')
            ->assertDontSee('Their Draft');
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        auth()->logout();

        $this->get(route('podcast_episode_drafts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_supports_sorting_by_title(): void
    {
        $this->createDraft(['title' => 'AAA Draft']);
        $this->createDraft(['title' => 'ZZZ Draft']);

        $this->get(route('podcast_episode_drafts.index', ['sort' => 'title', 'dir' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['AAA Draft', 'ZZZ Draft']);
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function test_show_renders_for_owner(): void
    {
        $draft = $this->createDraft(['title' => 'My Visible Draft']);

        $this->get(route('podcast_episode_drafts.show', $draft))
            ->assertOk()
            ->assertSee('My Visible Draft');
    }

    public function test_show_displays_website_content_section(): void
    {
        $draft = $this->createDraft([
            'website_content' => 'Episode about Laravel testing.',
            'website_excerpt' => 'Laravel testing episode.',
        ]);

        $this->get(route('podcast_episode_drafts.show', $draft))
            ->assertOk()
            ->assertSee('Laravel testing episode.');
    }

    public function test_show_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $otherShow = PodcastShow::factory()->create(['user_id' => $otherUser->id]);
        $draft = PodcastEpisodeDraft::factory()->forUser($otherUser)->create([
            'podcast_show_id' => $otherShow->id,
        ]);

        $this->get(route('podcast_episode_drafts.show', $draft))
            ->assertForbidden();
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    public function test_edit_renders_for_owner(): void
    {
        $draft = $this->createDraft();

        $this->get(route('podcast_episode_drafts.edit', $draft))
            ->assertOk()
            ->assertSee('Edit Podcast Episode Draft');
    }

    public function test_edit_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $otherShow = PodcastShow::factory()->create(['user_id' => $otherUser->id]);
        $draft = PodcastEpisodeDraft::factory()->forUser($otherUser)->create([
            'podcast_show_id' => $otherShow->id,
        ]);

        $this->get(route('podcast_episode_drafts.edit', $draft))
            ->assertForbidden();
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function test_update_persists_changes(): void
    {
        $draft = $this->createDraft(['title' => 'Original Title']);

        $this->put(
            route('podcast_episode_drafts.update', $draft),
            $this->draftPayload(['title' => 'Updated Title'])
        )->assertRedirect(route('podcast_episode_drafts.show', $draft));

        $this->assertDatabaseHas('podcast_episode_drafts', [
            'id'    => $draft->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_update_persists_website_fields(): void
    {
        $draft = $this->createDraft();

        $this->put(
            route('podcast_episode_drafts.update', $draft),
            $this->draftPayload([
                'website_content' => 'Updated website content.',
                'website_excerpt' => 'Updated excerpt.',
            ])
        )->assertRedirect(route('podcast_episode_drafts.show', $draft));

        $this->assertDatabaseHas('podcast_episode_drafts', [
            'id'              => $draft->id,
            'website_content' => 'Updated website content.',
            'website_excerpt' => 'Updated excerpt.',
        ]);
    }

    public function test_update_validates_required_title(): void
    {
        $draft = $this->createDraft();

        $this->put(
            route('podcast_episode_drafts.update', $draft),
            $this->draftPayload(['title' => ''])
        )->assertSessionHasErrors('title');
    }

    public function test_update_validates_website_content_max_length(): void
    {
        $draft = $this->createDraft();

        $this->put(
            route('podcast_episode_drafts.update', $draft),
            $this->draftPayload(['website_content' => str_repeat('x', 10001)])
        )->assertSessionHasErrors('website_content');
    }

    public function test_update_validates_website_excerpt_max_length(): void
    {
        $draft = $this->createDraft();

        $this->put(
            route('podcast_episode_drafts.update', $draft),
            $this->draftPayload(['website_excerpt' => str_repeat('x', 256)])
        )->assertSessionHasErrors('website_excerpt');
    }

    public function test_update_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $otherShow = PodcastShow::factory()->create(['user_id' => $otherUser->id]);
        $draft = PodcastEpisodeDraft::factory()->forUser($otherUser)->create([
            'podcast_show_id' => $otherShow->id,
        ]);

        $this->put(
            route('podcast_episode_drafts.update', $draft),
            $this->draftPayload()
        )->assertForbidden();
    }

    public function test_update_prevents_assigning_to_another_users_show(): void
    {
        $otherUser = User::factory()->create();
        $otherShow = PodcastShow::factory()->create(['user_id' => $otherUser->id]);
        $draft = $this->createDraft();

        $this->put(
            route('podcast_episode_drafts.update', $draft),
            $this->draftPayload(['podcast_show_id' => $otherShow->id])
        )->assertForbidden();
    }

    // =========================================================================
    // DELETE CONFIRM
    // =========================================================================

    public function test_delete_confirm_renders_for_owner(): void
    {
        $draft = $this->createDraft(['title' => 'Draft To Delete']);

        $this->get(route('podcast_episode_drafts.delete.confirm', $draft))
            ->assertOk()
            ->assertSee('Draft To Delete');
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function test_destroy_deletes_draft(): void
    {
        $draft = $this->createDraft();

        $this->delete(route('podcast_episode_drafts.destroy', $draft))
            ->assertRedirect(route('podcast_episode_drafts.index'));

        $this->assertDatabaseMissing('podcast_episode_drafts', ['id' => $draft->id]);
    }

    public function test_destroy_detaches_links_but_does_not_delete_them(): void
    {
        $draft = $this->createDraft();
        $link  = PodcastLink::factory()->create();
        $draft->links()->attach($link->id);

        $this->delete(route('podcast_episode_drafts.destroy', $draft))
            ->assertRedirect(route('podcast_episode_drafts.index'));

        $this->assertDatabaseMissing('podcast_episode_drafts', ['id' => $draft->id]);
        $this->assertDatabaseMissing('podcast_link_episode_draft', [
            'podcast_episode_draft_id' => $draft->id,
        ]);
        $this->assertDatabaseHas('podcast_links', ['id' => $link->id]);
    }

    public function test_destroy_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $otherShow = PodcastShow::factory()->create(['user_id' => $otherUser->id]);
        $draft = PodcastEpisodeDraft::factory()->forUser($otherUser)->create([
            'podcast_show_id' => $otherShow->id,
        ]);

        $this->delete(route('podcast_episode_drafts.destroy', $draft))
            ->assertForbidden();
    }

    // =========================================================================
    // CREATE DRAFT WIZARD — STEP 1
    // =========================================================================

    public function test_wizard_step1_renders(): void
    {
        $this->get(route('podcast_episode_drafts.create.step1'))
            ->assertOk()
            ->assertSee('Create a Podcast Episode Draft');
    }

    public function test_wizard_step1_stores_show_in_session(): void
    {
        $this->post(route('podcast_episode_drafts.create.step1.store'), [
            'podcast_show_id' => $this->show->id,
        ])->assertRedirect(route('podcast_episode_drafts.create.step2'));

        $this->assertEquals($this->show->id, session('wizard.create_draft.podcast_show_id'));
    }

    public function test_wizard_step1_validates_show_exists(): void
    {
        $this->post(route('podcast_episode_drafts.create.step1.store'), [
            'podcast_show_id' => 99999,
        ])->assertSessionHasErrors('podcast_show_id');
    }

    public function test_wizard_step1_rejects_another_users_show(): void
    {
        $otherUser = User::factory()->create();
        $otherShow = PodcastShow::factory()->create(['user_id' => $otherUser->id]);

        $this->post(route('podcast_episode_drafts.create.step1.store'), [
            'podcast_show_id' => $otherShow->id,
        ])->assertRedirect(route('podcast_episode_drafts.create.step1'))
          ->assertSessionHas('error');
    }

    // =========================================================================
    // CREATE DRAFT WIZARD — STEP 2
    // =========================================================================

    public function test_wizard_step2_redirects_without_session(): void
    {
        $this->get(route('podcast_episode_drafts.create.step2'))
            ->assertRedirect(route('podcast_episode_drafts.create.step1'));
    }

    public function test_wizard_step2_renders_with_session(): void
    {
        session(['wizard.create_draft.podcast_show_id' => $this->show->id]);

        $this->get(route('podcast_episode_drafts.create.step2'))
            ->assertOk()
            ->assertSee($this->show->title);
    }

    public function test_wizard_step2_store_creates_draft(): void
    {
        session(['wizard.create_draft.podcast_show_id' => $this->show->id]);

        $this->post(route('podcast_episode_drafts.create.step2.store'), [
            'podcast_show_id' => $this->show->id,
            'title'           => 'My New Draft',
            'episode_number'  => 10,
            'date'            => '2026-07-01',
            'draft'           => '# Hello World',
            'website_content' => 'About this episode.',
            'website_excerpt' => 'Short excerpt.',
            'guest_notes'     => 'Jane Doe',
            'comments'        => 'First recording attempt.',
            'basecamp_url'    => 'https://3.basecamp.com/12345',
        ])->assertRedirect();

        $this->assertDatabaseHas('podcast_episode_drafts', [
            'podcast_show_id' => $this->show->id,
            'user_id'         => $this->user->id,
            'title'           => 'My New Draft',
            'episode_number'  => 10,
            'website_content' => 'About this episode.',
            'website_excerpt' => 'Short excerpt.',
            'guest_notes'     => 'Jane Doe',
        ]);
    }

    public function test_wizard_step2_store_clears_session(): void
    {
        session(['wizard.create_draft.podcast_show_id' => $this->show->id]);

        $this->post(route('podcast_episode_drafts.create.step2.store'), [
            'podcast_show_id' => $this->show->id,
            'title'           => 'Session Clear Test',
        ]);

        $this->assertNull(session('wizard.create_draft'));
    }

    public function test_wizard_step2_store_redirects_without_session(): void
    {
        $this->post(route('podcast_episode_drafts.create.step2.store'), [
            'podcast_show_id' => $this->show->id,
            'title'           => 'Should Not Create',
        ])->assertRedirect(route('podcast_episode_drafts.create.step1'));

        $this->assertDatabaseMissing('podcast_episode_drafts', [
            'title' => 'Should Not Create',
        ]);
    }

    public function test_wizard_step2_store_validates_required_title(): void
    {
        session(['wizard.create_draft.podcast_show_id' => $this->show->id]);

        $this->post(route('podcast_episode_drafts.create.step2.store'), [
            'podcast_show_id' => $this->show->id,
            'title'           => '',
        ])->assertSessionHasErrors('title');
    }

    public function test_wizard_step2_store_validates_basecamp_url_format(): void
    {
        session(['wizard.create_draft.podcast_show_id' => $this->show->id]);

        $this->post(route('podcast_episode_drafts.create.step2.store'), [
            'podcast_show_id' => $this->show->id,
            'title'           => 'URL Validation Test',
            'basecamp_url'    => 'not-a-url',
        ])->assertSessionHasErrors('basecamp_url');
    }
}