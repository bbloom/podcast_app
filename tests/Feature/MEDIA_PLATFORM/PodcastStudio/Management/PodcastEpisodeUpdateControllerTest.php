<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\Management;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Controllers\PodcastEpisodeUpdateController;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\PodcastStudio\Management\Requests\UpdatePodcastEpisodeRequest;
use Illuminate\Http\Request;
use Tests\TestCase;

class PodcastEpisodeUpdateControllerTest extends TestCase
{
    use RefreshDatabase;


    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a minimum valid update payload.
     */
    private function updatePayload(PodcastShow $show, array $overrides = []): array
    {
        return array_merge([
            'podcast_show_id'          => $show->id,
            'title'                    => '#5 - My Test Episode',
            'slug'                     => 'bob-bloom-show-ep5-my-test-episode',
            'status'                   => PodcastEpisodeStatus::created->value,
            'scheduled_date'           => '2026-06-01',
            'draft'                    => null,
            'raw_input_audio_filename' => 'bobbloomshow5.wav',
            'itunes_title_tag'         => '#5 - My Test Episode',
            'itunes_enclosure_url'     => 'https://audio.example.com/bobbloomshow/bobbloomshow5.mp3',
            'itunes_enclosure_length'  => null,
            'itunes_enclosure_type'    => 'audio/mpeg',
            'itunes_guid'              => 'abcd1234-ef56-gh78-ij90-klmnopqrstuv',
            'itunes_pubdate'           => '2026-06-01',
            'itunes_description'       => 'Episode description.',
            'itunes_duration'          => null,
            'itunes_link'              => 'https://bobbloomshow.com/episode/bob-bloom-show-ep5-my-test-episode',
            'itunes_image'             => null,
            'itunes_explicit'          => '0',
            'itunes_itunestitle_tag'   => 'My Test Episode',
            'itunes_episode'           => 5,
            'itunes_season'            => 0,
            'itunes_episode_type'      => 'full',
            'itunes_block'             => '0',
            'itunes_summary'           => 'Episode description.',
            'itunes_subtitle'          => 'My Test Episode',
            'itunes_content_encoded'   => '<p>Episode description.</p>',
            'rss_feed_enabled'         => '0',
            'website_content'          => '<p>Episode description.</p>',
            'website_excerpt'          => 'Episode description.',
            'website_meta_description' => 'Episode description.',
            'website_episode_notes'    => null,
            'website_attribution'      => '<div>Beethoven</div>',
            'website_featured_image'   => null,
            'website_publish_on'       => '2026-06-01',
            'website_enabled'          => '0',
        ], $overrides);
    }

    /**
     * Create a show and episode belonging to the given user.
     */
    private function episodeForUser(User $user, array $overrides = []): PodcastEpisode
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create(array_merge([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::created,
        ], $overrides));
    }

    /**
     * Create a show with fields the population methods depend on.
     */
    private function showForUser(User $user, array $overrides = []): PodcastShow
    {
        return PodcastShow::factory()->create(array_merge([
            'user_id'                 => $user->id,
            'title'                   => 'The Bob Bloom Show',
            'slug'                    => 'bob-bloom-show',
            'itunes_link'             => 'https://bobbloomshow.com',
            'storage_audio_files_url' => 'https://audio.example.com/bobbloomshow',
        ], $overrides));
    }

    /**
     * Make a fake Request for unit-testing population methods.
     */
    private function fakeRequest(array $input): UpdatePodcastEpisodeRequest
    {
        $request = UpdatePodcastEpisodeRequest::create('/fake', 'PUT', $input);
        $request->setContainer(app());
        $request->validateResolved();

        return $request;
    }


    // -------------------------------------------------------------------------
    // edit — ownership
    // -------------------------------------------------------------------------

    public function test_edit_shows_form_to_the_episodes_owner(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes.edit', $episode))
            ->assertOk();
    }

    public function test_edit_redirects_with_error_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($other);

        $this->actingAs($user)
            ->get(route('podcast_episodes.edit', $episode))
            ->assertRedirect(route('podcast_episodes.index'))
            ->assertSessionHas('error');
    }


    // -------------------------------------------------------------------------
    // update — happy path
    // -------------------------------------------------------------------------

    public function test_update_saves_all_fields(): void
    {
        $user = User::factory()->create();
        $show = $this->showForUser($user);
        $episode = PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::created,
            'title'           => '#5 - Old Title',
            'slug'            => 'bob-bloom-show-ep5-old-title',
        ]);

        $payload = $this->updatePayload($show, [
            'title'              => '#5 - Updated Title',
            'slug'               => 'bob-bloom-show-ep5-old-title',
            'itunes_description' => 'Updated description.',
            'itunes_duration'    => '25:30',
            'website_content'    => '<p>Updated content.</p>',
        ]);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $payload)
            ->assertRedirect(route('podcast_episodes.show', $episode))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('podcast_episodes', [
            'id'                 => $episode->id,
            'title'              => '#5 - Updated Title',
            'slug'               => 'bob-bloom-show-ep5-old-title',
            'itunes_description' => 'Updated description.',
            'itunes_duration'    => '25:30',
            'website_content'    => '<p>Updated content.</p>',
        ]);
    }


    // -------------------------------------------------------------------------
    // update — slug is never recalculated
    // -------------------------------------------------------------------------

    public function test_update_does_not_overwrite_slug(): void
    {
        $user = User::factory()->create();
        $show = $this->showForUser($user);
        $episode = PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::created,
            'title'           => '#5 - My Episode',
            'slug'            => 'bob-bloom-show-ep5-my-episode',
        ]);

        $payload = $this->updatePayload($show, [
            'title' => '#5 - My Updated Episode',
            'slug'  => 'bob-bloom-show-ep5-my-episode',
        ]);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $payload)
            ->assertRedirect(route('podcast_episodes.show', $episode));

        // Slug must remain unchanged — it was set by the create wizard
        // and should never be recalculated by the update method.
        $this->assertDatabaseHas('podcast_episodes', [
            'id'    => $episode->id,
            'title' => '#5 - My Updated Episode',
            'slug'  => 'bob-bloom-show-ep5-my-episode',
        ]);
    }

    public function test_update_allows_deliberate_slug_change(): void
    {
        $user = User::factory()->create();
        $show = $this->showForUser($user);
        $episode = PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::created,
            'title'           => '#5 - My Episode',
            'slug'            => 'bob-bloom-show-ep5-my-episode',
        ]);

        $payload = $this->updatePayload($show, [
            'title'          => '#6 - Replacement Episode',
            'slug'           => 'bob-bloom-show-ep6-replacement-episode',
            'itunes_episode' => 6,
        ]);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $payload)
            ->assertRedirect(route('podcast_episodes.show', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'              => $episode->id,
            'title'           => '#6 - Replacement Episode',
            'slug'            => 'bob-bloom-show-ep6-replacement-episode',
            'itunes_episode'  => 6,
        ]);
    }


    // -------------------------------------------------------------------------
    // update — checkbox booleans
    // -------------------------------------------------------------------------

    public function test_update_handles_unchecked_checkboxes_as_false(): void
    {
        $user = User::factory()->create();
        $show = $this->showForUser($user);
        $episode = PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::created,
            'itunes_explicit' => true,
            'itunes_block'    => true,
            'rss_feed_enabled' => true,
            'website_enabled' => true,
        ]);

        // Payload without boolean fields = unchecked checkboxes
        $payload = $this->updatePayload($show);
        unset($payload['itunes_explicit'], $payload['itunes_block'], $payload['rss_feed_enabled'], $payload['website_enabled']);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $payload)
            ->assertRedirect(route('podcast_episodes.show', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'              => $episode->id,
            'itunes_explicit' => false,
            'itunes_block'    => false,
            'rss_feed_enabled' => false,
            'website_enabled' => false,
        ]);
    }

    public function test_update_handles_checked_checkboxes_as_true(): void
    {
        $user = User::factory()->create();
        $show = $this->showForUser($user);
        $episode = PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::created,
            'itunes_explicit' => false,
            'itunes_block'    => false,
            'rss_feed_enabled' => false,
            'website_enabled' => false,
        ]);

        $payload = $this->updatePayload($show, [
            'itunes_explicit'  => '1',
            'itunes_block'     => '1',
            'rss_feed_enabled' => '1',
            'website_enabled'  => '1',
        ]);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $payload)
            ->assertRedirect(route('podcast_episodes.show', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'              => $episode->id,
            'itunes_explicit' => true,
            'itunes_block'    => true,
            'rss_feed_enabled' => true,
            'website_enabled' => true,
        ]);
    }


    // -------------------------------------------------------------------------
    // update — ownership
    // -------------------------------------------------------------------------

    public function test_update_redirects_with_error_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $show    = PodcastShow::factory()->create(['user_id' => $other->id]);
        $episode = PodcastEpisode::factory()->create([
            'user_id'         => $other->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::created,
        ]);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $this->updatePayload($show))
            ->assertRedirect(route('podcast_episodes.index'))
            ->assertSessionHas('error');
    }

    public function test_update_redirects_with_error_when_reassigning_to_another_users_show(): void
    {
        $user      = User::factory()->create();
        $other     = User::factory()->create();
        $myShow    = $this->showForUser($user);
        $theirShow = PodcastShow::factory()->create(['user_id' => $other->id]);
        $episode   = PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $myShow->id,
            'status'          => PodcastEpisodeStatus::created,
        ]);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $this->updatePayload($theirShow))
            ->assertRedirect(route('podcast_episodes.edit', $episode))
            ->assertSessionHas('error');
    }


    // -------------------------------------------------------------------------
    // update — validation
    // -------------------------------------------------------------------------

    public function test_update_validates_required_fields(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), [])
            ->assertSessionHasErrors([
                'podcast_show_id',
                'status',
                'title',
            ]);
    }

    public function test_update_validates_url_fields(): void
    {
        $user = User::factory()->create();
        $show = $this->showForUser($user);
        $episode = PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::created,
        ]);

        $payload = $this->updatePayload($show, [
            'itunes_enclosure_url' => 'not-a-url',
            'itunes_link'          => 'not-a-url',
            'itunes_image'         => 'not-a-url',
            'website_featured_image' => 'not-a-url',
        ]);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $payload)
            ->assertSessionHasErrors([
                'itunes_enclosure_url',
                'itunes_link',
                'itunes_image',
                'website_featured_image',
            ]);
    }

    public function test_update_validates_enum_fields(): void
    {
        $user = User::factory()->create();
        $show = $this->showForUser($user);
        $episode = PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::created,
        ]);

        $payload = $this->updatePayload($show, [
            'status'              => 'invalid_status',
            'itunes_episode_type' => 'invalid_type',
            'itunes_enclosure_type' => 'invalid_type',
        ]);

        $this->actingAs($user)
            ->put(route('podcast_episodes.update', $episode), $payload)
            ->assertSessionHasErrors([
                'status',
                'itunes_episode_type',
                'itunes_enclosure_type',
            ]);
    }


    // -------------------------------------------------------------------------
    // Population methods — unit tests
    // -------------------------------------------------------------------------

    public function test_get_title_returns_form_value_unchanged(): void
    {
        $controller = new PodcastEpisodeUpdateController();
        $show       = PodcastShow::factory()->create();
        $request    = $this->fakeRequest($this->updatePayload($show, [
            'title' => '#5 - My Episode With Special Chars!',
        ]));

        $this->assertEquals('#5 - My Episode With Special Chars!', $controller->get_title($request));
    }

    public function test_get_slug_returns_form_value_when_provided(): void
    {
        $controller = new PodcastEpisodeUpdateController();
        $show       = PodcastShow::factory()->create();
        $episode    = PodcastEpisode::factory()->create([
            'podcast_show_id' => $show->id,
            'slug'            => 'original-slug',
        ]);
        $request = $this->fakeRequest($this->updatePayload($show, [
            'slug' => 'deliberately-changed-slug',
        ]));

        $this->assertEquals('deliberately-changed-slug', $controller->get_slug($request, $episode));
    }

    public function test_get_slug_preserves_existing_when_form_value_is_null(): void
    {
        $controller = new PodcastEpisodeUpdateController();
        $show       = PodcastShow::factory()->create();
        $episode    = PodcastEpisode::factory()->create([
            'podcast_show_id' => $show->id,
            'slug'            => 'original-slug',
        ]);
        $payload = $this->updatePayload($show);
        unset($payload['slug']);
        $request = $this->fakeRequest($payload);

        $this->assertEquals('original-slug', $controller->get_slug($request, $episode));
    }

    public function test_get_itunes_explicit_returns_false_when_unchecked(): void
    {
        $controller = new PodcastEpisodeUpdateController();
        $show       = PodcastShow::factory()->create();
        $payload    = $this->updatePayload($show);
        unset($payload['itunes_explicit']);
        $request = $this->fakeRequest($payload);

        $this->assertFalse($controller->get_itunes_explicit($request));
    }

    public function test_get_itunes_explicit_returns_true_when_checked(): void
    {
        $controller = new PodcastEpisodeUpdateController();
        $show       = PodcastShow::factory()->create();
        $request    = $this->fakeRequest($this->updatePayload($show, [
            'itunes_explicit' => '1',
        ]));

        $this->assertTrue($controller->get_itunes_explicit($request));
    }

    public function test_get_itunes_episode_returns_integer(): void
    {
        $controller = new PodcastEpisodeUpdateController();
        $show       = PodcastShow::factory()->create();
        $request    = $this->fakeRequest($this->updatePayload($show, [
            'itunes_episode' => 42,
        ]));

        $result = $controller->get_itunes_episode($request);
        $this->assertSame(42, $result);
    }
}