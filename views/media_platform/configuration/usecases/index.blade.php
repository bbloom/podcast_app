<x-layouts.app title="Use Cases">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Use Cases</h1>
        <a href="{{ route('language_models.usecases.create') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + New Use Case
        </a>
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

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Slug</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Models</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($useCases as $useCase)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">
                        <a href="{{ route('language_models.usecases.show', $useCase) }}" class="hover:text-purple-700">
                            {{ $useCase->name }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs bg-gray-100 border border-gray-200 rounded px-2 py-0.5 text-gray-600 inline-flex items-center gap-1">
                            {{ $useCase->slug }}
                            <button onclick="copyToClipboard('{{ $useCase->slug }}', this)"
                                    class="text-gray-400 hover:text-purple-700" title="Copy">⎘</button>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $useCase->description ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $useCase->language_models_count ?? 0 }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                        <a href="{{ route('language_models.usecases.show', $useCase) }}"
                           class="inline-block text-xs text-gray-500 hover:text-purple-700 font-medium transition">Details</a>
                        <a href="{{ route('language_models.usecases.destroy', $useCase) }}"
                           onclick="event.preventDefault(); if(confirm('Delete {{ addslashes($useCase->name) }}? This cannot be undone.')) { document.getElementById('del-{{ $useCase->id }}').submit(); }"
                           class="inline-block text-xs text-gray-400 hover:text-red-600 font-medium transition">Delete</a>
                        <form id="del-{{ $useCase->id }}" method="POST" action="{{ route('language_models.usecases.destroy', $useCase) }}" class="hidden">
                            @csrf @method('DELETE')
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-gray-400">
                        No use cases yet.
                        <a href="{{ route('language_models.usecases.create') }}" class="text-purple-700 hover:underline">Add one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($useCases->hasPages())
        <div class="mt-4">{{ $useCases->links() }}</div>
    @endif

    {{-- LLM Registry sub-nav --}}
    <div class="flex gap-2 mb-6 mt-12 pt-4 border-t border-gray-200 pb-3">
        <a href="{{ route('language_models.languagemodel.index') }}"
           class="text-xs font-semibold text-gray-500 hover:text-purple-700 bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-full">Language Models</a>
        <a href="{{ route('language_models.providers.index') }}"
           class="text-xs font-semibold text-gray-500 hover:text-purple-700 bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-full">Providers</a>
        <span class="text-xs font-semibold bg-purple-700 text-white px-3 py-1 rounded-full">Use Cases</span>
    </div>

</x-layouts.app>

@push('scripts')
<script>
function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.textContent;
        btn.textContent = '✓';
        setTimeout(() => btn.textContent = orig, 1500);
    });
}
</script>
@endpush