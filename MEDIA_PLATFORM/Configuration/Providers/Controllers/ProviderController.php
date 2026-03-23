<?php

namespace MediaPlatform\Configuration\Providers\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Configuration\Providers\Requests\StoreProviderRequest;
use MediaPlatform\Configuration\Providers\Requests\UpdateProviderRequest;
use MediaPlatform\Configuration\Providers\Models\Provider;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProviderController extends Controller
{
    public function index(): View
    {
        $providers = Provider::withCount('languageModels')
            ->orderBy('name')
            ->paginate(config('admin.pagination_index'));

        return view('media_platform.configuration.providers.index', compact('providers'));
    }

    public function create(): View
    {
        return view('media_platform.configuration.providers.create');
    }

    public function store(StoreProviderRequest $request): RedirectResponse
    {
        Provider::create($request->validated());

        return redirect()
            ->route('language_models.providers.index')
            ->with('success', 'Provider created successfully.');
    }

    public function show(Provider $provider): View
    {
        $provider->load(['languageModels.useCases', 'languageModels.provider']);

        return view('media_platform.configuration.providers.show', compact('provider'));
    }

    public function edit(Provider $provider): View
    {
        return view('media_platform.configuration.providers.edit', compact('provider'));
    }

    public function update(UpdateProviderRequest $request, Provider $provider): RedirectResponse
    {
        $provider->update($request->validated());

        return redirect()
            ->route('language_models.providers.show', $provider)
            ->with('success', 'Provider updated successfully.');
    }

    public function destroy(Provider $provider): RedirectResponse
    {
        // Prevent deletion if language models are still attached
        if ($provider->languageModels()->exists()) {
            return redirect()
                ->route('language_models.providers.show', $provider)
                ->with('error', 'Cannot delete a provider that has language models. Remove or reassign them first.');
        }

        $provider->delete();

        return redirect()
            ->route('language_models.providers.index')
            ->with('success', 'Provider deleted.');
    }
}
