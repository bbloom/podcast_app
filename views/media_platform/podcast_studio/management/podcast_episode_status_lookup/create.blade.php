<x-layouts.app title="New Episode Status">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('podcast_episode_status_lookup.index') }}" class="hover:text-purple-700 transition">Episode Statuses</a>
            <span>›</span>
            <span class="text-gray-700">New Status</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">New Episode Status</h1>
    </div>

    <form method="POST" action="{{ route('podcast_episode_status_lookup.store') }}">
        @csrf

        {{-- Title --}}
        <div class="mb-6">
            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Title</label>
            <input
                type="text"
                id="title"
                name="title"
                value="{{ old('title') }}"
                required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('title') border-red-400 @enderror"
            >
            @error('title') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Description --}}
        <div class="mb-6">
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
            <input
                type="text"
                id="description"
                name="description"
                value="{{ old('description') }}"
                required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('description') border-red-400 @enderror"
            >
            @error('description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Enabled --}}
        <div class="mb-8">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="1"
                        {{ old('enabled', '1') === '1' ? 'checked' : '' }}
                        class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        Enabled
                    </div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="0"
                        {{ old('enabled', '1') === '0' ? 'checked' : '' }}
                        class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        Disabled
                    </div>
                </label>
            </div>
            @error('enabled') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('podcast_episode_status_lookup.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                Cancel
            </a>
            <button type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Create Status
            </button>
        </div>

    </form>

</x-layouts.app>