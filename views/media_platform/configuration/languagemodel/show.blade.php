<x-layouts.app :title="$languageModel->name">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('language_models.languagemodel.index') }}" class="hover:text-purple-700 transition">← Language Models</a>
            <span>›</span>
            <span class="text-gray-700">{{ $languageModel->name }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $languageModel->name }}</h1>
            <a href="{{ route('language_models.languagemodel.edit', $languageModel) }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">Edit</a>
        </div>
    </div>

    @session('success')
        <div class="mb-5 px-4 py-3 bg-green-50 border border-green-300 text-green-800 rounded text-sm">
            {{ $value }}
        </div>
    @endsession

    @if ($errors->any())
        <div class="mb-5 px-4 py-3 bg-red-50 border border-red-300 text-red-800 rounded text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Details card --}}
    <div class="border border-purple-500 rounded-lg p-4 mb-8">
        <dl class="grid grid-cols-[160px_1fr] gap-y-3 gap-x-4 text-sm">

            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Provider</dt>
            <dd>
                <a href="{{ route('language_models.providers.show', $languageModel->provider) }}"
                   class="text-purple-700 hover:underline">{{ $languageModel->provider->name }}</a>
            </dd>

            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Slug / ID</dt>
            <dd>
                <span id="modelSlug" class="font-mono text-xs bg-white border border-gray-200 rounded px-2 py-0.5 text-gray-600 inline-flex items-center gap-1">
                    {{ $languageModel->slug }}
                    <button onclick="navigator.clipboard.writeText('{{ $languageModel->slug }}')" class="text-gray-400 hover:text-purple-700" title="Copy">⎘</button>
                </span>
            </dd>

            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Status</dt>
            <dd>
                @if($languageModel->enabled)
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span>
                @else
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">Inactive</span>
                @endif
                @if($isContentSummarisationHolder)
                    <span class="ml-2 inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">⚡ Processing model</span>
                @endif
            </dd>

            @if($languageModel->description)
            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Description</dt>
            <dd class="text-gray-600">{{ $languageModel->description }}</dd>
            @endif

            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Created</dt>
            <dd class="text-gray-600">{{ $languageModel->created_at->toFormattedDateString() }}</dd>

            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Updated</dt>
            <dd class="text-gray-600">{{ $languageModel->updated_at->toFormattedDateString() }}</dd>

        </dl>
    </div>

    {{-- ── Use Cases panel ─────────────────────────────────────────────────── --}}
    <div class="mb-4">
        <h2 class="text-lg font-bold text-gray-800">
            Use Cases
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $languageModel->useCases->count() }})</span>
        </h2>
    </div>

    {{-- Attached use cases table --}}
    @if($languageModel->useCases->isEmpty())
        <div class="border border-gray-200 rounded-lg px-6 py-8 text-center text-sm text-gray-400 mb-4">
            No use cases attached yet.
        </div>
    @else
        <div class="border border-gray-200 rounded-lg overflow-hidden mb-4">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Description</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($languageModel->useCases as $uc)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <a href="{{ route('language_models.usecases.show', $uc) }}"
                               class="font-medium text-purple-700 hover:underline">{{ $uc->name }}</a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono text-xs bg-gray-100 border border-gray-200 rounded px-2 py-0.5 text-gray-600">{{ $uc->slug }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ $uc->description ?? '—' }}</td>
                        <td class="px-6 py-4 text-right">
                            <form method="POST"
                                  action="{{ route('language_models.languagemodel.use_cases.detach', [$languageModel, $uc]) }}"
                                  onsubmit="return confirm('Detach \'{{ addslashes($uc->name) }}\' from this model?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-xs text-red-500 hover:underline font-medium">Detach</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Attach form — only shown when unattached use cases exist --}}
    @if($availableUseCases->isNotEmpty())
        <div class="border border-gray-200 rounded-lg p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Attach a Use Case</p>
            <form method="POST"
                  action="{{ route('language_models.languagemodel.use_cases.attach', $languageModel) }}"
                  id="attachUseCaseForm"
                  class="flex items-center gap-3">
                @csrf
                <select name="use_case_id"
                        id="useCaseSelect"
                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">— Select a use case —</option>
                    @foreach($availableUseCases as $uc)
                        <option value="{{ $uc->id }}"
                                data-slug="{{ $uc->slug }}"
                                data-exclusive="{{ $uc->slug === 'digest-processing' ? 'true' : 'false' }}"
                                data-holder="{{ $uc->slug === 'digest-processing' && $contentSummarisationHolder ? addslashes($contentSummarisationHolder->name) : '' }}">
                            {{ $uc->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit"
                        id="attachBtn"
                        class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2 rounded-lg transition whitespace-nowrap">
                    Attach
                </button>
            </form>
        </div>

        <script>
        document.getElementById('attachUseCaseForm').addEventListener('submit', function (e) {
            const select = document.getElementById('useCaseSelect');
            const opt    = select.options[select.selectedIndex];

            if (!opt || opt.value === '') return true;

            if (opt.dataset.exclusive === 'true') {
                const holder = opt.dataset.holder;
                const modelName = '{{ addslashes($languageModel->name) }}';
                const msg = holder
                    ? `"${opt.text}" is currently assigned to ${holder}.\n\nAssigning it to ${modelName} will remove it from ${holder}.\n\nProceed?`
                    : `"${opt.text}" will be exclusively assigned to ${modelName}.\n\nOnly one model can hold this use case at a time.\n\nProceed?`;
                if (!confirm(msg)) {
                    e.preventDefault();
                }
            }
        });
        </script>
    @endif

</x-layouts.app>