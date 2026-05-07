<?php

namespace Tests\Feature\MEDIA_PLATFORM\Videos;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Videos\Controllers\CreateVideoStep2Controller;
use MediaPlatform\Videos\Enums\VideoStatus;
use MediaPlatform\Videos\Models\Video;
use Tests\TestCase;

class CreateVideoStep2ControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Set wizard session data as if Step 1 was completed.
     */
    private function withStep1Session(array $overrides = []): array
    {
        return array_merge([
            'wizard.create_video.title'          => 'My Test Video',
            'wizard.create_video.description'    => 'A test video description.',
            'wizard.create_video.scheduled_date' => '2026-07-15',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // store — happy path
    // -------------------------------------------------------------------------

    public function test_store_creates_video_and_redirects_to_show(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession($this->withStep1Session())
            ->get(route('videos.create.step2'));

        $video = Video::where('title', 'My Test Video')->first();

        $this->assertNotNull($video);
        $this->assertEquals($user->id, $video->user_id);
        $this->assertEquals('My Test Video', $video->title);
        $this->assertEquals('A test video description.', $video->description);
        $this->assertEquals('2026-07-15', $video->scheduled_date->format('Y-m-d'));
        $this->assertEquals('my-test-video', $video->slug);
        $this->assertEquals(VideoStatus::not_published_to_youtube, $video->status);
        $this->assertEquals('MY TEST VIDEO, JULY 15, 2026', $video->youtube_title);
        $this->assertStringContainsString('A test video description.', $video->youtube_description);
        $this->assertStringContainsString('DISCLAIMER', $video->youtube_description);
        $this->assertStringContainsString('0:00 - Welcome', $video->youtube_chapters);
        $this->assertNotNull($video->youtube_url);

        $response->assertRedirect(route('videos.show', $video))
            ->assertSessionHas('success');
    }

    public function test_store_clears_wizard_session_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->withStep1Session())
            ->get(route('videos.create.step2'));

        $this->assertNull(session('wizard.create_video.title'));
        $this->assertNull(session('wizard.create_video.description'));
        $this->assertNull(session('wizard.create_video.scheduled_date'));
    }

    public function test_store_handles_null_scheduled_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->withStep1Session([
                'wizard.create_video.scheduled_date' => null,
            ]))
            ->get(route('videos.create.step2'));

        $video = Video::where('title', 'My Test Video')->first();

        $this->assertNotNull($video);
        $this->assertNull($video->scheduled_date);
    }

    public function test_store_trims_whitespace_from_title_and_description(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->withStep1Session([
                'wizard.create_video.title'       => '  Padded Title  ',
                'wizard.create_video.description' => '  Padded description.  ',
            ]))
            ->get(route('videos.create.step2'));

        $video = Video::where('slug', 'padded-title')->first();

        $this->assertNotNull($video);
        $this->assertEquals('Padded Title', $video->title);
        $this->assertEquals('Padded description.', $video->description);
    }

    // -------------------------------------------------------------------------
    // store — missing session
    // -------------------------------------------------------------------------

    public function test_store_redirects_to_step1_when_session_is_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('videos.create.step2'))
            ->assertRedirect(route('videos.create.step1'));

        $this->assertDatabaseCount('videos', 0);
    }

    // -------------------------------------------------------------------------
    // store — auth
    // -------------------------------------------------------------------------

    public function test_store_redirects_unauthenticated_users(): void
    {
        $this->get(route('videos.create.step2'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Population methods (unit tests)
    // -------------------------------------------------------------------------

    public function test_get_status_returns_not_published(): void
    {
        $controller = new CreateVideoStep2Controller();

        $this->assertEquals(VideoStatus::not_published_to_youtube->value, $controller->get_status());
    }

    public function test_get_youtube_url_returns_placeholder_message(): void
    {
        $controller = new CreateVideoStep2Controller();

        $this->assertNotNull($controller->get_youtube_url());
        $this->assertStringContainsString('PLEASE ENTER', $controller->get_youtube_url());
    }

    public function test_get_youtube_chapters_contains_chapter_markers(): void
    {
        $controller = new CreateVideoStep2Controller();

        $chapters = $controller->get_youtube_chapters();

        $this->assertStringContainsString('0:00 - Welcome', $chapters);
        $this->assertStringContainsString('0:11 - Opening', $chapters);
        $this->assertStringContainsString('2:44 - Closing', $chapters);
    }
}