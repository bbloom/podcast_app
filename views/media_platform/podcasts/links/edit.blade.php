<x-layouts.app title="Edit Podcast Link">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('podcast_links.index') }}" class="hover:text-purple-700 transition">Podcast Links</a>
            <span>›</span>
            <a href="{{ route('podcast_links.show', $link) }}" class="hover:text-purple-700 transition">{{ $link->title ?? 'Link #' . $link->id }}</a>
            <span>›</span>
            <span class="text-gray-700">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Edit Podcast Link</h1>
    </div>

    @session('warning')
        <div class="bg-yellow-50 border border-yellow-300 rounded-lg px-4 py-3 mb-6 text-sm text-yellow-800">
            {{ $value }}
        </div>
    @endsession

    <form method="POST" action="{{ route('podcast_links.update', $link) }}">
        @csrf
        @method('PUT')

        {{-- URL --}}
        <div class="mb-6">
            <label for="link" class="block text-sm font-semibold text-gray-700 mb-2">URL</label>
            <input type="url" id="link" name="link" value="{{ old('link', $link->link) }}" required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('link') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">Full URL including https://</p>
            @error('link') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Title --}}
        <div class="mb-6">
            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Title</label>
            <input type="text" id="title" name="title" value="{{ old('title', $link->title) }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('title') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">Leave blank to populate automatically via scraper.</p>
            @error('title') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Description --}}
        <div class="mb-6">
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
            <textarea id="description" name="description" rows="4"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize @error('description') border-red-400 @enderror">{{ old('description', $link->description) }}</textarea>
            <p class="mt-1 text-xs text-gray-400">Leave blank to populate automatically via scraper.</p>
            @error('description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Comments --}}
        <div class="mb-6">
            <label for="comments" class="block text-sm font-semibold text-gray-700 mb-2">Comments</label>
            <textarea id="comments" name="comments" rows="3"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize @error('comments') border-red-400 @enderror">{{ old('comments', $link->comments) }}</textarea>
            <p class="mt-1 text-xs text-gray-400">Internal notes — not published anywhere.</p>
            @error('comments') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Enabled --}}
        <div class="mb-8">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="1"
                        {{ old('enabled', $link->enabled ? '1' : '0') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Enabled</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="0"
                        {{ old('enabled', $link->enabled ? '1' : '0') === '0' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Disabled</div>
                </label>
            </div>
            @error('enabled') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('podcast_links.delete.confirm', $link) }}"
               class="text-sm text-red-500 hover:text-red-700 font-medium transition">
                Delete this link
            </a>
            <div class="flex gap-3">
                <a href="{{ route('podcast_links.show', $link) }}"
                   class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                    Save Changes
                </button>
            </div>
        </div>

    </form>

</x-layouts.app>