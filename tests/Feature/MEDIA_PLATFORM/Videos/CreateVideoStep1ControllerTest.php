<?php

namespace Tests\Feature\MEDIA_PLATFORM\Videos;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateVideoStep1ControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_step1_shows_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('videos.create.step1'))
            ->assertOk()
            ->assertSee('Create Video');
    }

    public function test_step1_redirects_unauthenticated_users(): void
    {
        $this->get(route('videos.create.step1'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // store — happy path
    // -------------------------------------------------------------------------

    public function test_step1_stores_data_in_session_and_redirects_to_step2(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('videos.create.step1.store'), [
                'title'          => 'My New Video',
                'description'    => 'A great video about testing.',
                'scheduled_date' => '2026-07-15',
            ])
            ->assertRedirect(route('videos.create.step2'));

        $this->assertEquals('My New Video', session('wizard.create_video.title'));
        $this->assertEquals('A great video about testing.', session('wizard.create_video.description'));
        $this->assertEquals('2026-07-15', session('wizard.create_video.scheduled_date'));
    }

    public function test_step1_validates_scheduled_date_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('videos.create.step1.store'), [
                'title'       => 'My Video',
                'description' => 'Description here.',
            ])
            ->assertSessionHasErrors(['scheduled_date']);
    }

    // -------------------------------------------------------------------------
    // store — validation
    // -------------------------------------------------------------------------

    public function test_step1_validates_title_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('videos.create.step1.store'), [
                'title'       => '',
                'description' => 'Some description.',
            ])
            ->assertSessionHasErrors(['title']);
    }

    public function test_step1_validates_description_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('videos.create.step1.store'), [
                'title'       => 'My Video',
                'description' => '',
            ])
            ->assertSessionHasErrors(['description']);
    }

    public function test_step1_validates_scheduled_date_is_a_valid_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('videos.create.step1.store'), [
                'title'          => 'My Video',
                'description'    => 'Description.',
                'scheduled_date' => 'not-a-date',
            ])
            ->assertSessionHasErrors(['scheduled_date']);
    }

    public function test_step1_validates_title_max_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('videos.create.step1.store'), [
                'title'       => str_repeat('a', 256),
                'description' => 'Description.',
            ])
            ->assertSessionHasErrors(['title']);
    }

    public function test_step1_validates_description_max_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('videos.create.step1.store'), [
                'title'       => 'My Video',
                'description' => str_repeat('a', 5001),
            ])
            ->assertSessionHasErrors(['description']);
    }

    public function test_step1_preserves_old_input_on_validation_failure(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('videos.create.step1.store'), [
                'title'       => '',
                'description' => 'I typed this.',
            ])
            ->assertSessionHasErrors(['title']);

        $this->assertEquals('I typed this.', session()->getOldInput('description'));
    }

    public function test_step1_store_redirects_unauthenticated_users(): void
    {
        $this->post(route('videos.create.step1.store'), [
            'title'       => 'My Video',
            'description' => 'Description.',
        ])
            ->assertRedirect(route('login'));
    }
}