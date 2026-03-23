<x-layouts.app title="Delete Podcast">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('podcasts.index') }}" class="hover:text-purple-700 transition">My Podcasts</a>
            <span>›</span>
            <span class="text-gray-700">Delete</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Delete Podcast</h1>
    </div>

    {{-- Warning --}}
    <div class="bg-red-50 border border-red-300 rounded-lg p-5 mb-8">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div class="text-sm text-red-800">
                <p class="font-semibold mb-1">This cannot be undone</p>
                <p>Deleting this podcast will permanently remove it and all of its list assignments. It will no longer appear in any digests.</p>
            </div>
        </div>
    </div>

    {{-- Podcast summary --}}
    <div class="border border-gray-200 rounded-lg p-4 mb-8">
        <div class="flex gap-4">

            <div class="flex-shrink-0">
                @if ($podcast->thumbnail)
                    <img
                        src="{{ $podcast->thumbnail }}"
                        alt="{{ $podcast->title }}"
                        class="w-16 h-16 rounded-lg object-cover border border-gray-200"
                    >
                @else
                    <div class="w-16 h-16 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center">
                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                    </div>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-lg font-bold text-gray-800">{{ $podcast->title }}</p>
                @if ($podcast->site_url)
                    <a href="{{ $podcast->site_url }}" target="_blank"
                       class="text-sm text-purple-700 hover:underline mt-0.5 inline-block">
                        {{ $podcast->site_url }}
                    </a>
                @endif
                <p class="text-xs text-gray-400 mt-1 truncate">{{ $podcast->rss_url }}</p>
            </div>

        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('podcasts.edit', $podcast) }}"
           class="text-sm text-gray-500 hover:text-gray-700 font-semibold py-3 transition">
            ← Cancel
        </a>
        <form method="POST" action="{{ route('podcasts.destroy', $podcast) }}">
            @csrf
            @method('DELETE')
            <button
                type="submit"
                class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
            >
                Yes, Delete This Podcast
            </button>
        </form>
    </div>

</x-layouts.app>
