<x-layouts.app :title="$useCase->name">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('language_models.usecases.index') }}" class="hover:text-purple-700 transition">← Use Cases</a>
            <span>›</span>
            <span class="text-gray-700">{{ $useCase->name }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $useCase->name }}</h1>
            <a href="{{ route('language_models.usecases.edit', $useCase) }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">Edit</a>
        </div>
    </div>

    @session('success')
        <div class="mb-5 px-4 py-3 bg-green-50 border border-green-300 text-green-800 rounded text-sm">
            {{ $value }}
        </div>
    @endsession

    {{-- Amber notice for digest-processing: exclusive model constraint --}}
    @if($useCase->slug === 'digest-processing')
        <div class="mb-6 px-4 py-3 bg-amber-50 border border-amber-300 rounded-lg text-sm text-amber-800 flex items-start gap-3">
            <span class="text-lg leading-none mt-0.5">⚡</span>
            <div>
                <p class="font-semibold mb-0.5">Processing use case — one active model at a time</p>
                @if($activeModel)
                    <p>Currently handled by
                        <a href="{{ route('language_models.languagemodel.show', $activeModel) }}"
                           class="font-semibold underline hover:text-amber-900">{{ $activeModel->name }}</a>.
                        To switch models, go to the new model's page and attach this use case — the switch happens automatically.
                    </p>
                @else
                    <p class="text-amber-700">No enabled model is currently assigned. Content summarisation will fail until an enabled model is attached to this use case.</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Details card --}}
    <div class="border border-purple-500 rounded-lg p-4 mb-8">
        <dl class="grid grid-cols-[160px_1fr] gap-y-3 gap-x-4 text-sm">

            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Slug</dt>
            <dd>
                <span id="useCaseSlug" class="font-mono text-xs bg-white border border-gray-200 rounded px-2 py-0.5 text-gray-600 inline-flex items-center gap-1">
                    {{ $useCase->slug }}
                    <button onclick="copySlug()" class="text-gray-400 hover:text-purple-700" title="Copy">⎘</button>
                </span>
            </dd>

            @if($useCase->description)
            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Description</dt>
            <dd class="text-gray-600">{{ $useCase->description }}</dd>
            @endif

            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Created</dt>
            <dd class="text-gray-600">{{ $useCase->created_at->toFormattedDateString() }}</dd>

            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Updated</dt>
            <dd class="text-gray-600">{{ $useCase->updated_at->toFormattedDateString() }}</dd>

        </dl>
    </div>

    {{-- Language models using this use case --}}
    <div class="mb-4">
        <h2 class="text-lg font-bold text-gray-800">
            Language Models
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $useCase->languageModels->count() }})</span>
        </h2>
    </div>

    @if($useCase->languageModels->isEmpty())
        <div class="border border-gray-200 rounded-lg px-6 py-8 text-center text-sm text-gray-400 mb-8">
            No models are using this use case yet.
        </div>
    @else
        <div class="border border-gray-200 rounded-lg overflow-hidden mb-8">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($useCase->languageModels as $model)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <a href="{{ route('language_models.languagemodel.show', $model) }}"
                               class="font-medium text-purple-700 hover:underline">{{ $model->name }}</a>
                            @if($useCase->slug === 'digest-processing' && $model->enabled)
                                <span class="ml-1 inline-block px-1.5 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700">⚡ Active</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono text-xs bg-gray-100 border border-gray-200 rounded px-2 py-0.5 text-gray-600">{{ $model->slug }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($model->enabled)
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span>
                            @else
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ $model->description ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</x-layouts.app>

@push('scripts')
<script>
function copySlug() {
    navigator.clipboard.writeText('{{ $useCase->slug }}');
}
</script>
@endpush