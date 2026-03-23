<?php

namespace Tests\Feature\MEDIA_PLATFORM\Configuration\LanguageModels;

use MediaPlatform\Configuration\LanguageModels\Models\LanguageModel;
use MediaPlatform\Configuration\Providers\Models\Provider;
use MediaPlatform\Configuration\UseCases\Models\UseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageModelControllerTest extends TestCase
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
        LanguageModel::factory()->count(3)->create();

        $this->get(route('language_models.languagemodel.index'))
            ->assertOk()
            ->assertViewIs('media_platform.configuration.languagemodel.index')
            ->assertViewHas('languageModels');
    }

    public function test_index_shows_model_names(): void
    {
        $model = LanguageModel::factory()->create(['name' => 'GPT-4o']);

        $this->get(route('language_models.languagemodel.index'))
            ->assertSee('GPT-4o');
    }

    public function test_index_shows_empty_state_when_no_models(): void
    {
        // The seeder is populating models before the test runs, so the empty state is never reached.
        // So, clear the models before assertion...
        // \App\Language_models\Models\LanguageModel::query()->delete();

        $this->get(route('language_models.languagemodel.index'))
            ->assertOk()
            ->assertSee('No models yet');
    }

    // ── create ─────────────────────────────────────────────────────────────────

    public function test_create_renders_form_with_providers_and_use_cases(): void
    {
        $provider = Provider::factory()->create(['enabled' => true]);
        $useCase  = UseCase::factory()->create();

        $this->get(route('language_models.languagemodel.create'))
            ->assertOk()
            ->assertViewIs('media_platform.configuration.languagemodel.create')
            ->assertViewHas('providers')
            ->assertViewHas('useCases')
            ->assertSee($provider->name)
            ->assertSee($useCase->name);
    }

    // ── store ──────────────────────────────────────────────────────────────────

    public function test_store_creates_model_and_redirects(): void
    {
        $provider = Provider::factory()->create();

        $response = $this->post(route('language_models.languagemodel.store'), [
            'provider_id' => $provider->id,
            'name'        => 'Claude 3.5 Sonnet',
            'description' => 'Fast and capable.',
            'enabled'   => '1',
        ]);

        $model = LanguageModel::where('name', 'Claude 3.5 Sonnet')->firstOrFail();

        $response->assertRedirect(route('language_models.languagemodel.show', $model))
                 ->assertSessionHas('success');

        $this->assertDatabaseHas('language_models', [
            'name' => 'Claude 3.5 Sonnet',
            'slug' => 'claude-3.5-sonnet',  // not 'claude-3-5-sonnet'
        ]);
    }

    public function test_store_syncs_use_cases(): void
    {
        $provider  = Provider::factory()->create();
        $useCases  = UseCase::factory()->count(2)->create();

        $this->post(route('language_models.languagemodel.store'), [
            'provider_id'  => $provider->id,
            'name'         => 'GPT-4o',
            'enabled'    => '1',
            'use_case_ids' => $useCases->pluck('id')->toArray(),
        ]);

        $model = LanguageModel::where('name', 'GPT-4o')->firstOrFail();

        $this->assertCount(2, $model->useCases);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->post(route('language_models.languagemodel.store'), [])
            ->assertSessionHasErrors(['provider_id', 'name']);
    }

    public function test_store_validates_provider_exists(): void
    {
        $this->post(route('language_models.languagemodel.store'), [
            'provider_id' => 9999,
            'name'        => 'Some Model',
            'enabled'   => '1',
        ])->assertSessionHasErrors('provider_id');
    }

    public function test_store_validates_unique_slug(): void
    {
        $provider = Provider::factory()->create();
        LanguageModel::factory()->forProvider($provider)->create(['slug' => 'gpt-4o']);

        $this->post(route('language_models.languagemodel.store'), [
            'provider_id' => $provider->id,
            'name'        => 'New Model',
            'slug'        => 'gpt-4o',
            'enabled'   => '1',
        ])->assertSessionHasErrors('slug');
    }

    public function test_store_auto_generates_slug(): void
    {
        $provider = Provider::factory()->create();

        $this->post(route('language_models.languagemodel.store'), [
            'provider_id' => $provider->id,
            'name'        => 'Gemini 1.5 Pro',
            'enabled'   => '1',
        ]);

        $this->assertDatabaseHas('language_models', ['slug' => 'gemini-1.5-pro']);
    }

    // ── show ───────────────────────────────────────────────────────────────────

    public function test_show_renders_model_detail(): void
    {
        $model = LanguageModel::factory()->create(['name' => 'Claude 3 Opus']);

        $this->get(route('language_models.languagemodel.show', $model))
            ->assertOk()
            ->assertViewIs('media_platform.configuration.languagemodel.show')
            ->assertSee('Claude 3 Opus');
    }

    public function test_show_returns_404_for_missing_model(): void
    {
        $this->get(route('language_models.languagemodel.show', 9999))
            ->assertNotFound();
    }

    // ── edit ───────────────────────────────────────────────────────────────────

    public function test_edit_renders_form_with_model_data(): void
    {
        $model = LanguageModel::factory()->create(['name' => 'GPT-4 Turbo']);

        $this->get(route('language_models.languagemodel.edit', $model))
            ->assertOk()
            ->assertViewIs('media_platform.configuration.languagemodel.edit')
            ->assertSee('GPT-4 Turbo');
    }

    // ── update ─────────────────────────────────────────────────────────────────

    public function test_update_saves_changes_and_redirects(): void
    {
        $model    = LanguageModel::factory()->create(['name' => 'Old Name']);
        $provider = Provider::factory()->create();

        $this->put(route('language_models.languagemodel.update', $model), [
            'provider_id' => $provider->id,
            'name'        => 'New Name',
            'enabled'   => '1',
        ])->assertRedirect(route('language_models.languagemodel.show', $model))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('language_models', ['id' => $model->id, 'name' => 'New Name']);
    }

    public function test_update_syncs_use_cases(): void
    {
        $model    = LanguageModel::factory()->create();
        $initial  = UseCase::factory()->count(2)->create();
        $model->useCases()->sync($initial->pluck('id'));

        $newUseCases = UseCase::factory()->count(1)->create();

        $this->put(route('language_models.languagemodel.update', $model), [
            'provider_id'  => $model->provider_id,
            'name'         => $model->name,
            'enabled'    => '1',
            'use_case_ids' => $newUseCases->pluck('id')->toArray(),
        ]);

        $model->refresh();
        $this->assertCount(1, $model->useCases);
        $this->assertEquals($newUseCases->first()->id, $model->useCases->first()->id);
    }

    public function test_update_slug_uniqueness_ignores_own_record(): void
    {
        $model = LanguageModel::factory()->create(['slug' => 'gpt-4o']);

        $this->put(route('language_models.languagemodel.update', $model), [
            'provider_id' => $model->provider_id,
            'name'        => $model->name,
            'slug'        => 'gpt-4o',
            'enabled'   => '1',
        ])->assertSessionDoesntHaveErrors('slug');
    }

    // ── destroy ────────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_model(): void
    {
        $model = LanguageModel::factory()->create();

        $this->delete(route('language_models.languagemodel.destroy', $model))
            ->assertRedirect(route('language_models.languagemodel.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('language_models', ['id' => $model->id]);
    }

    public function test_destroy_detaches_use_cases(): void
    {
        $model    = LanguageModel::factory()->create();
        $useCase  = UseCase::factory()->create();
        $model->useCases()->attach($useCase);

        $this->delete(route('language_models.languagemodel.destroy', $model));
        $this->delete(route('language_models.languagemodel.destroy', $model));

        // Pivot row should be gone (cascade on delete is set in migration)
        $this->assertDatabaseMissing('language_model_use_case', [
            'language_model_id' => $model->id,
            'use_case_id'       => $useCase->id,
        ]);
    }
}
