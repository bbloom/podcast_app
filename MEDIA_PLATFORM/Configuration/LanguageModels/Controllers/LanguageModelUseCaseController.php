<?php

namespace MediaPlatform\Configuration\LanguageModels\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Configuration\LanguageModels\Models\LanguageModel;
use MediaPlatform\Configuration\UseCases\Models\UseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageModelUseCaseController extends Controller
{
    private const EXCLUSIVE_USE_CASE = 'digest-processing';

    /**
     * Attach a use case to a language model.
     *
     * For the digest-processing use case, only one enabled model may hold it
     * at a time. If another enabled model already has it attached, that model
     * is automatically detached and the new one takes over.
     *
     * For all other use cases, attaches normally.
     */
    public function attach(Request $request, LanguageModel $languageModel): RedirectResponse
    {
        $request->validate([
            'use_case_id' => ['required', 'integer', 'exists:use_cases,id'],
        ]);

        $useCaseId = (int) $request->input('use_case_id');

        if ($languageModel->useCases()->where('use_case_id', $useCaseId)->exists()) {
            return back()->withErrors(['use_case_id' => 'That use case is already attached to this model.']);
        }

        $useCase = UseCase::find($useCaseId);

        // ── digest-processing: exclusive attachment ───────────────────────────
        if ($useCase?->slug === self::EXCLUSIVE_USE_CASE && $languageModel->enabled) {
            $displaced = $this->findCurrentHolder($useCaseId, $languageModel->id);

            if ($displaced) {
                $displaced->useCases()->detach($useCaseId);
                $languageModel->useCases()->attach($useCaseId);

                return redirect()
                    ->route('language_models.languagemodel.show', $languageModel)
                    ->with('success', "\"{$useCase->name}\" reassigned from {$displaced->name} to {$languageModel->name}.");
            }
        }

        // ── Standard attach ───────────────────────────────────────────────────
        $languageModel->useCases()->attach($useCaseId);

        return redirect()
            ->route('language_models.languagemodel.show', $languageModel)
            ->with('success', "\"{$useCase->name}\" attached successfully.");
    }

    /**
     * Detach a use case from a language model.
     */
    public function detach(LanguageModel $languageModel, UseCase $useCase): RedirectResponse
    {
        abort_unless(
            $languageModel->useCases()->where('use_case_id', $useCase->id)->exists(),
            404,
            'This use case is not attached to the model.'
        );

        $languageModel->useCases()->detach($useCase->id);

        return redirect()
            ->route('language_models.languagemodel.show', $languageModel)
            ->with('success', "\"{$useCase->name}\" detached successfully.");
    }

    /**
     * Find the currently enabled model holding the given use case,
     * excluding the model that is about to take it over.
     */
    private function findCurrentHolder(int $useCaseId, int $excludeModelId): ?LanguageModel
    {
        return LanguageModel::where('enabled', true)
            ->where('id', '!=', $excludeModelId)
            ->whereHas('useCases', fn ($q) => $q->where('use_case_id', $useCaseId))
            ->first();
    }
}