<x-layouts.app title="Detach Feed from List">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('text_based_rss_feeds.index') }}" class="hover:text-purple-700 transition">← My Text Based RSS Feeds</a>
            <span>›</span>
            <a href="{{ route('text_based_rss_feeds.show', $source) }}" class="hover:text-purple-700 transition">{{ $source->title }}</a>
            <span>›</span>
            <span class="text-gray-700">Detach from list</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Detach from List</h1>
    </div>

    {{-- Warning --}}
    <div class="bg-red-50 border border-red-300 rounded-lg p-4 mb-6">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div class="text-sm text-red-800">
                <p class="font-semibold mb-1">This action cannot be undone.</p>
                <p>Detaching this feed will permanently delete all summaries that were generated
                   for this feed within this list. The feed itself will not be deleted.</p>
            </div>
        </div>
    </div>

    {{-- Summary card — clearly names both sides of the relationship --}}
    <div class="border border-red-300 rounded-lg p-6 mb-8">
        <table class="text-sm text-gray-600 border-collapse">
            <tr>
                <td class="pr-8 py-2 text-gray-500 whitespace-nowrap font-semibold w-36">Feed</td>
                <td class="py-2 text-gray-800 font-bold">{{ $source->title }}</td>
            </tr>
            <tr>
                <td class="pr-8 py-2 text-gray-500 whitespace-nowrap font-semibold">Being removed from</td>
                <td class="py-2 text-gray-800 font-bold">
                    <a href="{{ route('lists.show', $listSource->list) }}"
                       class="text-purple-700 hover:underline">
                        {{ $listSource->list->name }}
                    </a>
                </td>
            </tr>
            <tr>
                <td class="pr-8 py-2 text-gray-500 whitespace-nowrap font-semibold">Processing mode</td>
                <td class="py-2 text-gray-800 capitalize">{{ $listSource->processing_mode }}</td>
            </tr>
            @if ($listSource->search_terms)
                <tr>
                    <td class="pr-8 py-2 text-gray-500 whitespace-nowrap font-semibold">Search terms</td>
                    <td class="py-2 text-gray-800">{{ $listSource->search_terms }}</td>
                </tr>
            @endif
        </table>
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('text_based_rss_feeds.show', $source) }}"
           class="text-sm text-purple-700 hover:underline flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Cancel, go back
        </a>

        <form method="POST"
              action="{{ route('text_based_rss_feeds.list_sources.detach', [$source, $listSource]) }}">
            @csrf
            @method('DELETE')
            <button
                type="submit"
                class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
            >
                Yes, detach from "{{ $listSource->list->name }}"
            </button>
        </form>
    </div>

</x-layouts.app>