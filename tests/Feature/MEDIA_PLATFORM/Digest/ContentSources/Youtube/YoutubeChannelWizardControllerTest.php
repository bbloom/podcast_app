<?php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\ContentSources\Youtube;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListSource;
use MediaPlatform\Digest\ContentSources\Youtube\Models\YoutubeChannel;
use MediaPlatform\Digest\ContentSources\Youtube\Services\YoutubeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YoutubeChannelWizardControllerTest extends TestCase
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
        YoutubeChannel::factory()->forUser($this->user)->count(2)->create();

        $this->get(route('youtube.channels.index'))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.youtube.channels.index')
            ->assertViewHas('channels');
    }

    // ── step 1: enter query ────────────────────────────────────────────────────

    public function test_step1_renders_form(): void
    {
        $this->get(route('youtube.channels.create.step1'))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.youtube.channels.wizard-step1');
    }

    public function test_step1_submit_validates_required_query(): void
    {
        $this->post(route('youtube.channels.create.step1.submit'), [])
            ->assertSessionHasErrors('query');
    }

    public function test_step1_submit_stores_results_in_session(): void
    {
        $this->fakeYoutubeSearch();

        $this->post(route('youtube.channels.create.step1.submit'), [
            'query' => 'tech reviews',
        ])->assertRedirect(route('youtube.channels.create.step2'))
          ->assertSessionHas('yt_wizard.results');
    }

    public function test_step1_submit_returns_error_when_no_results(): void
    {
        $this->fakeYoutubeEmpty();

        $this->post(route('youtube.channels.create.step1.submit'), [
            'query' => 'xyznonexistent123',
        ])->assertSessionHasErrors('query');
    }

    public function test_step1_submit_flags_already_added_channels(): void
    {
        YoutubeChannel::factory()->forUser($this->user)->create([
            'channel_id' => 'UCtest1234567890123456',
        ]);

        $this->fakeYoutubeSearch();

        $response = $this->post(route('youtube.channels.create.step1.submit'), [
            'query' => 'tech reviews',
        ]);

        $results = session('yt_wizard.results');
        $this->assertTrue($results[0]['already_added']);
    }

    // ── step 2: select channel ─────────────────────────────────────────────────

    public function test_step2_redirects_to_step1_without_session(): void
    {
        $this->get(route('youtube.channels.create.step2'))
            ->assertRedirect(route('youtube.channels.create.step1'));
    }

    public function test_step2_renders_with_valid_session(): void
    {
        $this->withSession(['yt_wizard.results' => [$this->channelData()]])
            ->get(route('youtube.channels.create.step2'))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.youtube.channels.wizard-step2')
            ->assertViewHas('results');
    }

    public function test_step2_submit_validates_required_channel_id(): void
    {
        $this->withSession(['yt_wizard.results' => [$this->channelData()]])
            ->post(route('youtube.channels.create.step2.submit'), [])
            ->assertSessionHasErrors('channel_id');
    }

    public function test_step2_submit_rejects_channel_id_not_in_results(): void
    {
        $this->withSession(['yt_wizard.results' => [$this->channelData()]])
            ->post(route('youtube.channels.create.step2.submit'), [
                'channel_id' => 'UCnotinresults',
            ])->assertSessionHasErrors('channel_id');
    }

    public function test_step2_submit_stores_selected_channel_id(): void
    {
        $data = $this->channelData();

        $this->withSession(['yt_wizard.results' => [$data]])
            ->post(route('youtube.channels.create.step2.submit'), [
                'channel_id' => $data['channel_id'],
            ])->assertRedirect(route('youtube.channels.create.step3'))
              ->assertSessionHas('yt_wizard.selected_channel_id', $data['channel_id']);
    }

    // ── step 3: confirm ────────────────────────────────────────────────────────

    public function test_step3_redirects_to_step1_without_session(): void
    {
        $this->get(route('youtube.channels.create.step3'))
            ->assertRedirect(route('youtube.channels.create.step1'));
    }

    public function test_step3_renders_with_valid_session(): void
    {
        $data = $this->channelData();

        $this->withSession([
            'yt_wizard.results'             => [$data],
            'yt_wizard.selected_channel_id' => $data['channel_id'],
        ])->get(route('youtube.channels.create.step3'))
          ->assertOk()
          ->assertViewIs('media_platform.digest.content_sources.youtube.channels.wizard-step3')
          ->assertViewHas('selected');
    }

    public function test_step3_submit_sets_confirmed_flag(): void
    {
        $data = $this->channelData();

        $this->withSession([
            'yt_wizard.results'             => [$data],
            'yt_wizard.selected_channel_id' => $data['channel_id'],
        ])->post(route('youtube.channels.create.step3.submit'))
          ->assertRedirect(route('youtube.channels.create.step4'))
          ->assertSessionHas('yt_wizard.confirmed', true);
    }

    // ── step 4: assign to lists ────────────────────────────────────────────────

    public function test_step4_redirects_to_step1_without_confirmation(): void
    {
        $this->get(route('youtube.channels.create.step4'))
            ->assertRedirect(route('youtube.channels.create.step1'));
    }

    public function test_step4_renders_with_valid_session(): void
    {
        $data = $this->channelData();
        ListModel::factory()->forUser($this->user)->count(2)->create();

        $this->withSession([
            'yt_wizard.results'             => [$data],
            'yt_wizard.selected_channel_id' => $data['channel_id'],
            'yt_wizard.confirmed'           => true,
        ])->get(route('youtube.channels.create.step4'))
          ->assertOk()
          ->assertViewIs('media_platform.digest.content_sources.youtube.channels.wizard-step4')
          ->assertViewHas('lists')
          ->assertViewHas('channelTitle', $data['title']);
    }

    public function test_step4_submit_validates_list_ids_required(): void
    {
        $data = $this->channelData();

        $this->withSession([
            'yt_wizard.results'             => [$data],
            'yt_wizard.selected_channel_id' => $data['channel_id'],
            'yt_wizard.confirmed'           => true,
        ])->post(route('youtube.channels.create.step4.submit'), [])
          ->assertSessionHasErrors('list_ids');
    }

    public function test_step4_submit_rejects_lists_owned_by_other_user(): void
    {
        $data      = $this->channelData();
        $otherUser = \App\Models\User::factory()->create();
        $otherList = ListModel::factory()->forUser($otherUser)->create();

        $this->withSession([
            'yt_wizard.results'             => [$data],
            'yt_wizard.selected_channel_id' => $data['channel_id'],
            'yt_wizard.confirmed'           => true,
        ])->post(route('youtube.channels.create.step4.submit'), [
            'list_ids' => [$otherList->id],
        ])->assertSessionHasErrors('list_ids');
    }

    public function test_step4_submit_persists_channel_and_list_sources(): void
    {
        $data  = $this->channelData();
        $lists = ListModel::factory()->forUser($this->user)->count(2)->create();

        $this->withSession([
            'yt_wizard.results'             => [$data],
            'yt_wizard.selected_channel_id' => $data['channel_id'],
            'yt_wizard.confirmed'           => true,
        ])->post(route('youtube.channels.create.step4.submit'), [
            'list_ids' => $lists->pluck('id')->toArray(),
        ])->assertRedirect(route('youtube.channels.create.step5'));

        $this->assertDatabaseHas('youtube_channels', [
            'user_id'    => $this->user->id,
            'channel_id' => $data['channel_id'],
            'title'      => $data['title'],
        ]);

        $channel = YoutubeChannel::where('user_id', $this->user->id)->firstOrFail();

        $this->assertCount(2, $channel->listSources);

        foreach ($lists as $list) {
            $this->assertDatabaseHas('list_sources', [
                'list_id'         => $list->id,
                'sourceable_id'   => $channel->id,
                'sourceable_type' => 'youtube_channel',
                'enabled'         => true,
                'suspended'       => false,
            ]);
        }
    }

    public function test_step4_submit_clears_wizard_session(): void
    {
        $data = $this->channelData();
        $list = ListModel::factory()->forUser($this->user)->create();

        $response = $this->withSession([
            'yt_wizard.results'              => [$data],
            'yt_wizard.selected_channel_id'  => $data['channel_id'],
            'yt_wizard.confirmed'            => true,
        ])->post(route('youtube.channels.create.step4.submit'), [
            'list_ids' => [$list->id],
        ]);

        $response->assertSessionMissing('yt_wizard.query');
        $response->assertSessionMissing('yt_wizard.results');
        $response->assertSessionMissing('yt_wizard.selected_channel_id');
        $response->assertSessionMissing('yt_wizard.confirmed');
        $response->assertSessionHas('yt_wizard.saved_title');
        $response->assertSessionHas('yt_wizard.saved_list_count');
    }

    // ── step 5: done ───────────────────────────────────────────────────────────

    public function test_step5_renders_with_saved_data(): void
    {
        $this->withSession([
            'yt_wizard.saved_title'      => 'MKBHD',
            'yt_wizard.saved_list_count' => 1,
        ])->get(route('youtube.channels.create.step5'))
          ->assertOk()
          ->assertViewIs('media_platform.digest.content_sources.youtube.channels.wizard-step5')
          ->assertViewHas('title', 'MKBHD')
          ->assertViewHas('listCount', 1);
    }

    public function test_step5_uses_defaults_when_session_empty(): void
    {
        $this->get(route('youtube.channels.create.step5'))
            ->assertOk()
            ->assertViewHas('title', 'Channel')
            ->assertViewHas('listCount', 0);
    }

    // ── edit ───────────────────────────────────────────────────────────────────

    public function test_edit_renders_for_owner(): void
    {
        $channel = YoutubeChannel::factory()->forUser($this->user)->create();

        $this->get(route('youtube.channels.edit', $channel))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.youtube.channels.edit');
    }

    public function test_edit_returns_403_for_non_owner(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $channel   = YoutubeChannel::factory()->forUser($otherUser)->create();

        $this->get(route('youtube.channels.edit', $channel))
            ->assertForbidden();
    }

    // ── show ───────────────────────────────────────────────────────────────────

    public function test_show_renders_for_owner(): void
    {
        $channel = YoutubeChannel::factory()->forUser($this->user)->create();

        $this->get(route('youtube.channels.show', $channel))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.youtube.channels.show')
            ->assertViewHas('youtubeChannel')
            ->assertViewHas('listSources')
            ->assertViewHas('tracking')
            ->assertViewHas('availableLists');
    }

    public function test_show_available_lists_excludes_already_attached(): void
    {
        $channel  = YoutubeChannel::factory()->forUser($this->user)->create();
        $attached = ListModel::factory()->forUser($this->user)->create();
        $free     = ListModel::factory()->forUser($this->user)->create();

        $this->createListSource($channel->id, 'youtube_channel', $attached->id);

        $response = $this->get(route('youtube.channels.show', $channel));

        $availableLists = $response->viewData('availableLists');
        $this->assertFalse($availableLists->contains('id', $attached->id));
        $this->assertTrue($availableLists->contains('id', $free->id));
    }

    public function test_show_returns_403_for_non_owner(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $channel   = YoutubeChannel::factory()->forUser($otherUser)->create();

        $this->get(route('youtube.channels.show', $channel))
            ->assertForbidden();
    }

    public function test_show_returns_404_for_missing_record(): void
    {
        $this->get(route('youtube.channels.show', 99999))
            ->assertNotFound();
    }

    // ── update ─────────────────────────────────────────────────────────────────

    public function test_update_saves_enabled_status(): void
    {
        $channel = YoutubeChannel::factory()->forUser($this->user)->create(['enabled' => true]);

        $this->put(route('youtube.channels.update', $channel), ['enabled' => '0'])
            ->assertRedirect(route('youtube.channels.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('youtube_channels', ['id' => $channel->id, 'enabled' => false]);
    }

    public function test_update_returns_403_for_non_owner(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $channel   = YoutubeChannel::factory()->forUser($otherUser)->create();

        $this->put(route('youtube.channels.update', $channel), ['enabled' => '1'])
            ->assertForbidden();
    }

    // ── delete ─────────────────────────────────────────────────────────────────

    public function test_confirm_delete_renders_for_owner(): void
    {
        $channel = YoutubeChannel::factory()->forUser($this->user)->create();

        $this->get(route('youtube.channels.delete.confirm', $channel))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.youtube.channels.delete-confirm');
    }

    public function test_destroy_deletes_channel_and_redirects(): void
    {
        $channel = YoutubeChannel::factory()->forUser($this->user)->create();

        $this->delete(route('youtube.channels.destroy', $channel))
            ->assertRedirect(route('youtube.channels.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('youtube_channels', ['id' => $channel->id]);
    }

    public function test_destroy_returns_403_for_non_owner(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $channel   = YoutubeChannel::factory()->forUser($otherUser)->create();

        $this->delete(route('youtube.channels.destroy', $channel))
            ->assertForbidden();
    }

    // ── attachList ─────────────────────────────────────────────────────────────

    public function test_attach_list_creates_list_source_row(): void
    {
        $channel = YoutubeChannel::factory()->forUser($this->user)->create();
        $list    = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('youtube.channels.list_sources.attach', $channel), [
            'list_id'         => $list->id,
            'processing_mode' => 'description',
        ])->assertRedirect(route('youtube.channels.show', $channel))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('list_sources', [
            'list_id'         => $list->id,
            'sourceable_id'   => $channel->id,
            'sourceable_type' => 'youtube_channel',
            'processing_mode' => 'description',
            'enabled'         => true,
        ]);
    }

    public function test_attach_list_stores_search_terms_in_search_mode(): void
    {
        $channel = YoutubeChannel::factory()->forUser($this->user)->create();
        $list    = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('youtube.channels.list_sources.attach', $channel), [
            'list_id'         => $list->id,
            'processing_mode' => 'search',
            'search_terms'    => 'AI, robotics',
        ]);

        $this->assertDatabaseHas('list_sources', [
            'sourceable_id'   => $channel->id,
            'sourceable_type' => 'youtube_channel',
            'processing_mode' => 'search',
            'search_terms'    => 'AI, robotics',
        ]);
    }

    public function test_attach_list_nulls_search_terms_when_not_in_search_mode(): void
    {
        $channel = YoutubeChannel::factory()->forUser($this->user)->create();
        $list    = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('youtube.channels.list_sources.attach', $channel), [
            'list_id'         => $list->id,
            'processing_mode' => 'summary',
            'search_terms'    => 'should be ignored',
        ]);

        $this->assertDatabaseHas('list_sources', [
            'sourceable_id'   => $channel->id,
            'processing_mode' => 'summary',
            'search_terms'    => null,
        ]);
    }

    public function test_attach_list_rejects_duplicate(): void
    {
        $channel = YoutubeChannel::factory()->forUser($this->user)->create();
        $list    = ListModel::factory()->forUser($this->user)->create();

        $this->createListSource($channel->id, 'youtube_channel', $list->id);

        $this->post(route('youtube.channels.list_sources.attach', $channel), [
            'list_id'         => $list->id,
            'processing_mode' => 'description',
        ])->assertSessionHasErrors('list_id');
    }

    public function test_attach_list_rejects_list_owned_by_other_user(): void
    {
        $channel   = YoutubeChannel::factory()->forUser($this->user)->create();
        $otherUser = \App\Models\User::factory()->create();
        $otherList = ListModel::factory()->forUser($otherUser)->create();

        $this->post(route('youtube.channels.list_sources.attach', $channel), [
            'list_id'         => $otherList->id,
            'processing_mode' => 'description',
        ])->assertSessionHasErrors('list_id');
    }

    public function test_attach_list_returns_403_for_non_owner_of_channel(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $channel   = YoutubeChannel::factory()->forUser($otherUser)->create();
        $list      = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('youtube.channels.list_sources.attach', $channel), [
            'list_id'         => $list->id,
            'processing_mode' => 'description',
        ])->assertForbidden();
    }

    public function test_attach_list_validates_required_fields(): void
    {
        $channel = YoutubeChannel::factory()->forUser($this->user)->create();

        $this->post(route('youtube.channels.list_sources.attach', $channel), [])
            ->assertSessionHasErrors(['list_id', 'processing_mode']);
    }

    public function test_attach_list_validates_processing_mode_values(): void
    {
        $channel = YoutubeChannel::factory()->forUser($this->user)->create();
        $list    = ListModel::factory()->forUser($this->user)->create();

        $this->post(route('youtube.channels.list_sources.attach', $channel), [
            'list_id'         => $list->id,
            'processing_mode' => 'invalid_mode',
        ])->assertSessionHasErrors('processing_mode');
    }

    // ── updateListSource ───────────────────────────────────────────────────────

    public function test_update_list_source_saves_new_mode(): void
    {
        $channel    = YoutubeChannel::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($channel->id, 'youtube_channel', $list->id, 'description');

        $this->patch(route('youtube.channels.list_sources.update', [$channel, $listSource]), [
            'processing_mode' => 'summary',
        ])->assertRedirect(route('youtube.channels.show', $channel))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('list_sources', [
            'id'              => $listSource->id,
            'processing_mode' => 'summary',
            'search_terms'    => null,
        ]);
    }

    public function test_update_list_source_saves_search_terms_in_search_mode(): void
    {
        $channel    = YoutubeChannel::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($channel->id, 'youtube_channel', $list->id, 'description');

        $this->patch(route('youtube.channels.list_sources.update', [$channel, $listSource]), [
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
        $channel    = YoutubeChannel::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($channel->id, 'youtube_channel', $list->id, 'search', 'AI');

        $this->patch(route('youtube.channels.list_sources.update', [$channel, $listSource]), [
            'processing_mode' => 'description',
        ]);

        $this->assertDatabaseHas('list_sources', [
            'id'              => $listSource->id,
            'processing_mode' => 'description',
            'search_terms'    => null,
        ]);
    }

    public function test_update_list_source_returns_403_for_non_owner_of_channel(): void
    {
        $otherUser  = \App\Models\User::factory()->create();
        $channel    = YoutubeChannel::factory()->forUser($otherUser)->create();
        $list       = ListModel::factory()->forUser($otherUser)->create();
        $listSource = $this->createListSource($channel->id, 'youtube_channel', $list->id);

        $this->patch(route('youtube.channels.list_sources.update', [$channel, $listSource]), [
            'processing_mode' => 'summary',
        ])->assertForbidden();
    }

    public function test_update_list_source_returns_403_when_list_source_belongs_to_different_channel(): void
    {
        $channel      = YoutubeChannel::factory()->forUser($this->user)->create();
        $otherChannel = YoutubeChannel::factory()->forUser($this->user)->create();
        $list         = ListModel::factory()->forUser($this->user)->create();
        // list_source belongs to otherChannel, not channel
        $listSource = $this->createListSource($otherChannel->id, 'youtube_channel', $list->id);

        $this->patch(route('youtube.channels.list_sources.update', [$channel, $listSource]), [
            'processing_mode' => 'summary',
        ])->assertForbidden();
    }

    // ── detachConfirm ──────────────────────────────────────────────────────────

    public function test_detach_confirm_renders_for_owner(): void
    {
        $channel    = YoutubeChannel::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($channel->id, 'youtube_channel', $list->id);

        $this->get(route('youtube.channels.list_sources.detach.confirm', [$channel, $listSource]))
            ->assertOk()
            ->assertViewIs('media_platform.digest.content_sources.youtube.channels.detach-confirm')
            ->assertViewHas('source')
            ->assertViewHas('listSource');
    }

    public function test_detach_confirm_returns_403_for_non_owner_of_channel(): void
    {
        $otherUser  = \App\Models\User::factory()->create();
        $channel    = YoutubeChannel::factory()->forUser($otherUser)->create();
        $list       = ListModel::factory()->forUser($otherUser)->create();
        $listSource = $this->createListSource($channel->id, 'youtube_channel', $list->id);

        $this->get(route('youtube.channels.list_sources.detach.confirm', [$channel, $listSource]))
            ->assertForbidden();
    }

    public function test_detach_confirm_returns_403_when_list_source_belongs_to_different_channel(): void
    {
        $channel      = YoutubeChannel::factory()->forUser($this->user)->create();
        $otherChannel = YoutubeChannel::factory()->forUser($this->user)->create();
        $list         = ListModel::factory()->forUser($this->user)->create();
        $listSource   = $this->createListSource($otherChannel->id, 'youtube_channel', $list->id);

        $this->get(route('youtube.channels.list_sources.detach.confirm', [$channel, $listSource]))
            ->assertForbidden();
    }

    // ── detach ─────────────────────────────────────────────────────────────────

    public function test_detach_deletes_list_source_and_redirects(): void
    {
        $channel    = YoutubeChannel::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($channel->id, 'youtube_channel', $list->id);

        $this->delete(route('youtube.channels.list_sources.detach', [$channel, $listSource]))
            ->assertRedirect(route('youtube.channels.show', $channel))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('list_sources', ['id' => $listSource->id]);
    }

    public function test_detach_cascades_to_summaries(): void
    {
        $channel    = YoutubeChannel::factory()->forUser($this->user)->create();
        $list       = ListModel::factory()->forUser($this->user)->create();
        $listSource = $this->createListSource($channel->id, 'youtube_channel', $list->id);

        // Insert a summary row tied to this list_source
        DB::table('summaries')->insert([
            'user_id'           => $this->user->id,
            'list_source_id'    => $listSource->id,
            'source_url'        => 'https://youtube.com/watch?v=abc123',
            'processing_mode'   => 'description',
            'is_relevant'       => true,
            'included_in_digest'=> false,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->assertDatabaseHas('summaries', ['list_source_id' => $listSource->id]);

        $this->delete(route('youtube.channels.list_sources.detach', [$channel, $listSource]));

        $this->assertDatabaseMissing('summaries', ['list_source_id' => $listSource->id]);
    }

    public function test_detach_returns_403_for_non_owner_of_channel(): void
    {
        $otherUser  = \App\Models\User::factory()->create();
        $channel    = YoutubeChannel::factory()->forUser($otherUser)->create();
        $list       = ListModel::factory()->forUser($otherUser)->create();
        $listSource = $this->createListSource($channel->id, 'youtube_channel', $list->id);

        $this->delete(route('youtube.channels.list_sources.detach', [$channel, $listSource]))
            ->assertForbidden();
    }

    public function test_detach_returns_403_when_list_source_belongs_to_different_channel(): void
    {
        $channel      = YoutubeChannel::factory()->forUser($this->user)->create();
        $otherChannel = YoutubeChannel::factory()->forUser($this->user)->create();
        $list         = ListModel::factory()->forUser($this->user)->create();
        $listSource   = $this->createListSource($otherChannel->id, 'youtube_channel', $list->id);

        $this->delete(route('youtube.channels.list_sources.detach', [$channel, $listSource]))
            ->assertForbidden();
    }

    // ── YouTube API fakes ──────────────────────────────────────────────────────

    private function fakeYoutubeSearch(): void
    {
        Http::fake([
            'googleapis.com/youtube/v3/search*' => Http::response([
                'items' => [[
                    'snippet' => ['channelId' => 'UCtest1234567890123456'],
                ]],
            ]),
            'googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [[
                    'id'      => 'UCtest1234567890123456',
                    'snippet' => [
                        'title'       => 'Tech Reviews',
                        'description' => 'A tech channel.',
                        'customUrl'   => '@techreviews',
                        'thumbnails'  => ['default' => ['url' => 'https://yt.com/thumb.jpg']],
                    ],
                ]],
            ]),
        ]);
    }

    private function fakeYoutubeEmpty(): void
    {
        Http::fake([
            'googleapis.com/youtube/v3/search*'   => Http::response(['items' => []]),
            'googleapis.com/youtube/v3/channels*'  => Http::response(['items' => []]),
        ]);
    }

    private function fakeYoutubeSearchWithChannelId(string $channelId): void
    {
        Http::fake([
            'googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [[
                    'id'      => $channelId,
                    'snippet' => [
                        'title'       => 'Existing Channel',
                        'description' => 'Already added.',
                        'customUrl'   => '@existingchannel',
                        'thumbnails'  => ['default' => ['url' => 'https://yt.com/thumb.jpg']],
                    ],
                ]],
            ]),
        ]);
    }

    // ── Data fixtures ──────────────────────────────────────────────────────────

    private function channelData(): array
    {
        return [
            'channel_id'    => 'UCtest1234567890123456',
            'title'         => 'Tech Reviews',
            'description'   => 'A tech channel.',
            'thumbnail'     => 'https://yt.com/thumb.jpg',
            'handle'        => '@techreviews',
            'channel_url'   => 'https://www.youtube.com/@techreviews',
            'rss_url'       => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCtest1234567890123456',
            'already_added' => false,
        ];
    }

    /**
     * Insert a list_sources row directly via DB and return it as a ListSource model.
     */
    private function createListSource(
        int    $sourceableId,
        string $sourceableType,
        int    $listId,
        string $mode = 'description',
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