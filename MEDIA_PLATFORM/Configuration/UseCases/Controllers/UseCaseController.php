<?php

namespace MediaPlatform\Configuration\UseCases\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Configuration\UseCases\Models\UseCase;
use MediaPlatform\Configuration\UseCases\Requests\StoreUseCaseRequest;
use MediaPlatform\Configuration\UseCases\Requests\UpdateUseCaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UseCaseController extends Controller
{
    public function index(): View
    {
        $useCases = UseCase::withCount('languageModels')
            ->orderBy('name')
            ->paginate(config('admin.pagination_index'));

        return view('media_platform.configuration.usecases.index', compact('useCases'));
    }

    public function create(): View
    {
        return view('media_platform.configuration.usecases.create');
    }

    public function store(StoreUseCaseRequest $request): RedirectResponse
    {
        UseCase::create($request->validated());

        return redirect()
            ->route('language_models.usecases.index')
            ->with('success', 'Use case created successfully.');
    }

    public function show(UseCase $useCase): View
    {
        $useCase->load('languageModels.provider');

        // For the digest-processing use case, identify the currently active
        // model so the view can display the amber notice naming it.
        $activeModel = null;
        if ($useCase->slug === 'digest-processing') {
            $activeModel = $useCase->languageModels
                ->firstWhere('enabled', true);
        }

        return view('media_platform.configuration.usecases.show', compact('useCase', 'activeModel'));
    }

    public function edit(UseCase $useCase): View
    {
        return view('media_platform.configuration.usecases.edit', compact('useCase'));
    }

    public function update(UpdateUseCaseRequest $request, UseCase $useCase): RedirectResponse
    {
        $useCase->update($request->validated());

        return redirect()
            ->route('language_models.usecases.show', $useCase)
            ->with('success', 'Use case updated successfully.');
    }

    public function destroy(UseCase $useCase): RedirectResponse
    {
        $useCase->delete();

        return redirect()
            ->route('language_models.usecases.index')
            ->with('success', 'Use case deleted.');
    }
}
