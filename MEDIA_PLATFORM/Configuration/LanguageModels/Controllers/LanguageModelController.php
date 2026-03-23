<?php

namespace MediaPlatform\Configuration\LanguageModels\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Configuration\LanguageModels\Requests\StoreLanguageModelRequest;
use MediaPlatform\Configuration\LanguageModels\Requests\UpdateLanguageModelRequest;
use MediaPlatform\Configuration\LanguageModels\Models\LanguageModel;
use MediaPlatform\Configuration\Providers\Models\Provider;
use MediaPlatform\Configuration\UseCases\Models\UseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LanguageModelController extends Controller
{

    private const CONTENT_SUMMARISATION_SLUG = 'digest-processing';


    public function index(): View
    {
        $languageModels = LanguageModel::with(['provider', 'useCases'])
            ->orderBy('name')
            ->paginate(config('admin.pagination_index'));

        return view('media_platform.configuration.languagemodel.index', compact('languageModels'));
    }

    public function create(): View
    {
        $providers = Provider::active()->orderBy('name')->get();
        $useCases  = UseCase::orderBy('name')->get();

        return view('media_platform.configuration.languagemodel.create', compact('providers', 'useCases'));
    }

    public function store(StoreLanguageModelRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $useCaseIds = $validated['use_case_ids'] ?? [];
        unset($validated['use_case_ids']);

        $languageModel = LanguageModel::create($validated);
        $languageModel->useCases()->sync($useCaseIds);

        return redirect()
            ->route('language_models.languagemodel.show', $languageModel)
            ->with('success', 'Language model created successfully.');
    }

    public function show(LanguageModel $languageModel): View
    {
        $languageModel->load(['provider', 'useCases']);

        $attachedIds       = $languageModel->useCases->pluck('id');
        $availableUseCases = UseCase::orderBy('name')
            ->whereNotIn('id', $attachedIds)
            ->get();

        // Is this model the current holder of digest-processing?
        $isContentSummarisationHolder = $languageModel->enabled
            && $languageModel->useCases->contains('slug', self::CONTENT_SUMMARISATION_SLUG);

        // If digest-processing is available to attach, find who currently holds it
        // so the confirm dialog can name the model being displaced.
        $contentSummarisationHolder = null;
        $csUseCase = $availableUseCases->firstWhere('slug', self::CONTENT_SUMMARISATION_SLUG);
        if ($csUseCase) {
            $contentSummarisationHolder = LanguageModel::where('enabled', true)
                ->where('id', '!=', $languageModel->id)
                ->whereHas('useCases', fn ($q) => $q->where('slug', self::CONTENT_SUMMARISATION_SLUG))
                ->first();
        }

        return view('media_platform.configuration.languagemodel.show', compact(
            'languageModel',
            'availableUseCases',
            'isContentSummarisationHolder',
            'contentSummarisationHolder',
        ));
    }

    public function edit(LanguageModel $languageModel): View
    {
        $providers = Provider::active()->orderBy('name')->get();
        $useCases  = UseCase::orderBy('name')->get();
        $languageModel->load('useCases');

        $isContentSummarisationHolder = $languageModel->enabled
            && $languageModel->useCases->contains('slug', self::CONTENT_SUMMARISATION_SLUG);

        return view('media_platform.configuration.languagemodel.edit', compact(
            'languageModel',
            'providers',
            'useCases',
            'isContentSummarisationHolder',
        ));
    }

    public function update(UpdateLanguageModelRequest $request, LanguageModel $languageModel): RedirectResponse
    {
        $validated = $request->validated();
        $useCaseIds = $validated['use_case_ids'] ?? [];
        unset($validated['use_case_ids']);

        $languageModel->update($validated);
        $languageModel->useCases()->sync($useCaseIds);

        return redirect()
            ->route('language_models.languagemodel.show', $languageModel)
            ->with('success', 'Language model updated successfully.');
    }

    public function destroy(LanguageModel $languageModel): RedirectResponse
    {
        $languageModel->delete();

        return redirect()
            ->route('language_models.languagemodel.index')
            ->with('success', 'Language model deleted.');
    }
}
