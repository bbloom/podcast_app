<?php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\ContentSources\TextBasedRssFeeds;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListSource;
use MediaPlatform\Digest\ContentSources\TextBasedRssFeeds\Models\TextBasedRssFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TextBasedRssFeedWizardControllerTest extends TestCase
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
        TextBasedRssFeed::factory()->forUser($this->user)->count(2)->create();

        $this->get(route('text_based_rss_feeds.index'))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.text_based_rss_feeds.index')
            ->assertViewHas('feeds');
    }

    // ── step 1: enter RSS URL ──────────────────────────────────────────────────

    public function test_step1_renders_form(): void
    {
        $this->get(route('text_based_rss_feeds.create.step1'))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.text_based_rss_feeds.wizard-step1');
    }

    public function test_step1_submit_validates_required_url(): void
    {
        $this->post(route('text_based_rss_feeds.create.step1.submit'), [])
            ->assertSessionHasErrors('rss_url');
    }

    public function test_step1_submit_validates_url_format(): void
    {
        $this->post(route('text_based_rss_feeds.create.step1.submit'), ['rss_url' => 'not-a-url'])
            ->assertSessionHasErrors('rss_url');
    }

    public function test_step1_submit_rejects_duplicate_feed_url(): void
    {
        TextBasedRssFeed::factory()->forUser($this->user)->create([
            'rss_url' => 'https://example.com/feed.xml',
        ]);

        $this->post(route('text_based_rss_feeds.create.step1.submit'), [
            'rss_url' => 'https://example.com/feed.xml',
        ])->assertSessionHasErrors('rss_url');
    }

    public function test_step1_submit_allows_same_url_for_different_user(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        TextBasedRssFeed::factory()->forUser($otherUser)->create([
            'rss_url' => 'https://example.com/feed.xml',
        ]);

        Http::fake(['example.com/*' => Http::response($this->feedXml())]);

        $this->post(route('text_based_rss_feeds.create.step1.submit'), [
            'rss_url' => 'https://example.com/feed.xml',
        ])->assertRedirect(route('text_based_rss_feeds.create.step2'));
    }

    public function test_step1_submit_fetches_feed_and_stores_in_session(): void
    {
        Http::fake(['example.com/*' => Http::response($this->feedXml())]);

        $this->post(route('text_based_rss_feeds.create.step1.submit'), [
            'rss_url' => 'https://example.com/feed.xml',
        ])->assertRedirect(route('text_based_rss_feeds.create.step2'))
          ->assertSessionHas('rss_wizard.rss_url')
          ->assertSessionHas('rss_wizard.feed_data');
    }

    public function test_step1_submit_returns_error_when_feed_unreachable(): void
    {
        Http::fake(['example.com/*' => Http::response('', 500)]);

        $this->post(route('text_based_rss_feeds.create.step1.submit'), [
            'rss_url' => 'https://example.com/feed.xml',
        ])->assertSessionHasErrors('rss_url');
    }

    public function test_step1_submit_returns_error_for_invalid_xml(): void
    {
        Http::fake(['example.com/*' => Http::response('not xml at all')]);

        $this->post(route('text_based_rss_feeds.create.step1.submit'), [
            'rss_url' => 'https://example.com/feed.xml',
        ])->assertSessionHasErrors('rss_url');
    }

    // ── step 2: confirm feed details ───────────────────────────────────────────

    public function test_step2_redirects_to_step1_without_session(): void
    {
        $this->get(route('text_based_rss_feeds.create.step2'))
            ->assertRedirect(route('text_based_rss_feeds.create.step1'));
    }

    public function test_step2_renders_with_valid_session(): void
    {
        $this->withSession([
            'rss_wizard.rss_url'   => 'https://example.com/feed.xml',
            'rss_wizard.feed_data' => $this->feedData(),
        ])->get(route('text_based_rss_feeds.create.step2'))
          ->assertOk()
          ->assertViewIs('media_platform.digest.content_sources.text_based_rss_feeds.wizard-step2')
          ->assertViewHas('feed');
    }

    public function test_step2_submit_sets_confirmed_flag(): void
    {
        $this->withSession([
            'rss_wizard.rss_url'   => 'https://example.com/feed.xml',
            'rss_wizard.feed_data' => $this->feedData(),
        ])->post(route('text_based_rss_feeds.create.step2.submit'))
          ->assertRedirect(route('text_based_rss_feeds.create.step3'))
          ->assertSessionHas('rss_wizard.confirmed', true);
    }

    public function test_step2_submit_redirects_to_step1_without_session(): void
    {
        $this->post(route('text_based_rss_feeds.create.step2.submit'))
            ->assertRedirect(route('text_based_rss_feeds.create.step1'));
    }

    // ── step 3: assign to lists ────────────────────────────────────────────────

    public function test_step3_redirects_to_step1_without_confirmation(): void
    {
        $this->get(route('text_based_rss_feeds.create.step3'))
            ->assertRedirect(route('text_based_rss_feeds.create.step1'));
    }

    public function test_step3_renders_with_valid_session(): void
    {
        ListModel::factory()->forUser($this->user)->count(2)->create();

        $this->withSession([
            'rss_wizard.rss_url'   => 'https://example.com/feed.xml',
            'rss_wizard.feed_data' => $this->feedData(),
            'rss_wizard.confirmed' => true,
        ])->get(route('text_based_rss_feeds.create.step3'))
          ->assertOk()
          ->assertViewIs('media_platform.digest.content_sources.text_based_rss_feeds.wizard-step3')
          ->assertViewHas('lists')
          ->assertViewHas('feedTitle', 'Tech Daily');
    }

    public function test_step3_submit_validates_list_ids_required(): void
    {
        $this->withSession([
            'rss_wizard.rss_url'   => 'https://example.com/feed.xml',
            'rss_wizard.feed_data' => $this->feedData(),
            'rss_wizard.confirmed' => true,
        ])->post(route('text_based_rss_feeds.create.step3.submit'), [])
          ->assertSessionHasErrors('list_ids');
    }

    public function test_step3_submit_rejects_lists_owned_by_other_user(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $otherList = ListModel::factory()->forUser($otherUser)->create();

        $this->withSession([
            'rss_wizard.rss_url'   => 'https://example.com/feed.xml',
            'rss_wizard.feed_data' => $this->feedData(),
            'rss_wizard.confirmed' => true,
        ])->post(route('text_based_rss_feeds.create.step3.submit'), [
            'list_ids' => [$otherList->id],
        ])->assertSessionHasErrors('list_ids');
    }

    public function test_step3_submit_persists_feed_and_list_sources(): void
    {
        $lists = ListModel::factory()->forUser($this->user)->count(2)->create();

        $this->withSession([
            'rss_wizard.rss_url'   => 'https://example.com/feed.xml',
            'rss_wizard.feed_data' => $this->feedData(),
            'rss_wizard.confirmed' => true,
        ])->post(route('text_based_rss_feeds.create.step3.submit'), [
            'list_ids' => $lists->pluck('id')->toArray(),
        ])->assertRedirect(route('text_based_rss_feeds.create.step4'));

        $this->assertDatabaseHas('text_based_rss_feeds', [
            'user_id' => $this->user->id,
            'rss_url' => 'https://example.com/feed.xml',
            'title'   => 'Tech Daily',
        ]);

        $feed = TextBasedRssFeed::where('user_id', $this->user->id)->firstOrFail();

        $this->assertCount(2, $feed->listSources);

        foreach ($lists as $list) {
            $this->assertDatabaseHas('list_sources', [
                'list_id'         => $list->id,
                'sourceable_id'   => $feed->id,
                'sourceable_type' => 'text_based_rss_feed',
                'enabled'         => true,
                'suspended'       => false,
            ]);
        }
    }

    public function test_step3_submit_clears_wizard_session(): void
    {
        $list = ListModel::factory()->forUser($this->user)->create();

        $response = $this->withSession([
            'rss_wizard.rss_url'   => 'https://example.com/feed.xml',
            'rss_wizard.feed_data' => $this->feedData(),
            'rss_wizard.confirmed' => true,
        ])->post(route('text_based_rss_feeds.create.step3.submit'), [
            'list_ids' => [$list->id],
        ]);

        $response->assertSessionMissing('rss_wizard.rss_url');
        $response->assertSessionMissing('rss_wizard.feed_data');
        $response->assertSessionMissing('rss_wizard.confirmed');
        $response->assertSessionHas('rss_wizard.saved_title');
        $response->assertSessionHas('rss_wizard.saved_list_count');
    }

    // ── step 4: done ───────────────────────────────────────────────────────────

    public function test_step4_renders_with_saved_data(): void
    {
        $this->withSession([
            'rss_wizard.saved_title'      => 'Tech Daily',
            'rss_wizard.saved_list_count' => 3,
        ])->get(route('text_based_rss_feeds.create.step4'))
          ->assertOk()
          ->assertViewIs('media_platform.digest.content_sources.text_based_rss_feeds.wizard-step4')
          ->assertViewHas('title', 'Tech Daily')
          ->assertViewHas('listCount', 3);
    }

    public function test_step4_uses_defaults_when_session_empty(): void
    {
        $this->get(route('text_based_rss_feeds.create.step4'))
            ->assertOk()
            ->assertViewHas('title', 'Feed')
            ->assertViewHas('listCount', 0);
    }

    // ── edit ───────────────────────────────────────────────────────────────────

    public function test_edit_renders_for_owner(): void
    {
        $feed = TextBasedRssFeed::factory()->forUser($this->user)->create();

        $this->get(route('text_based_rss_feeds.edit', $feed))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.text_based_rss_feeds.edit');
    }

    public function test_edit_returns_403_for_non_owner(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $feed      = TextBasedRssFeed::factory()->forUser($otherUser)->create();

        $this->get(route('text_based_rss_feeds.edit', $feed))
            ->assertForbidden();
    }

    // ── show ───────────────────────────────────────────────────────────────────

    public function test_show_renders_for_owner(): void
    {
        $feed = TextBasedRssFeed::factory()->forUser($this->user)->create();

        $this->get(route('text_based_rss_feeds.show', $feed))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.text_based_rss_feeds.show')
            ->assertViewHas('textBasedRssFeed')
            ->assertViewHas('listSources')
            ->assertViewHas('tracking')
            ->assertViewHas('availableLists');
    }

    public function test_show_available_lists_excludes_already_attached(): void
    {
        $feed     = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $attached = ListModel::factory()->forUser($this->user)->create();
        $free     = ListModel::factory()->forUser($this->user)->create();

        $this->createListSource($feed->id, 'text_based_rss_feed', $attached->id);

        $response       = $this->get(route('text_based_rss_feeds.show', $feed));
        $availableLists = $response->viewData('availableLists');

        $this->assertFalse($availableLists->contains('id', $attached->id));
        $this->assertTrue($availableLists->contains('id', $free->id));
    }

    public function test_show_returns_403_for_non_owner(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $feed      = TextBasedRssFeed::factory()->forUser($otherUser)->create();

        $this->get(route('text_based_rss_feeds.show', $feed))
            ->assertForbidden();
    }

    public function test_show_returns_404_for_missing_record(): void
    {
        $this->get(route('text_based_rss_feeds.show', 99999))
            ->assertNotFound();
    }

    // ── update ─────────────────────────────────────────────────────────────────

    public function test_update_saves_enabled_status(): void
    {
        $feed = TextBasedRssFeed::factory()->forUser($this->user)->create(['enabled' => true]);

        $this->put(route('text_based_rss_feeds.update', $feed), ['enabled' => '0'])
            ->assertRedirect(route('text_based_rss_feeds.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('text_based_rss_feeds', ['id' => $feed->id, 'enabled' => false]);
    }

    public function test_update_returns_403_for_non_owner(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $feed      = TextBasedRssFeed::factory()->forUser($otherUser)->create();

        $this->put(route('text_based_rss_feeds.update', $feed), ['enabled' => '1'])
            ->assertForbidden();
    }

    // ── delete ─────────────────────────────────────────────────────────────────

    public function test_confirm_delete_renders_for_owner(): void
    {
        $feed = TextBasedRssFeed::factory()->forUser($this->user)->create();

        $this->get(route('text_based_rss_feeds.delete.confirm', $feed))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.text_based_rss_feeds.delete-confirm');
    }

    public function test_destroy_deletes_feed_and_redirects(): void
    {
        $feed = TextBasedRssFeed::factory()->forUser($this->user)->create();

        $this->delete(route('text_based_rss_feeds.destroy', $feed))
            ->assertRedirect(route('text_based_rss_feeds.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('text_based_rss_feeds', ['id' => $feed->id]);
    }

    public function test_destroy_returns_403_for_non_owner(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $feed      = TextBasedRssFeed::factory()->forUser($otherUser)->create();

        $this->delete(route('text_based_rss_feeds.destroy', $feed))
            ->assertForbidden();
    }

    // ── attachList ─────────────────────────────────────────────────────────────

    public function test_attach_list_creates_list_source_row(): void
    {
        $feed = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('text_based_rss_feeds.list_sources.attach', $feed), [
            'list_id'         => $list->id,
            'processing_mode' => 'description',
        ])->assertRedirect(route('text_based_rss_feeds.show', $feed))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('list_sources', [
            'list_id'         => $list->id,
            'sourceable_id'   => $feed->id,
            'sourceable_type' => 'text_based_rss_feed',
            'processing_mode' => 'description',
            'enabled'         => true,
        ]);
    }

    public function test_attach_list_stores_search_terms_in_search_mode(): void
    {
        $feed = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('text_based_rss_feeds.list_sources.attach', $feed), [
            'list_id'         => $list->id,
            'processing_mode' => 'search',
            'search_terms'    => 'AI, robotics',
        ]);

        $this->assertDatabaseHas('list_sources', [
            'sourceable_id'   => $feed->id,
            'sourceable_type' => 'text_based_rss_feed',
            'processing_mode' => 'search',
            'search_terms'    => 'AI, robotics',
        ]);
    }

    public function test_attach_list_nulls_search_terms_when_not_in_search_mode(): void
    {
        $feed = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('text_based_rss_feeds.list_sources.attach', $feed), [
            'list_id'         => $list->id,
            'processing_mode' => 'summary',
            'search_terms'    => 'should be ignored',
        ]);

        $this->assertDatabaseHas('list_sources', [
            'sourceable_id'   => $feed->id,
            'processing_mode' => 'summary',
            'search_terms'    => null,
        ]);
    }

    public function test_attach_list_rejects_duplicate(): void
    {
        $feed = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->createListSource($feed->id, 'text_based_rss_feed', $list->id);

        $this->post(route('text_based_rss_feeds.list_sources.attach', $feed), [
            'list_id'         => $list->id,
            'processing_mode' => 'description',
        ])->assertSessionHasErrors('list_id');
    }

    public function test_attach_list_rejects_list_owned_by_other_user(): void
    {
        $feed      = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $otherUser = \App\Models\User::factory()->create();
        $otherList = ListModel::factory()->forUser($otherUser)->create();

        $this->post(route('text_based_rss_feeds.list_sources.attach', $feed), [
            'list_id'         => $otherList->id,
            'processing_mode' => 'description',
        ])->assertSessionHasErrors('list_id');
    }

    public function test_attach_list_returns_403_for_non_owner_of_feed(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $feed      = TextBasedRssFeed::factory()->forUser($otherUser)->create();
        $list      = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('text_based_rss_feeds.list_sources.attach', $feed), [
            'list_id'         => $list->id,
            'processing_mode' => 'description',
        ])->assertForbidden();
    }

    public function test_attach_list_validates_required_fields(): void
    {
        $feed = TextBasedRssFeed::factory()->forUser($this->user)->create();

        $this->post(route('text_based_rss_feeds.list_sources.attach', $feed), [])
            ->assertSessionHasErrors(['list_id', 'processing_mode']);
    }

    public function test_attach_list_validates_processing_mode_values(): void
    {
        $feed = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('text_based_rss_feeds.list_sources.attach', $feed), [
            'list_id'         => $list->id,
            'processing_mode' => 'invalid_mode',
        ])->assertSessionHasErrors('processing_mode');
    }

    // ── updateListSource ───────────────────────────────────────────────────────

    public function test_update_list_source_saves_new_mode(): void
    {
        $feed       = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($feed->id, 'text_based_rss_feed', $list->id, 'description');

        $this->patch(route('text_based_rss_feeds.list_sources.update', [$feed, $listSource]), [
            'processing_mode' => 'summary',
        ])->assertRedirect(route('text_based_rss_feeds.show', $feed))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('list_sources', [
            'id'              => $listSource->id,
            'processing_mode' => 'summary',
            'search_terms'    => null,
        ]);
    }

    public function test_update_list_source_saves_search_terms_in_search_mode(): void
    {
        $feed       = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($feed->id, 'text_based_rss_feed', $list->id, 'description');

        $this->patch(route('text_based_rss_feeds.list_sources.update', [$feed, $listSource]), [
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
        $feed       = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($feed->id, 'text_based_rss_feed', $list->id, 'search', 'AI');

        $this->patch(route('text_based_rss_feeds.list_sources.update', [$feed, $listSource]), [
            'processing_mode' => 'description',
        ]);

        $this->assertDatabaseHas('list_sources', [
            'id'              => $listSource->id,
            'processing_mode' => 'description',
            'search_terms'    => null,
        ]);
    }

    public function test_update_list_source_returns_403_for_non_owner_of_feed(): void
    {
        $otherUser  = \App\Models\User::factory()->create();
        $feed       = TextBasedRssFeed::factory()->forUser($otherUser)->create();
        $list       = ListModel::factory()->forUser($otherUser)->create();
        $listSource = $this->createListSource($feed->id, 'text_based_rss_feed', $list->id);

        $this->patch(route('text_based_rss_feeds.list_sources.update', [$feed, $listSource]), [
            'processing_mode' => 'summary',
        ])->assertForbidden();
    }

    public function test_update_list_source_returns_403_when_list_source_belongs_to_different_feed(): void
    {
        $feed      = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $otherFeed = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $list      = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($otherFeed->id, 'text_based_rss_feed', $list->id);

        $this->patch(route('text_based_rss_feeds.list_sources.update', [$feed, $listSource]), [
            'processing_mode' => 'summary',
        ])->assertForbidden();
    }

    // ── detachConfirm ──────────────────────────────────────────────────────────

    public function test_detach_confirm_renders_for_owner(): void
    {
        $feed       = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($feed->id, 'text_based_rss_feed', $list->id);

        $this->get(route('text_based_rss_feeds.list_sources.detach.confirm', [$feed, $listSource]))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.text_based_rss_feeds.detach-confirm')
            ->assertViewHas('source')
            ->assertViewHas('listSource');
    }

    public function test_detach_confirm_returns_403_for_non_owner_of_feed(): void
    {
        $otherUser  = \App\Models\User::factory()->create();
        $feed       = TextBasedRssFeed::factory()->forUser($otherUser)->create();
        $list       = ListModel::factory()->forUser($otherUser)->create();
        $listSource = $this->createListSource($feed->id, 'text_based_rss_feed', $list->id);

        $this->get(route('text_based_rss_feeds.list_sources.detach.confirm', [$feed, $listSource]))
            ->assertForbidden();
    }

    public function test_detach_confirm_returns_403_when_list_source_belongs_to_different_feed(): void
    {
        $feed       = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $otherFeed  = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($otherFeed->id, 'text_based_rss_feed', $list->id);

        $this->get(route('text_based_rss_feeds.list_sources.detach.confirm', [$feed, $listSource]))
            ->assertForbidden();
    }

    // ── detach ─────────────────────────────────────────────────────────────────

    public function test_detach_deletes_list_source_and_redirects(): void
    {
        $feed       = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($feed->id, 'text_based_rss_feed', $list->id);

        $this->delete(route('text_based_rss_feeds.list_sources.detach', [$feed, $listSource]))
            ->assertRedirect(route('text_based_rss_feeds.show', $feed))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('list_sources', ['id' => $listSource->id]);
    }

    public function test_detach_cascades_to_summaries(): void
    {
        $feed       = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($feed->id, 'text_based_rss_feed', $list->id);

        DB::table('summaries')->insert([
            'user_id'            => $this->user->id,
            'list_source_id'     => $listSource->id,
            'source_url'         => 'https://example.com/article-1',
            'processing_mode'    => 'description',
            'is_relevant'        => true,
            'included_in_digest' => false,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->assertDatabaseHas('summaries', ['list_source_id' => $listSource->id]);

        $this->delete(route('text_based_rss_feeds.list_sources.detach', [$feed, $listSource]));

        $this->assertDatabaseMissing('summaries', ['list_source_id' => $listSource->id]);
    }

    public function test_detach_returns_403_for_non_owner_of_feed(): void
    {
        $otherUser  = \App\Models\User::factory()->create();
        $feed       = TextBasedRssFeed::factory()->forUser($otherUser)->create();
        $list       = ListModel::factory()->forUser($otherUser)->create();
        $listSource = $this->createListSource($feed->id, 'text_based_rss_feed', $list->id);

        $this->delete(route('text_based_rss_feeds.list_sources.detach', [$feed, $listSource]))
            ->assertForbidden();
    }

    public function test_detach_returns_403_when_list_source_belongs_to_different_feed(): void
    {
        $feed       = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $otherFeed  = TextBasedRssFeed::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($otherFeed->id, 'text_based_rss_feed', $list->id);

        $this->delete(route('text_based_rss_feeds.list_sources.detach', [$feed, $listSource]))
            ->assertForbidden();
    }

    // ── XML + data fixtures ────────────────────────────────────────────────────

    private function feedXml(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <title>Tech Daily</title>
            <link>https://example.com</link>
            <description>Daily tech news.</description>
          </channel>
        </rss>
        XML;
    }

    private function feedData(): array
    {
        return [
            'title'       => 'Tech Daily',
            'description' => 'Daily tech news.',
            'site_url'    => 'https://example.com',
            'rss_url'     => 'https://example.com/feed.xml',
            'thumbnail'   => null,
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