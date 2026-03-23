<x-layouts.app :title="$provider->name">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('language_models.providers.index') }}" class="hover:text-purple-700 transition">← Providers</a>
            <span>›</span>
            <span class="text-gray-700">{{ $provider->name }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $provider->name }}</h1>
            <a href="{{ route('language_models.providers.edit', $provider) }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">Edit</a>
        </div>
    </div>

    @session('success')
        <div class="mb-5 px-4 py-3 bg-green-50 border border-green-300 text-green-800 rounded text-sm">
            {{ $value }}
        </div>
    @endsession
    @session('error')
        <div class="mb-5 px-4 py-3 bg-red-50 border border-red-300 text-red-800 rounded text-sm">
            {{ $value }}
        </div>
    @endsession

    {{-- Details card --}}
    <div class="border border-purple-500 rounded-lg p-4 mb-8">
        <dl class="grid grid-cols-[160px_1fr] gap-y-3 gap-x-4 text-sm">

            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Slug</dt>
            <dd>
                <span class="font-mono text-xs bg-white border border-gray-200 rounded px-2 py-0.5 text-gray-600">
                    {{ $provider->slug }}
                </span>
            </dd>

            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Status</dt>
            <dd>
                @if($provider->enabled)
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span>
                @else
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">Inactive</span>
                @endif
            </dd>

            @if($provider->website_url)
            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Website</dt>
            <dd>
                <a href="{{ $provider->website_url }}" target="_blank" rel="noopener"
                   class="text-purple-700 hover:underline">{{ $provider->website_url }}</a>
            </dd>
            @endif

            @if($provider->description)
            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Description</dt>
            <dd class="text-gray-600">{{ $provider->description }}</dd>
            @endif

            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Created</dt>
            <dd class="text-gray-600">{{ $provider->created_at->toFormattedDateString() }}</dd>

        </dl>
    </div>

    {{-- Language models --}}
    <div class="mb-4">
        <h2 class="text-lg font-bold text-gray-800">
            Language Models
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $provider->languageModels->count() }})</span>
        </h2>
    </div>

    @if($provider->languageModels->isEmpty())
        <div class="border border-gray-200 rounded-lg px-6 py-8 text-center text-sm text-gray-400 mb-8">
            No models for this provider yet.
            <a href="{{ route('language_models.languagemodel.create') }}" class="text-purple-700 hover:underline">Add one →</a>
        </div>
    @else
        <div class="border border-gray-200 rounded-lg overflow-hidden mb-8">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Use Cases</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($provider->languageModels as $model)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <a href="{{ route('language_models.languagemodel.show', $model) }}"
                               class="font-medium text-purple-700 hover:underline">{{ $model->name }}</a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono text-xs bg-gray-100 border border-gray-200 rounded px-2 py-0.5 text-gray-600">{{ $model->slug }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @foreach($model->useCases as $uc)
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 mr-1">{{ $uc->name }}</span>
                            @endforeach
                        </td>
                        <td class="px-6 py-4">
                            @if($model->enabled)
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span>
                            @else
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Danger zone — only shown when no models are attached --}}
    @if($provider->languageModels->isEmpty())
    <div class="mt-8 pt-6 border-t border-gray-200">
        <p class="text-xs text-gray-400 mb-3">Danger zone</p>
        <form method="POST" action="{{ route('language_models.providers.destroy', $provider) }}"
              onsubmit="return confirm('Delete {{ addslashes($provider->name) }}? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="text-sm bg-red-100 hover:bg-red-200 text-red-700 font-medium px-4 py-2 rounded">
                Delete Provider
            </button>
        </form>
    </div>
    @endif

    <div class="mt-6 text-sm">
        <a href="{{ route('language_models.providers.index') }}" class="hover:text-purple-700 transition">← Providers</a>
    </div>

</x-layouts.app>