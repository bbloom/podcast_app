<?php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\ContentSources\Podcasts;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListSource;
use MediaPlatform\Digest\ContentSources\Podcasts\Models\Podcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PodcastWizardControllerTest extends TestCase
{
    use RefreshDatabase;

    private \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);
    }

    // ── index ──────────────────────────────────────────────────────────────────

    public function test_index_renders_successfully(): void
    {
        Podcast::factory()->forUser($this->user)->count(2)->create();

        $this->get(route('digest-podcasts.index'))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.podcasts.index')
            ->assertViewHas('podcasts');
    }

    // ── step 1: enter RSS URL ──────────────────────────────────────────────────

    public function test_step1_renders_form(): void
    {
        $this->get(route('digest-podcasts.create.step1'))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.podcasts.wizard-step1');
    }

    public function test_step1_submit_validates_required_url(): void
    {
        $this->post(route('digest-podcasts.create.step1.submit'), [])
            ->assertSessionHasErrors('rss_url');
    }

    public function test_step1_submit_validates_url_format(): void
    {
        $this->post(route('digest-podcasts.create.step1.submit'), ['rss_url' => 'not-a-url'])
            ->assertSessionHasErrors('rss_url');
    }

    public function test_step1_submit_rejects_duplicate_feed_url(): void
    {
        Podcast::factory()->forUser($this->user)->create([
            'rss_url' => 'https://example.com/podcast.xml',
        ]);

        $this->post(route('digest-podcasts.create.step1.submit'), [
            'rss_url' => 'https://example.com/podcast.xml',
        ])->assertSessionHasErrors('rss_url');
    }

    public function test_step1_submit_allows_same_url_for_different_user(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        Podcast::factory()->forUser($otherUser)->create([
            'rss_url' => 'https://example.com/podcast.xml',
        ]);

        Http::fake(['example.com/*' => Http::response($this->podcastXml())]);

        $this->post(route('digest-podcasts.create.step1.submit'), [
            'rss_url' => 'https://example.com/podcast.xml',
        ])->assertRedirect(route('digest-podcasts.create.step2'));
    }

    public function test_step1_submit_fetches_feed_and_stores_in_session(): void
    {
        Http::fake(['example.com/*' => Http::response($this->podcastXml())]);

        $this->post(route('digest-podcasts.create.step1.submit'), [
            'rss_url' => 'https://example.com/podcast.xml',
        ])->assertRedirect(route('digest-podcasts.create.step2'))
          ->assertSessionHas('podcast_wizard.rss_url')
          ->assertSessionHas('podcast_wizard.feed_data');
    }

    public function test_step1_submit_returns_error_when_feed_unreachable(): void
    {
        Http::fake(['example.com/*' => Http::response('', 500)]);

        $this->post(route('digest-podcasts.create.step1.submit'), [
            'rss_url' => 'https://example.com/podcast.xml',
        ])->assertSessionHasErrors('rss_url');
    }

    public function test_step1_submit_returns_error_for_invalid_xml(): void
    {
        Http::fake(['example.com/*' => Http::response('not xml at all')]);

        $this->post(route('digest-podcasts.create.step1.submit'), [
            'rss_url' => 'https://example.com/podcast.xml',
        ])->assertSessionHasErrors('rss_url');
    }

    // ── step 2: confirm feed details ───────────────────────────────────────────

    public function test_step2_redirects_to_step1_without_session(): void
    {
        $this->get(route('digest-podcasts.create.step2'))
            ->assertRedirect(route('digest-podcasts.create.step1'));
    }

    public function test_step2_renders_with_valid_session(): void
    {
        $this->withSession([
            'podcast_wizard.rss_url'   => 'https://example.com/podcast.xml',
            'podcast_wizard.feed_data' => $this->feedData(),
        ])->get(route('digest-podcasts.create.step2'))
          ->assertOk()
          ->assertViewIs('media_platform.digest.content_sources.podcasts.wizard-step2')
          ->assertViewHas('podcast');
    }

    public function test_step2_submit_sets_confirmed_flag(): void
    {
        $this->withSession([
            'podcast_wizard.rss_url'   => 'https://example.com/podcast.xml',
            'podcast_wizard.feed_data' => $this->feedData(),
        ])->post(route('digest-podcasts.create.step2.submit'))
          ->assertRedirect(route('digest-podcasts.create.step3'))
          ->assertSessionHas('podcast_wizard.confirmed', true);
    }

    public function test_step2_submit_redirects_to_step1_without_session(): void
    {
        $this->post(route('digest-podcasts.create.step2.submit'))
            ->assertRedirect(route('digest-podcasts.create.step1'));
    }

    // ── step 3: assign to lists ────────────────────────────────────────────────

    public function test_step3_redirects_to_step1_without_confirmation(): void
    {
        $this->get(route('digest-podcasts.create.step3'))
            ->assertRedirect(route('digest-podcasts.create.step1'));
    }

    public function test_step3_renders_with_valid_session(): void
    {
        ListModel::factory()->forUser($this->user)->count(2)->create();

        $this->withSession([
            'podcast_wizard.rss_url'   => 'https://example.com/podcast.xml',
            'podcast_wizard.feed_data' => $this->feedData(),
            'podcast_wizard.confirmed' => true,
        ])->get(route('digest-podcasts.create.step3'))
          ->assertOk()
          ->assertViewIs('media_platform.digest.content_sources.podcasts.wizard-step3')
          ->assertViewHas('lists')
          ->assertViewHas('podcastTitle', 'My Podcast');
    }

    public function test_step3_submit_validates_list_ids_required(): void
    {
        $this->withSession([
            'podcast_wizard.rss_url'   => 'https://example.com/podcast.xml',
            'podcast_wizard.feed_data' => $this->feedData(),
            'podcast_wizard.confirmed' => true,
        ])->post(route('digest-podcasts.create.step3.submit'), [])
          ->assertSessionHasErrors('list_ids');
    }

    public function test_step3_submit_rejects_lists_owned_by_other_user(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $otherList = ListModel::factory()->forUser($otherUser)->create();

        $this->withSession([
            'podcast_wizard.rss_url'   => 'https://example.com/podcast.xml',
            'podcast_wizard.feed_data' => $this->feedData(),
            'podcast_wizard.confirmed' => true,
        ])->post(route('digest-podcasts.create.step3.submit'), [
            'list_ids' => [$otherList->id],
        ])->assertSessionHasErrors('list_ids');
    }

    public function test_step3_submit_persists_podcast_and_list_sources(): void
    {
        $lists = ListModel::factory()->forUser($this->user)->count(2)->create();

        $this->withSession([
            'podcast_wizard.rss_url'   => 'https://example.com/podcast.xml',
            'podcast_wizard.feed_data' => $this->feedData(),
            'podcast_wizard.confirmed' => true,
        ])->post(route('digest-podcasts.create.step3.submit'), [
            'list_ids' => $lists->pluck('id')->toArray(),
        ])->assertRedirect(route('digest-podcasts.create.step4'));

        $this->assertDatabaseHas('podcasts', [
            'user_id' => $this->user->id,
            'rss_url' => 'https://example.com/podcast.xml',
            'title'   => 'My Podcast',
        ]);

        $podcast = Podcast::where('user_id', $this->user->id)->firstOrFail();

        $this->assertCount(2, $podcast->listSources);

        foreach ($lists as $list) {
            $this->assertDatabaseHas('list_sources', [
                'list_id'         => $list->id,
                'sourceable_id'   => $podcast->id,
                'sourceable_type' => 'podcast',
                'enabled'         => true,
                'suspended'       => false,
            ]);
        }
    }

    public function test_step3_submit_clears_wizard_session(): void
    {
        $list = ListModel::factory()->forUser($this->user)->create();

        $response = $this->withSession([
            'podcast_wizard.rss_url'   => 'https://example.com/podcast.xml',
            'podcast_wizard.feed_data' => $this->feedData(),
            'podcast_wizard.confirmed' => true,
        ])->post(route('digest-podcasts.create.step3.submit'), [
            'list_ids' => [$list->id],
        ]);

        $response->assertSessionMissing('podcast_wizard.rss_url');
        $response->assertSessionMissing('podcast_wizard.feed_data');
        $response->assertSessionMissing('podcast_wizard.confirmed');
        $response->assertSessionHas('podcast_wizard.saved_title');
        $response->assertSessionHas('podcast_wizard.saved_list_count');
    }

    // ── step 4: done ───────────────────────────────────────────────────────────

    public function test_step4_renders_with_saved_data(): void
    {
        $this->withSession([
            'podcast_wizard.saved_title'      => 'My Podcast',
            'podcast_wizard.saved_list_count' => 2,
        ])->get(route('digest-podcasts.create.step4'))
          ->assertOk()
          ->assertViewIs('media_platform.digest.content_sources.podcasts.wizard-step4')
          ->assertViewHas('title', 'My Podcast')
          ->assertViewHas('listCount', 2);
    }

    public function test_step4_uses_defaults_when_session_empty(): void
    {
        $this->get(route('digest-podcasts.create.step4'))
            ->assertOk()
            ->assertViewHas('title', 'Podcast')
            ->assertViewHas('listCount', 0);
    }

    // ── edit ───────────────────────────────────────────────────────────────────

    public function test_edit_renders_for_owner(): void
    {
        $podcast = Podcast::factory()->forUser($this->user)->create();

        $this->get(route('digest-podcasts.edit', $podcast))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.podcasts.edit');
    }

    public function test_edit_returns_403_for_non_owner(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $podcast   = Podcast::factory()->forUser($otherUser)->create();

        $this->get(route('digest-podcasts.edit', $podcast))
            ->assertForbidden();
    }

    // ── show ───────────────────────────────────────────────────────────────────

    public function test_show_renders_for_owner(): void
    {
        $podcast = Podcast::factory()->forUser($this->user)->create();

        $this->get(route('digest-podcasts.show', $podcast))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.podcasts.show')
            ->assertViewHas('podcast')
            ->assertViewHas('listSources')
            ->assertViewHas('tracking')
            ->assertViewHas('availableLists');
    }

    public function test_show_available_lists_excludes_already_attached(): void
    {
        $podcast  = Podcast::factory()->forUser($this->user)->create();
        $attached = ListModel::factory()->forUser($this->user)->create();
        $free     = ListModel::factory()->forUser($this->user)->create();

        $this->createListSource($podcast->id, 'podcast', $attached->id);

        $response       = $this->get(route('digest-podcasts.show', $podcast));
        $availableLists = $response->viewData('availableLists');

        $this->assertFalse($availableLists->contains('id', $attached->id));
        $this->assertTrue($availableLists->contains('id', $free->id));
    }

    public function test_show_returns_403_for_non_owner(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $podcast   = Podcast::factory()->forUser($otherUser)->create();

        $this->get(route('digest-podcasts.show', $podcast))
            ->assertForbidden();
    }

    public function test_show_returns_404_for_missing_record(): void
    {
        $this->get(route('digest-podcasts.show', 99999))
            ->assertNotFound();
    }

    // ── update ─────────────────────────────────────────────────────────────────

    public function test_update_saves_enabled_status(): void
    {
        $podcast = Podcast::factory()->forUser($this->user)->create(['enabled' => true]);

        $this->put(route('digest-podcasts.update', $podcast), ['enabled' => '0'])
            ->assertRedirect(route('digest-podcasts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('podcasts', ['id' => $podcast->id, 'enabled' => false]);
    }

    public function test_update_returns_403_for_non_owner(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $podcast   = Podcast::factory()->forUser($otherUser)->create();

        $this->put(route('digest-podcasts.update', $podcast), ['enabled' => '1'])
            ->assertForbidden();
    }

    // ── delete ─────────────────────────────────────────────────────────────────

    public function test_confirm_delete_renders_for_owner(): void
    {
        $podcast = Podcast::factory()->forUser($this->user)->create();

        $this->get(route('digest-podcasts.delete.confirm', $podcast))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.podcasts.delete-confirm');
    }

    public function test_destroy_deletes_podcast_and_redirects(): void
    {
        $podcast = Podcast::factory()->forUser($this->user)->create();

        $this->delete(route('digest-podcasts.destroy', $podcast))
            ->assertRedirect(route('digest-podcasts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('podcasts', ['id' => $podcast->id]);
    }

    public function test_destroy_returns_403_for_non_owner(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $podcast   = Podcast::factory()->forUser($otherUser)->create();

        $this->delete(route('digest-podcasts.destroy', $podcast))
            ->assertForbidden();
    }

    // ── attachList ─────────────────────────────────────────────────────────────

    public function test_attach_list_creates_list_source_row(): void
    {
        $podcast = Podcast::factory()->forUser($this->user)->create();
        $list    = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('digest-podcasts.list_sources.attach', $podcast), [
            'list_id'         => $list->id,
            'processing_mode' => 'description',
        ])->assertRedirect(route('digest-podcasts.show', $podcast))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('list_sources', [
            'list_id'         => $list->id,
            'sourceable_id'   => $podcast->id,
            'sourceable_type' => 'podcast',
            'processing_mode' => 'description',
            'enabled'         => true,
        ]);
    }

    public function test_attach_list_stores_search_terms_in_search_mode(): void
    {
        $podcast = Podcast::factory()->forUser($this->user)->create();
        $list    = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('digest-podcasts.list_sources.attach', $podcast), [
            'list_id'         => $list->id,
            'processing_mode' => 'search',
            'search_terms'    => 'AI, robotics',
        ]);

        $this->assertDatabaseHas('list_sources', [
            'sourceable_id'   => $podcast->id,
            'sourceable_type' => 'podcast',
            'processing_mode' => 'search',
            'search_terms'    => 'AI, robotics',
        ]);
    }

    public function test_attach_list_nulls_search_terms_when_not_in_search_mode(): void
    {
        $podcast = Podcast::factory()->forUser($this->user)->create();
        $list    = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('digest-podcasts.list_sources.attach', $podcast), [
            'list_id'         => $list->id,
            'processing_mode' => 'summary',
            'search_terms'    => 'should be ignored',
        ]);

        $this->assertDatabaseHas('list_sources', [
            'sourceable_id'   => $podcast->id,
            'processing_mode' => 'summary',
            'search_terms'    => null,
        ]);
    }

    public function test_attach_list_rejects_duplicate(): void
    {
        $podcast = Podcast::factory()->forUser($this->user)->create();
        $list    = ListModel::factory()->forUser($this->user)->create();

        $this->createListSource($podcast->id, 'podcast', $list->id);

        $this->post(route('digest-podcasts.list_sources.attach', $podcast), [
            'list_id'         => $list->id,
            'processing_mode' => 'description',
        ])->assertSessionHasErrors('list_id');
    }

    public function test_attach_list_rejects_list_owned_by_other_user(): void
    {
        $podcast   = Podcast::factory()->forUser($this->user)->create();
        $otherUser = \App\Models\User::factory()->create();
        $otherList = ListModel::factory()->forUser($otherUser)->create();

        $this->post(route('digest-podcasts.list_sources.attach', $podcast), [
            'list_id'         => $otherList->id,
            'processing_mode' => 'description',
        ])->assertSessionHasErrors('list_id');
    }

    public function test_attach_list_returns_403_for_non_owner_of_podcast(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $podcast   = Podcast::factory()->forUser($otherUser)->create();
        $list      = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('digest-podcasts.list_sources.attach', $podcast), [
            'list_id'         => $list->id,
            'processing_mode' => 'description',
        ])->assertForbidden();
    }

    public function test_attach_list_validates_required_fields(): void
    {
        $podcast = Podcast::factory()->forUser($this->user)->create();

        $this->post(route('digest-podcasts.list_sources.attach', $podcast), [])
            ->assertSessionHasErrors(['list_id', 'processing_mode']);
    }

    public function test_attach_list_validates_processing_mode_values(): void
    {
        $podcast = Podcast::factory()->forUser($this->user)->create();
        $list    = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('digest-podcasts.list_sources.attach', $podcast), [
            'list_id'         => $list->id,
            'processing_mode' => 'invalid_mode',
        ])->assertSessionHasErrors('processing_mode');
    }

    // ── updateListSource ───────────────────────────────────────────────────────

    public function test_update_list_source_saves_new_mode(): void
    {
        $podcast    = Podcast::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($podcast->id, 'podcast', $list->id, 'description');

        $this->patch(route('digest-podcasts.list_sources.update', [$podcast, $listSource]), [
            'processing_mode' => 'summary',
        ])->assertRedirect(route('digest-podcasts.show', $podcast))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('list_sources', [
            'id'              => $listSource->id,
            'processing_mode' => 'summary',
            'search_terms'    => null,
        ]);
    }

    public function test_update_list_source_saves_search_terms_in_search_mode(): void
    {
        $podcast    = Podcast::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($podcast->id, 'podcast', $list->id, 'description');

        $this->patch(route('digest-podcasts.list_sources.update', [$podcast, $listSource]), [
            'processing_mode' => 'search',
            'search_terms'    => 'machine learning',
        ]);

        $this->assertDatabaseHas('list_sources', [
            'id'              => $listSource->id,
            'processing_mode' => 'search',
            'search_terms'    => 'machine learning',
        ]);
    }

    public function test_update_list_source_nulls_search_terms_when_leaving_search_mode(): void
    {
        $podcast    = Podcast::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($podcast->id, 'podcast', $list->id, 'search', 'AI');

        $this->patch(route('digest-podcasts.list_sources.update', [$podcast, $listSource]), [
            'processing_mode' => 'description',
        ]);

        $this->assertDatabaseHas('list_sources', [
            'id'              => $listSource->id,
            'processing_mode' => 'description',
            'search_terms'    => null,
        ]);
    }

    public function test_update_list_source_returns_403_for_non_owner_of_podcast(): void
    {
        $otherUser  = \App\Models\User::factory()->create();
        $podcast    = Podcast::factory()->forUser($otherUser)->create();
        $list       = ListModel::factory()->forUser($otherUser)->create();
        $listSource = $this->createListSource($podcast->id, 'podcast', $list->id);

        $this->patch(route('digest-podcasts.list_sources.update', [$podcast, $listSource]), [
            'processing_mode' => 'summary',
        ])->assertForbidden();
    }

    public function test_update_list_source_returns_403_when_list_source_belongs_to_different_podcast(): void
    {
        $podcast      = Podcast::factory()->forUser($this->user)->create();
        $otherPodcast = Podcast::factory()->forUser($this->user)->create();
        $list         = ListModel::factory()->forUser($this->user)->create();
        $listSource   = $this->createListSource($otherPodcast->id, 'podcast', $list->id);

        $this->patch(route('digest-podcasts.list_sources.update', [$podcast, $listSource]), [
            'processing_mode' => 'summary',
        ])->assertForbidden();
    }

    // ── detachConfirm ──────────────────────────────────────────────────────────

    public function test_detach_confirm_renders_for_owner(): void
    {
        $podcast    = Podcast::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($podcast->id, 'podcast', $list->id);

        $this->get(route('digest-podcasts.list_sources.detach.confirm', [$podcast, $listSource]))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.podcasts.detach-confirm')
            ->assertViewHas('source')
            ->assertViewHas('listSource');
    }

    public function test_detach_confirm_returns_403_for_non_owner_of_podcast(): void
    {
        $otherUser  = \App\Models\User::factory()->create();
        $podcast    = Podcast::factory()->forUser($otherUser)->create();
        $list       = ListModel::factory()->forUser($otherUser)->create();
        $listSource = $this->createListSource($podcast->id, 'podcast', $list->id);

        $this->get(route('digest-podcasts.list_sources.detach.confirm', [$podcast, $listSource]))
            ->assertForbidden();
    }

    public function test_detach_confirm_returns_403_when_list_source_belongs_to_different_podcast(): void
    {
        $podcast      = Podcast::factory()->forUser($this->user)->create();
        $otherPodcast = Podcast::factory()->forUser($this->user)->create();
        $list         = ListModel::factory()->forUser($this->user)->create();
        $listSource   = $this->createListSource($otherPodcast->id, 'podcast', $list->id);

        $this->get(route('digest-podcasts.list_sources.detach.confirm', [$podcast, $listSource]))
            ->assertForbidden();
    }

    // ── detach ─────────────────────────────────────────────────────────────────

    public function test_detach_deletes_list_source_and_redirects(): void
    {
        $podcast    = Podcast::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($podcast->id, 'podcast', $list->id);

        $this->delete(route('digest-podcasts.list_sources.detach', [$podcast, $listSource]))
            ->assertRedirect(route('digest-podcasts.show', $podcast))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('list_sources', ['id' => $listSource->id]);
    }

    public function test_detach_cascades_to_summaries(): void
    {
        $podcast    = Podcast::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($podcast->id, 'podcast', $list->id);

        DB::table('summaries')->insert([
            'user_id'            => $this->user->id,
            'list_source_id'     => $listSource->id,
            'source_url'         => 'https://example.com/ep1',
            'processing_mode'    => 'description',
            'is_relevant'        => true,
            'included_in_digest' => false,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->assertDatabaseHas('summaries', ['list_source_id' => $listSource->id]);

        $this->delete(route('digest-podcasts.list_sources.detach', [$podcast, $listSource]));

        $this->assertDatabaseMissing('summaries', ['list_source_id' => $listSource->id]);
    }

    public function test_detach_returns_403_for_non_owner_of_podcast(): void
    {
        $otherUser  = \App\Models\User::factory()->create();
        $podcast    = Podcast::factory()->forUser($otherUser)->create();
        $list       = ListModel::factory()->forUser($otherUser)->create();
        $listSource = $this->createListSource($podcast->id, 'podcast', $list->id);

        $this->delete(route('digest-podcasts.list_sources.detach', [$podcast, $listSource]))
            ->assertForbidden();
    }

    public function test_detach_returns_403_when_list_source_belongs_to_different_podcast(): void
    {
        $podcast      = Podcast::factory()->forUser($this->user)->create();
        $otherPodcast = Podcast::factory()->forUser($this->user)->create();
        $list         = ListModel::factory()->forUser($this->user)->create();
        $listSource   = $this->createListSource($otherPodcast->id, 'podcast', $list->id);

        $this->delete(route('digest-podcasts.list_sources.detach', [$podcast, $listSource]))
            ->assertForbidden();
    }

    // ── XML + data fixtures ────────────────────────────────────────────────────

    private function podcastXml(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
          <channel>
            <title>My Podcast</title>
            <link>https://example.com</link>
            <description>A great podcast about things.</description>
            <itunes:image href="https://example.com/cover.jpg"/>
          </channel>
        </rss>
        XML;
    }

    private function feedData(): array
    {
        return [
            'title'       => 'My Podcast',
            'description' => 'A great podcast about things.',
            'site_url'    => 'https://example.com',
            'rss_url'     => 'https://example.com/podcast.xml',
            'thumbnail'   => 'https://example.com/cover.jpg',
        ];
    }

    /**
     * Insert a list_sources row directly via DB and return it as a ListSource model.
     */
    private function createListSource(
        int     $sourceableId,
        string  $sourceableType,
        int     $listId,
        string  $mode = 'description',
        ?string $searchTerms = null
    ): ListSource {
        DB::table('list_sources')->insert([
            'list_id'         => $listId,
            'sourceable_id'   => $sourceableId,
            'sourceable_type' => $sourceableType,
            'enabled'         => true,
            'suspended'       => false,
            'processing_mode' => $mode,
            'search_terms'    => $searchTerms,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return ListSource::where('list_id', $listId)
            ->where('sourceable_id', $sourceableId)
            ->where('sourceable_type', $sourceableType)
            ->firstOrFail();
    }
}