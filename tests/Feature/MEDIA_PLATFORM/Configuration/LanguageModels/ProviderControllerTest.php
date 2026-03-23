<?php

namespace Tests\Feature\MEDIA_PLATFORM\Configuration\LanguageModels;

use MediaPlatform\Configuration\LanguageModels\Models\LanguageModel;
use MediaPlatform\Configuration\Providers\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use Database\Seeders\DatabaseSeeder;

class ProviderControllerTest extends TestCase
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
        Provider::factory()->count(3)->create();

        $this->get(route('language_models.providers.index'))
            ->assertOk()
            ->assertViewIs('media_platform.configuration.providers.index')
            ->assertViewHas('providers');
    }

    public function test_index_shows_provider_names(): void
    {
        $provider = Provider::factory()->create(['name' => 'Anthropic']);

        $this->get(route('language_models.providers.index'))
            ->assertSee('Anthropic');
    }

    public function test_index_shows_empty_state_when_no_providers(): void
    {
        $this->get(route('language_models.providers.index'))
            ->assertOk()
            ->assertSee('No providers yet');
    }

    // ── create ─────────────────────────────────────────────────────────────────

    public function test_create_renders_form(): void
    {
        $this->get(route('language_models.providers.create'))
            ->assertOk()
            ->assertViewIs('media_platform.configuration.providers.create');
    }

    // ── store ──────────────────────────────────────────────────────────────────

    public function test_store_creates_provider_and_redirects(): void
    {
        $data = [
            'name'        => 'Anthropic',
            'description' => 'AI safety company.',
            'website_url' => 'https://anthropic.com',
            'enabled'   => '1',
        ];

        $this->post(route('language_models.providers.store'), $data)
            ->assertRedirect(route('language_models.providers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('providers', [
            'name' => 'Anthropic',
            'slug' => 'anthropic',
        ]);
    }

    public function test_store_auto_generates_slug_from_name(): void
    {
        $this->post(route('language_models.providers.store'), ['name' => 'Open AI Corp', 'enabled' => '1']);

        $this->assertDatabaseHas('providers', ['slug' => 'open-ai-corp']);
    }

    public function test_store_accepts_custom_slug(): void
    {
        $this->post(route('language_models.providers.store'), [
            'name'      => 'OpenAI',
            'slug'      => 'openai',
            'enabled' => '1',
        ]);

        $this->assertDatabaseHas('providers', ['slug' => 'openai']);
    }

    public function test_store_validates_required_name(): void
    {
        $this->post(route('language_models.providers.store'), [])
            ->assertSessionHasErrors('name');
    }

    public function test_store_validates_unique_slug(): void
    {
        Provider::factory()->create(['slug' => 'anthropic']);

        $this->post(route('language_models.providers.store'), ['name' => 'Anthropic 2', 'slug' => 'anthropic', 'enabled' => '1'])
            ->assertSessionHasErrors('slug');
    }

    public function test_store_validates_url_format(): void
    {
        $this->post(route('language_models.providers.store'), [
            'name'        => 'Anthropic',
            'website_url' => 'not-a-url',
            'enabled'   => '1',
        ])->assertSessionHasErrors('website_url');
    }

    // ── show ───────────────────────────────────────────────────────────────────

    public function test_show_renders_provider_detail(): void
    {
        $provider = Provider::factory()->create(['name' => 'Google']);

        $this->get(route('language_models.providers.show', $provider))
            ->assertOk()
            ->assertViewIs('media_platform.configuration.providers.show')
            ->assertSee('Google');
    }

    public function test_show_returns_404_for_missing_provider(): void
    {
        $this->get(route('language_models.providers.show', 9999))
            ->assertNotFound();
    }

    // ── edit ───────────────────────────────────────────────────────────────────

    public function test_edit_renders_form_with_provider_data(): void
    {
        $provider = Provider::factory()->create(['name' => 'OpenAI']);

        $this->get(route('language_models.providers.edit', $provider))
            ->assertOk()
            ->assertViewIs('media_platform.configuration.providers.edit')
            ->assertSee('OpenAI');
    }

    // ── update ─────────────────────────────────────────────────────────────────

    public function test_update_saves_changes_and_redirects(): void
    {
        $provider = Provider::factory()->create(['name' => 'Old Name']);

        $this->put(route('language_models.providers.update', $provider), [
            'name'      => 'New Name',
            'enabled' => '1',
        ])->assertRedirect(route('language_models.providers.show', $provider))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('providers', ['id' => $provider->id, 'name' => 'New Name']);
    }

    public function test_update_slug_uniqueness_ignores_own_record(): void
    {
        $provider = Provider::factory()->create(['name' => 'Anthropic', 'slug' => 'anthropic']);

        // Updating with the same slug should not trigger a unique validation error
        $this->put(route('language_models.providers.update', $provider), [
            'name'      => 'Anthropic',
            'slug'      => 'anthropic',
            'enabled' => '1',
        ])->assertSessionDoesntHaveErrors('slug');
    }

    public function test_update_validates_required_name(): void
    {
        $provider = Provider::factory()->create();

        $this->put(route('language_models.providers.update', $provider), ['name' => '', 'enabled' => '1'])
            ->assertSessionHasErrors('name');
    }

    // ── destroy ────────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_provider(): void
    {
        $provider = Provider::factory()->create();

        $this->delete(route('language_models.providers.destroy', $provider))
            ->assertRedirect(route('language_models.providers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('providers', ['id' => $provider->id]);
    }

    public function test_destroy_refuses_to_delete_provider_with_models(): void
    {
        $provider = Provider::factory()->create();
        LanguageModel::factory()->forProvider($provider)->create();

        $this->delete(route('language_models.providers.destroy', $provider))
            ->assertRedirect(route('language_models.providers.show', $provider))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('providers', ['id' => $provider->id]);
    }
}
