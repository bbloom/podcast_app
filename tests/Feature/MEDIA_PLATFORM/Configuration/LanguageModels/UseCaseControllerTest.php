<?php

namespace Tests\Feature\MEDIA_PLATFORM\Configuration\LanguageModels;

use MediaPlatform\Configuration\LanguageModels\Models\LanguageModel;
use MediaPlatform\Configuration\UseCases\Models\UseCase;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UseCaseControllerTest extends TestCase
{
    use RefreshDatabase;


    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    // ── index ──────────────────────────────────────────────────────────────────

    public function test_index_renders_successfully(): void
    {
        $this->get(route('language_models.usecases.index'))
            ->assertOk()
            ->assertViewIs('media_platform.configuration.usecases.index')
            ->assertViewHas('useCases');
    }

    public function test_index_shows_use_case_names(): void
    {
        $useCase = UseCase::factory()->create(['name' => 'Vision']);

        $this->get(route('language_models.usecases.index'))
            ->assertSee('Vision');
    }

    public function test_index_shows_empty_state_when_no_use_cases(): void
    {
        UseCase::query()->delete();

        $this->get(route('language_models.usecases.index'))
            ->assertOk()
            ->assertSee('No use cases yet');
    }

    // ── create ─────────────────────────────────────────────────────────────────

    public function test_create_renders_form(): void
    {
        $this->get(route('language_models.usecases.create'))
            ->assertOk()
            ->assertViewIs('media_platform.configuration.usecases.create');
    }

    // ── store ──────────────────────────────────────────────────────────────────

    public function test_store_creates_use_case_and_redirects(): void
    {
        $data = [
            'name'        => 'Audio Transcription',
            'description' => 'Speech to text tasks.',
        ];

        $this->post(route('language_models.usecases.store'), $data)
            ->assertRedirect(route('language_models.usecases.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('use_cases', [
            'name' => 'Audio Transcription',
            'slug' => 'audio-transcription',
        ]);
    }

    public function test_store_auto_generates_slug_from_name(): void
    {
        $this->post(route('language_models.usecases.store'), ['name' => 'Function Calling']);

        $this->assertDatabaseHas('use_cases', ['slug' => 'function-calling']);
    }

    public function test_store_accepts_custom_slug(): void
    {
        $this->post(route('language_models.usecases.store'), [
            'name' => 'Chat',
            'slug' => 'chat-completion',
        ]);

        $this->assertDatabaseHas('use_cases', ['slug' => 'chat-completion']);
    }

    public function test_store_validates_required_name(): void
    {
        $this->post(route('language_models.usecases.store'), [])
            ->assertSessionHasErrors('name');
    }

    public function test_store_validates_unique_slug(): void
    {
        UseCase::factory()->create(['slug' => 'chat']);

        $this->post(route('language_models.usecases.store'), [
            'name' => 'Another Chat',
            'slug' => 'chat',
        ])->assertSessionHasErrors('slug');
    }

    // ── show ───────────────────────────────────────────────────────────────────

    public function test_show_renders_use_case_detail(): void
    {
        $useCase = UseCase::factory()->create(['name' => 'Embedding']);

        $this->get(route('language_models.usecases.show', $useCase))
            ->assertOk()
            ->assertViewIs('media_platform.configuration.usecases.show')
            ->assertSee('Embedding');
    }

    public function test_show_returns_404_for_missing_use_case(): void
    {
        $this->get(route('language_models.usecases.show', 9999))
            ->assertNotFound();
    }

    // ── edit ───────────────────────────────────────────────────────────────────

    public function test_edit_renders_form_with_use_case_data(): void
    {
        $useCase = UseCase::factory()->create(['name' => 'Vision']);

        $this->get(route('language_models.usecases.edit', $useCase))
            ->assertOk()
            ->assertViewIs('media_platform.configuration.usecases.edit')
            ->assertSee('Vision');
    }

    // ── update ─────────────────────────────────────────────────────────────────

    public function test_update_saves_changes_and_redirects(): void
    {
        $useCase = UseCase::factory()->create(['name' => 'Old Name']);

        $this->put(route('language_models.usecases.update', $useCase), [
            'name' => 'New Name',
        ])->assertRedirect(route('language_models.usecases.show', $useCase))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('use_cases', ['id' => $useCase->id, 'name' => 'New Name']);
    }

    public function test_update_slug_uniqueness_ignores_own_record(): void
    {
        $useCase = UseCase::factory()->create(['name' => 'Chat', 'slug' => 'chat']);

        $this->put(route('language_models.usecases.update', $useCase), [
            'name' => 'Chat',
            'slug' => 'chat',
        ])->assertSessionDoesntHaveErrors('slug');
    }

    public function test_update_validates_required_name(): void
    {
        $useCase = UseCase::factory()->create();

        $this->put(route('language_models.usecases.update', $useCase), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    // ── destroy ────────────────────────────────────────────────────────────────

    public function test_destroy_deletes_use_case_and_redirects(): void
    {
        $useCase = UseCase::factory()->create();

        $this->delete(route('language_models.usecases.destroy', $useCase))
            ->assertRedirect(route('language_models.usecases.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('use_cases', ['id' => $useCase->id]);
    }

    public function test_destroy_detaches_from_language_models(): void
    {
        $useCase      = UseCase::factory()->create();
        $languageModel = LanguageModel::factory()->create();
        $languageModel->useCases()->attach($useCase);

        $this->delete(route('language_models.usecases.destroy', $useCase));

        $this->assertDatabaseMissing('language_model_use_case', [
            'use_case_id' => $useCase->id,
        ]);
    }
}
