<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PreProduction\CreateEpisode;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PreProduction\CreateEpisode\Controllers\Step3Controller;
use Tests\TestCase;

class Step3ControllerTest extends TestCase
{
    use RefreshDatabase;


    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a minimum valid Step 2 form payload.
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title'           => '#5 - My Test Episode',
            'itunes_episode'  => 5,
            'scheduled_date'  => '2026-06-01',
            'website_content' => '<p>This is my episode description.</p>',
        ], $overrides);
    }

    /**
     * Create a show with all fields the wizard population methods depend on.
     */
    private function showForWizard(User $user, array $overrides = []): PodcastShow
    {
        return PodcastShow::factory()->create(array_merge([
            'user_id'                => $user->id,
            'title'                  => 'The Bob Bloom Show',
            'slug'                   => 'bob-bloom-show',
            'itunes_link'            => 'https://bobbloomshow.com',
            'storage_audio_files_url'=> 'https://audio.example.com/bobbloomshow',
        ], $overrides));
    }

    /**
     * Post to Step 2 store with session and payload.
     */
    private function postStep2(User $user, PodcastShow $show, array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)
            ->withSession(['wizard.create_episode.podcast_show_id' => $show->id])
            ->post(route('pre_production_create_podcast_episode.step2.store'), $payload ?: $this->validPayload());
    }

    /**
     * Make a fake Request with given input for unit-testing population methods.
     */
    private function fakeRequest(array $input): Request
    {
        return Request::create('/', 'POST', $input);
    }


    // -------------------------------------------------------------------------
    // store — routing and session
    // -------------------------------------------------------------------------

    public function test_store_redirects_unauthenticated_user(): void
    {
        $this->post(route('pre_production_create_podcast_episode.step2.store'), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_store_redirects_to_step1_when_session_is_missing(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('pre_production_create_podcast_episode.step2.store'), $this->validPayload())
            ->assertRedirect(route('pre_production_create_podcast_episode.step1'));
    }

    public function test_store_redirects_to_step1_with_error_when_show_belongs_to_another_user(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $show  = PodcastShow::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->withSession(['wizard.create_episode.podcast_show_id' => $show->id])
            ->post(route('pre_production_create_podcast_episode.step2.store'), $this->validPayload())
            ->assertRedirect(route('pre_production_create_podcast_episode.step1'))
            ->assertSessionHas('error');
    }

    public function test_store_clears_session_after_successful_save(): void
    {
        $user = User::factory()->create();
        $show = $this->showForWizard($user);

        $this->postStep2($user, $show);

        $this->assertNull(session('wizard.create_episode.podcast_show_id'));
    }

    public function test_store_redirects_to_episode_show_view_on_success(): void
    {
        $user = User::factory()->create();
        $show = $this->showForWizard($user);

        $this->postStep2($user, $show)
            ->assertRedirect();
    }


    // -------------------------------------------------------------------------
    // store — validation
    // -------------------------------------------------------------------------

    public function test_store_validates_title_is_required(): void
    {
        $user = User::factory()->create();
        $show = $this->showForWizard($user);

        $this->actingAs($user)
            ->withSession(['wizard.create_episode.podcast_show_id' => $show->id])
            ->post(route('pre_production_create_podcast_episode.step2.store'), $this->validPayload(['title' => '']))
            ->assertSessionHasErrors(['title']);
    }

    public function test_store_validates_itunes_episode_is_required(): void
    {
        $user = User::factory()->create();
        $show = $this->showForWizard($user);

        $this->actingAs($user)
            ->withSession(['wizard.create_episode.podcast_show_id' => $show->id])
            ->post(route('pre_production_create_podcast_episode.step2.store'), $this->validPayload(['itunes_episode' => '']))
            ->assertSessionHasErrors(['itunes_episode']);
    }

    public function test_store_validates_website_content_is_required(): void
    {
        $user = User::factory()->create();
        $show = $this->showForWizard($user);

        $this->actingAs($user)
            ->withSession(['wizard.create_episode.podcast_show_id' => $show->id])
            ->post(route('pre_production_create_podcast_episode.step2.store'), $this->validPayload(['website_content' => '']))
            ->assertSessionHasErrors(['website_content']);
    }

    public function test_store_validates_website_content_max_10000_characters(): void
    {
        $user = User::factory()->create();
        $show = $this->showForWizard($user);

        $this->actingAs($user)
            ->withSession(['wizard.create_episode.podcast_show_id' => $show->id])
            ->post(route('pre_production_create_podcast_episode.step2.store'), $this->validPayload([
                'website_content' => str_repeat('a', 10001),
            ]))
            ->assertSessionHasErrors(['website_content']);
    }


    // -------------------------------------------------------------------------
    // store — database persistence
    // -------------------------------------------------------------------------

    public function test_store_creates_episode_assigned_to_authenticated_user(): void
    {
        $user = User::factory()->create();
        $show = $this->showForWizard($user);

        $this->postStep2($user, $show);

        $this->assertDatabaseHas('podcast_episodes', [
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
        ]);
    }

    public function test_store_persists_correct_title(): void
    {
        $user = User::factory()->create();
        $show = $this->showForWizard($user);

        $this->postStep2($user, $show, $this->validPayload(['title' => '#5 - My Test Episode', 'itunes_episode' => 5]));

        $this->assertDatabaseHas('podcast_episodes', ['title' => '#5 - My Test Episode']);
    }

    public function test_store_persists_correct_itunes_episode_number(): void
    {
        $user = User::factory()->create();
        $show = $this->showForWizard($user);

        $this->postStep2($user, $show, $this->validPayload(['itunes_episode' => 7, 'title' => '#7 - Test']));

        $this->assertDatabaseHas('podcast_episodes', ['itunes_episode' => 7]);
    }

    public function test_store_persists_created_status(): void
    {
        $user = User::factory()->create();
        $show = $this->showForWizard($user);

        $this->postStep2($user, $show);

        $this->assertDatabaseHas('podcast_episodes', [
            'status' => PodcastEpisodeStatus::created->value,
        ]);
    }

    public function test_store_sets_rss_feed_enabled_to_false(): void
    {
        $user = User::factory()->create();
        $show = $this->showForWizard($user);

        $this->postStep2($user, $show);

        $this->assertDatabaseHas('podcast_episodes', ['rss_feed_enabled' => false]);
    }

    public function test_store_sets_website_enabled_to_false(): void
    {
        $user = User::factory()->create();
        $show = $this->showForWizard($user);

        $this->postStep2($user, $show);

        $this->assertDatabaseHas('podcast_episodes', ['website_enabled' => false]);
    }


    // -------------------------------------------------------------------------
    // get_title()
    // -------------------------------------------------------------------------

    public function test_get_title_preserves_existing_prefix(): void
    {
        $controller = new Step3Controller();
        $request    = $this->fakeRequest(['title' => '#5 - My Episode', 'itunes_episode' => 5]);

        $this->assertEquals('#5 - My Episode', $controller->get_title($request));
    }

    public function test_get_title_prepends_prefix_when_missing(): void
    {
        $controller = new Step3Controller();
        $request    = $this->fakeRequest(['title' => 'My Episode', 'itunes_episode' => 5]);

        $this->assertEquals('#5 - My Episode', $controller->get_title($request));
    }

    public function test_get_title_truncates_to_163_characters(): void
    {
        $controller = new Step3Controller();
        $longTitle  = '#5 - ' . str_repeat('a', 200);
        $request    = $this->fakeRequest(['title' => $longTitle, 'itunes_episode' => 5]);

        $this->assertEquals(163, strlen($controller->get_title($request)));
    }


    // -------------------------------------------------------------------------
    // get_slug()
    // -------------------------------------------------------------------------

    public function test_get_slug_generates_correct_slug(): void
    {
        $controller = new Step3Controller();
        $show       = PodcastShow::factory()->create(['slug' => 'bob-bloom-show']);
        $request    = $this->fakeRequest(['title' => '#5 - My Test Episode', 'itunes_episode' => 5]);

        $this->assertEquals('bob-bloom-show-ep5-my-test-episode', $controller->get_slug($request, $show));
    }

    public function test_get_slug_strips_special_characters(): void
    {
        $controller = new Step3Controller();
        $show       = PodcastShow::factory()->create(['slug' => 'bob-bloom-show']);
        $request    = $this->fakeRequest(['title' => "#5 - It's \"Great\", Really!", 'itunes_episode' => 5]);

        $slug = $controller->get_slug($request, $show);

        $this->assertStringNotContainsString("'", $slug);
        $this->assertStringNotContainsString('"', $slug);
        $this->assertStringNotContainsString(',', $slug);
        $this->assertStringNotContainsString('!', $slug);
    }

    public function test_get_slug_is_truncated_to_100_characters(): void
    {
        $controller = new Step3Controller();
        $show       = PodcastShow::factory()->create(['slug' => 'bob-bloom-show']);
        $request    = $this->fakeRequest(['title' => '#5 - ' . str_repeat('a ', 60), 'itunes_episode' => 5]);

        $this->assertLessThanOrEqual(100, strlen($controller->get_slug($request, $show)));
    }


    // -------------------------------------------------------------------------
    // normalise_show_title()
    // -------------------------------------------------------------------------

    public function test_normalise_show_title_strips_the_prefix(): void
    {
        $controller = new Step3Controller();
        $show       = PodcastShow::factory()->make(['title' => 'The Bob Bloom Show']);

        $this->assertEquals('bobbloomshow', $controller->normalise_show_title($show));
    }

    public function test_normalise_show_title_lowercases_and_removes_spaces(): void
    {
        $controller = new Step3Controller();
        $show       = PodcastShow::factory()->make(['title' => 'PHP Serverless News']);

        $this->assertEquals('phpserverlessnews', $controller->normalise_show_title($show));
    }


    // -------------------------------------------------------------------------
    // get_itunes_enclosure_url()
    // -------------------------------------------------------------------------

    public function test_get_itunes_enclosure_url_generates_correct_url(): void
    {
        $controller = new Step3Controller();
        $show       = PodcastShow::factory()->make([
            'title'                   => 'The Bob Bloom Show',
            'slug'                    => 'bob-bloom-show',
            'storage_audio_files_url' => 'https://audio.example.com/bobbloomshow',
            'itunes_link'             => 'https://bobbloomshow.com',
        ]);
        $request = $this->fakeRequest(['title' => '#5 - My Episode', 'itunes_episode' => 5]);

        $this->assertEquals(
            'https://audio.example.com/bobbloomshow/bobbloomshow5.mp3',
            $controller->get_itunes_enclosure_url($request, $show)
        );
    }


    // -------------------------------------------------------------------------
    // get_itunes_guid()
    // -------------------------------------------------------------------------

    public function test_get_itunes_guid_matches_expected_format(): void
    {
        $controller = new Step3Controller();
        $guid       = $controller->get_itunes_guid();

        // Expected format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
        $this->assertMatchesRegularExpression(
            '/^[a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{12}$/',
            $guid
        );
    }

    public function test_get_itunes_guid_generates_unique_values(): void
    {
        $controller = new Step3Controller();

        $this->assertNotEquals($controller->get_itunes_guid(), $controller->get_itunes_guid());
    }


    // -------------------------------------------------------------------------
    // get_itunes_pubdate()
    // -------------------------------------------------------------------------

    public function test_get_itunes_pubdate_formats_correctly(): void
    {
        $controller = new Step3Controller();
        $request    = $this->fakeRequest(['scheduled_date' => '2026-06-01', 'itunes_episode' => 1]);

        $this->assertEquals('2026-06-01 00:00:00', $controller->get_itunes_pubdate($request));
    }

    public function test_get_itunes_pubdate_defaults_to_today_when_date_is_null(): void
    {
        $controller = new Step3Controller();
        $request    = $this->fakeRequest(['scheduled_date' => null, 'itunes_episode' => 1]);

        $this->assertEquals(now()->toDateString() . ' 00:00:00', $controller->get_itunes_pubdate($request));
    }


    // -------------------------------------------------------------------------
    // get_itunes_itunestitle_tag() and get_itunes_subtitle()
    // -------------------------------------------------------------------------

    public function test_get_itunes_itunestitle_tag_strips_episode_prefix(): void
    {
        $controller = new Step3Controller();
        $request    = $this->fakeRequest(['title' => '#5 - My Episode Title', 'itunes_episode' => 5]);

        $this->assertEquals('My Episode Title', $controller->get_itunes_itunestitle_tag($request));
    }

    public function test_get_itunes_subtitle_strips_episode_prefix(): void
    {
        $controller = new Step3Controller();
        $request    = $this->fakeRequest(['title' => '#5 - My Episode Title', 'itunes_episode' => 5]);

        $this->assertEquals('My Episode Title', $controller->get_itunes_subtitle($request));
    }


    // -------------------------------------------------------------------------
    // get_itunes_link()
    // -------------------------------------------------------------------------

    public function test_get_itunes_link_generates_correct_url(): void
    {
        $controller = new Step3Controller();
        $show       = PodcastShow::factory()->make([
            'title'                   => 'The Bob Bloom Show',
            'slug'                    => 'bob-bloom-show',
            'itunes_link'             => 'https://bobbloomshow.com',
            'storage_audio_files_url' => 'https://audio.example.com/bobbloomshow',
        ]);
        $request = $this->fakeRequest(['title' => '#5 - My Episode', 'itunes_episode' => 5]);

        $this->assertEquals(
            'https://bobbloomshow.com/episode/bob-bloom-show-ep5-my-episode',
            $controller->get_itunes_link($request, $show)
        );
    }


    // -------------------------------------------------------------------------
    // get_website_content()
    // -------------------------------------------------------------------------

    public function test_get_website_content_strips_disallowed_tags(): void
    {
        $controller = new Step3Controller();
        $request    = $this->fakeRequest([
            'website_content' => '<p>Good content</p><script>alert("bad")</script>',
            'itunes_episode'  => 1,
        ]);

        $content = $controller->get_website_content($request);

        $this->assertStringContainsString('<p>Good content</p>', $content);
        $this->assertStringNotContainsString('<script>', $content);
    }

    public function test_get_website_content_preserves_allowed_tags(): void
    {
        $controller = new Step3Controller();
        $input      = '<p>Text</p><ul><li><a href="#">Link</a></li></ul>';
        $request    = $this->fakeRequest(['website_content' => $input, 'itunes_episode' => 1]);

        $this->assertEquals($input, $controller->get_website_content($request));
    }


    // -------------------------------------------------------------------------
    // get_website_attribution()
    // -------------------------------------------------------------------------

    public function test_get_website_attribution_throws_for_unknown_show(): void
    {
        $controller = new Step3Controller();
        $show       = PodcastShow::factory()->make(['title' => 'Unknown Show', 'id' => 999]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Unknown Show/');

        $controller->get_website_attribution($show);
    }

    public function test_get_website_attribution_returns_string_for_each_active_show(): void
    {
        $controller = new Step3Controller();

        $shows = [
            'The Bob Bloom Show',
            'The Bob Bloom Interviews',
            'PHP Serverless News',
            'PHP Serverless Profiles',
            'PHP Serverless Project Updates',
        ];

        foreach ($shows as $title) {
            $show   = PodcastShow::factory()->make(['title' => $title]);
            $result = $controller->get_website_attribution($show);

            $this->assertIsString($result, "Attribution for '{$title}' should be a string.");
        }
    }
}