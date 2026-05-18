<x-layouts.app title="New Podcast Link">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('podcast_links.index') }}" class="hover:text-purple-700 transition">Podcast Links</a>
            <span>›</span>
            <span class="text-gray-700">New Link</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">New Podcast Link</h1>
        <p class="mt-1 text-sm text-gray-500">Enter the URL — the title and description will be populated automatically.</p>
    </div>

    @session('warning')
        <div class="bg-yellow-50 border border-yellow-300 rounded-lg px-4 py-3 mb-6 text-sm text-yellow-800">
            {{ $value }}
        </div>
    @endsession

    <form method="POST" action="{{ route('podcast_links.store') }}">
        @csrf

        {{-- URL --}}
        <div class="mb-8">
            <label for="link" class="block text-sm font-semibold text-gray-700 mb-2">URL</label>
            <input type="url" id="link" name="link" value="{{ old('link') }}" required autofocus
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('link') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">Full URL including https://</p>
            @error('link') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('podcast_links.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                Cancel
            </a>
            <button type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Fetch &amp; Save
            </button>
        </div>

    </form>

</x-layouts.app>