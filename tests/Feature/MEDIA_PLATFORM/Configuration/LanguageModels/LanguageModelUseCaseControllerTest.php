<?php

namespace Tests\Feature\MEDIA_PLATFORM\Configuration\LanguageModels;

use MediaPlatform\Configuration\LanguageModels\Models\LanguageModel;
use MediaPlatform\Configuration\UseCases\Models\UseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LanguageModelUseCaseControllerTest
 *
 * TEST GROUPS
 * ───────────
 *   1. show() — availableUseCases and digest-processing holder passed to view
 *   2. attach — happy path
 *   3. attach — validation
 *   4. attach — duplicate guard
 *   5. attach — digest-processing exclusive swap (enabled model displaced)
 *   6. attach — digest-processing no swap (no other enabled holder)
 *   7. attach — digest-processing no swap (only disabled holder)
 *   8. attach — digest-processing no swap (attaching model is disabled)
 *   9. detach — happy path
 *  10. detach — 404 when not attached
 *  11. edit form — enabled field locked when digest-processing holder
 *  12. update — enabled forced true when digest-processing holder
 *  13. auth — guests are redirected
 */
class LanguageModelUseCaseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeContentSummarisationUseCase(): UseCase
    {
        return UseCase::factory()->create([
            'name' => 'Content Summarisation',
            'slug' => 'digest-processing',
        ]);
    }

    // =========================================================================
    // GROUP 1: show() — view data
    // =========================================================================

    public function test_show_passes_available_use_cases_excluding_attached(): void
    {
        $model    = LanguageModel::factory()->create();
        $attached = UseCase::factory()->create(['name' => 'Summarisation']);
        $free     = UseCase::factory()->create(['name' => 'Translation']);
        $model->useCases()->attach($attached);

        $response = $this->get(route('language_models.languagemodel.show', $model));

        $response->assertOk();
        $available = $response->viewData('availableUseCases');
        $this->assertTrue($available->contains('id', $free->id));
        $this->assertFalse($available->contains('id', $attached->id));
    }

    public function test_show_passes_content_summarisation_holder_when_available_to_attach(): void
    {
        $holder  = LanguageModel::factory()->create(['enabled' => true]);
        $target  = LanguageModel::factory()->create(['enabled' => true]);
        $useCase = $this->makeContentSummarisationUseCase();
        $holder->useCases()->attach($useCase);

        $response = $this->get(route('language_models.languagemodel.show', $target));

        $response->assertOk();
        $this->assertEquals($holder->id, $response->viewData('contentSummarisationHolder')->id);
    }

    public function test_show_passes_null_holder_when_no_enabled_model_holds_content_summarisation(): void
    {
        $target  = LanguageModel::factory()->create(['enabled' => true]);
        $useCase = $this->makeContentSummarisationUseCase();

        $response = $this->get(route('language_models.languagemodel.show', $target));

        $response->assertOk();
        $this->assertNull($response->viewData('contentSummarisationHolder'));
    }

    public function test_show_marks_model_as_content_summarisation_holder(): void
    {
        $model   = LanguageModel::factory()->create(['enabled' => true]);
        $useCase = $this->makeContentSummarisationUseCase();
        $model->useCases()->attach($useCase);

        $response = $this->get(route('language_models.languagemodel.show', $model));

        $response->assertOk();
        $this->assertTrue($response->viewData('isContentSummarisationHolder'));
    }

    // =========================================================================
    // GROUP 2: attach — happy path
    // =========================================================================

    public function test_attach_creates_pivot_row_and_redirects(): void
    {
        $model   = LanguageModel::factory()->create();
        $useCase = UseCase::factory()->create();

        $this->post(route('language_models.languagemodel.use_cases.attach', $model), [
            'use_case_id' => $useCase->id,
        ])
            ->assertRedirect(route('language_models.languagemodel.show', $model))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('language_model_use_case', [
            'language_model_id' => $model->id,
            'use_case_id'       => $useCase->id,
        ]);
    }

    public function test_attach_success_message_contains_use_case_name(): void
    {
        $model   = LanguageModel::factory()->create();
        $useCase = UseCase::factory()->create(['name' => 'Translation']);

        $this->post(route('language_models.languagemodel.use_cases.attach', $model), [
            'use_case_id' => $useCase->id,
        ])->assertSessionHas('success', fn ($msg) => str_contains($msg, 'Translation'));
    }

    // =========================================================================
    // GROUP 3: attach — validation
    // =========================================================================

    public function test_attach_requires_use_case_id(): void
    {
        $model = LanguageModel::factory()->create();
        $this->post(route('language_models.languagemodel.use_cases.attach', $model), [])
            ->assertSessionHasErrors('use_case_id');
    }

    public function test_attach_rejects_nonexistent_use_case_id(): void
    {
        $model = LanguageModel::factory()->create();
        $this->post(route('language_models.languagemodel.use_cases.attach', $model), [
            'use_case_id' => 9999,
        ])->assertSessionHasErrors('use_case_id');
    }

    // =========================================================================
    // GROUP 4: attach — duplicate guard
    // =========================================================================

    public function test_attach_rejects_already_attached_use_case(): void
    {
        $model   = LanguageModel::factory()->create();
        $useCase = UseCase::factory()->create();
        $model->useCases()->attach($useCase);

        $this->post(route('language_models.languagemodel.use_cases.attach', $model), [
            'use_case_id' => $useCase->id,
        ])->assertSessionHasErrors('use_case_id');

        $this->assertCount(1, $model->useCases()->get());
    }

    // =========================================================================
    // GROUP 5: attach — digest-processing exclusive swap
    // =========================================================================

    public function test_attaching_content_summarisation_displaces_existing_enabled_holder(): void
    {
        $oldModel = LanguageModel::factory()->create(['enabled' => true, 'name' => 'Old Model']);
        $newModel = LanguageModel::factory()->create(['enabled' => true, 'name' => 'New Model']);
        $useCase  = $this->makeContentSummarisationUseCase();
        $oldModel->useCases()->attach($useCase);

        $this->post(route('language_models.languagemodel.use_cases.attach', $newModel), [
            'use_case_id' => $useCase->id,
        ])->assertRedirect(route('language_models.languagemodel.show', $newModel))
          ->assertSessionHas('success');

        // New model now holds it
        $this->assertDatabaseHas('language_model_use_case', [
            'language_model_id' => $newModel->id,
            'use_case_id'       => $useCase->id,
        ]);

        // Old model no longer holds it
        $this->assertDatabaseMissing('language_model_use_case', [
            'language_model_id' => $oldModel->id,
            'use_case_id'       => $useCase->id,
        ]);
    }

    public function test_swap_flash_message_names_both_models(): void
    {
        $oldModel = LanguageModel::factory()->create(['enabled' => true, 'name' => 'Gemini Flash']);
        $newModel = LanguageModel::factory()->create(['enabled' => true, 'name' => 'Bobby Plus 3']);
        $useCase  = $this->makeContentSummarisationUseCase();
        $oldModel->useCases()->attach($useCase);

        $this->post(route('language_models.languagemodel.use_cases.attach', $newModel), [
            'use_case_id' => $useCase->id,
        ])->assertSessionHas('success', function ($msg) {
            return str_contains($msg, 'Gemini Flash') && str_contains($msg, 'Bobby Plus 3');
        });
    }

    // =========================================================================
    // GROUP 6: attach — digest-processing no swap (no other enabled holder)
    // =========================================================================

    public function test_attaching_content_summarisation_with_no_existing_holder_attaches_normally(): void
    {
        $model   = LanguageModel::factory()->create(['enabled' => true]);
        $useCase = $this->makeContentSummarisationUseCase();

        $this->post(route('language_models.languagemodel.use_cases.attach', $model), [
            'use_case_id' => $useCase->id,
        ])->assertRedirect(route('language_models.languagemodel.show', $model))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('language_model_use_case', [
            'language_model_id' => $model->id,
            'use_case_id'       => $useCase->id,
        ]);
    }

    // =========================================================================
    // GROUP 7: attach — digest-processing no swap (only disabled holder)
    // =========================================================================

    public function test_attaching_content_summarisation_does_not_displace_disabled_holder(): void
    {
        $disabledHolder = LanguageModel::factory()->create(['enabled' => false]);
        $newModel       = LanguageModel::factory()->create(['enabled' => true]);
        $useCase        = $this->makeContentSummarisationUseCase();
        $disabledHolder->useCases()->attach($useCase);

        $this->post(route('language_models.languagemodel.use_cases.attach', $newModel), [
            'use_case_id' => $useCase->id,
        ])->assertRedirect(route('language_models.languagemodel.show', $newModel));

        // Both now have it (disabled holder was not detached)
        $this->assertDatabaseHas('language_model_use_case', [
            'language_model_id' => $disabledHolder->id,
            'use_case_id'       => $useCase->id,
        ]);
        $this->assertDatabaseHas('language_model_use_case', [
            'language_model_id' => $newModel->id,
            'use_case_id'       => $useCase->id,
        ]);
    }

    // =========================================================================
    // GROUP 8: attach — digest-processing no swap (attaching model is disabled)
    // =========================================================================

    public function test_attaching_content_summarisation_to_disabled_model_does_not_trigger_swap(): void
    {
        $enabledHolder  = LanguageModel::factory()->create(['enabled' => true]);
        $disabledTarget = LanguageModel::factory()->create(['enabled' => false]);
        $useCase        = $this->makeContentSummarisationUseCase();
        $enabledHolder->useCases()->attach($useCase);

        $this->post(route('language_models.languagemodel.use_cases.attach', $disabledTarget), [
            'use_case_id' => $useCase->id,
        ])->assertRedirect(route('language_models.languagemodel.show', $disabledTarget));

        // Enabled holder was NOT displaced
        $this->assertDatabaseHas('language_model_use_case', [
            'language_model_id' => $enabledHolder->id,
            'use_case_id'       => $useCase->id,
        ]);
        $this->assertDatabaseHas('language_model_use_case', [
            'language_model_id' => $disabledTarget->id,
            'use_case_id'       => $useCase->id,
        ]);
    }

    // =========================================================================
    // GROUP 9: detach — happy path
    // =========================================================================

    public function test_detach_removes_pivot_row_and_redirects(): void
    {
        $model   = LanguageModel::factory()->create();
        $useCase = UseCase::factory()->create();
        $model->useCases()->attach($useCase);

        $this->delete(route('language_models.languagemodel.use_cases.detach', [$model, $useCase]))
            ->assertRedirect(route('language_models.languagemodel.show', $model))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('language_model_use_case', [
            'language_model_id' => $model->id,
            'use_case_id'       => $useCase->id,
        ]);
    }

    public function test_detach_does_not_affect_other_attached_use_cases(): void
    {
        $model    = LanguageModel::factory()->create();
        $toDetach = UseCase::factory()->create();
        $toKeep   = UseCase::factory()->create();
        $model->useCases()->attach([$toDetach->id, $toKeep->id]);

        $this->delete(route('language_models.languagemodel.use_cases.detach', [$model, $toDetach]));

        $this->assertDatabaseMissing('language_model_use_case', [
            'language_model_id' => $model->id,
            'use_case_id'       => $toDetach->id,
        ]);
        $this->assertDatabaseHas('language_model_use_case', [
            'language_model_id' => $model->id,
            'use_case_id'       => $toKeep->id,
        ]);
    }

    // =========================================================================
    // GROUP 10: detach — 404 when not attached
    // =========================================================================

    public function test_detach_returns_404_when_use_case_not_attached(): void
    {
        $model   = LanguageModel::factory()->create();
        $useCase = UseCase::factory()->create();

        $this->delete(route('language_models.languagemodel.use_cases.detach', [$model, $useCase]))
            ->assertNotFound();
    }

    // =========================================================================
    // GROUP 11: edit form — enabled locked when digest-processing holder
    // =========================================================================

    public function test_edit_view_passes_is_content_summarisation_holder_true(): void
    {
        $model   = LanguageModel::factory()->create(['enabled' => true]);
        $useCase = $this->makeContentSummarisationUseCase();
        $model->useCases()->attach($useCase);

        $response = $this->get(route('language_models.languagemodel.edit', $model));

        $response->assertOk();
        $this->assertTrue($response->viewData('isContentSummarisationHolder'));
    }

    public function test_edit_view_passes_is_content_summarisation_holder_false_for_non_holder(): void
    {
        $model = LanguageModel::factory()->create(['enabled' => true]);
        UseCase::factory()->create(['slug' => 'digest-processing']);

        $response = $this->get(route('language_models.languagemodel.edit', $model));

        $response->assertOk();
        $this->assertFalse($response->viewData('isContentSummarisationHolder'));
    }

    // =========================================================================
    // GROUP 12: update — enabled forced true for digest-processing holder
    // =========================================================================

    public function test_update_forces_enabled_true_when_model_holds_content_summarisation(): void
    {
        $model   = LanguageModel::factory()->create(['enabled' => true]);
        $useCase = $this->makeContentSummarisationUseCase();
        $model->useCases()->attach($useCase);

        $this->put(route('language_models.languagemodel.update', $model), [
            'provider_id' => $model->provider_id,
            'name'        => $model->name,
            'enabled'     => '0', // attempt to disable
        ])->assertRedirect(route('language_models.languagemodel.show', $model));

        $this->assertDatabaseHas('language_models', [
            'id'      => $model->id,
            'enabled' => true, // forced back to true
        ]);
    }

    public function test_update_allows_disabling_model_without_content_summarisation(): void
    {
        $model = LanguageModel::factory()->create(['enabled' => true]);

        $this->put(route('language_models.languagemodel.update', $model), [
            'provider_id' => $model->provider_id,
            'name'        => $model->name,
            'enabled'     => '0',
        ])->assertRedirect(route('language_models.languagemodel.show', $model));

        $this->assertDatabaseHas('language_models', [
            'id'      => $model->id,
            'enabled' => false,
        ]);
    }

    // =========================================================================
    // GROUP 13: auth — guests redirected
    // =========================================================================

    public function test_attach_requires_authentication(): void
    {
        \Auth::logout();
        $useCase = UseCase::factory()->create();
        $model   = LanguageModel::factory()->create();

        $this->post(
            route('language_models.languagemodel.use_cases.attach', $model),
            ['use_case_id' => $useCase->id]
        )->assertRedirect(route('login'));
    }

    public function test_detach_requires_authentication(): void
    {
        $model   = LanguageModel::factory()->create();
        $useCase = UseCase::factory()->create();
        $model->useCases()->attach($useCase);

        \Auth::logout();

        $this->delete(
            route('language_models.languagemodel.use_cases.detach', [$model, $useCase])
        )->assertRedirect(route('login'));
    }
}