<x-layouts.app title="Edit Podcast">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('podcasts.index') }}" class="hover:text-purple-700 transition">My Podcasts</a>
            <span>›</span>
            <span class="text-gray-700">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Edit Podcast</h1>
    </div>

    {{-- Podcast summary (read-only) --}}
    <div class="border border-purple-500 rounded-lg p-4 mb-8">
        <div class="flex gap-4">

            <div class="flex-shrink-0">
                @if ($podcast->thumbnail)
                    <img
                        src="{{ $podcast->thumbnail }}"
                        alt="{{ $podcast->title }}"
                        class="w-20 h-20 rounded-lg object-cover border border-gray-200"
                    >
                @else
                    <div class="w-20 h-20 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                    </div>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-xl font-bold text-gray-800">{{ $podcast->title }}</p>

                @if ($podcast->description)
                    <p class="mt-2 text-sm text-gray-600 line-clamp-2">{{ $podcast->description }}</p>
                @endif

                <table class="mt-4 text-sm text-gray-600 border-collapse">
                    @if ($podcast->site_url)
                        <tr>
                            <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Website</td>
                            <td class="py-1">
                                <a href="{{ $podcast->site_url }}" target="_blank" class="text-purple-700 hover:underline break-all">
                                    {{ $podcast->site_url }}
                                </a>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">RSS Feed</td>
                        <td class="py-1">
                            <a href="{{ $podcast->rss_url }}" target="_blank" class="text-purple-700 hover:underline break-all">
                                {{ $podcast->rss_url }}
                            </a>
                        </td>
                    </tr>
                </table>
            </div>

        </div>
    </div>

    {{-- Edit form --}}
    <form method="POST" action="{{ route('podcasts.update', $podcast) }}">
        @csrf
        @method('PUT')

        <div class="mb-8">
            <label class="block text-sm font-semibold text-gray-700 mb-3">Status</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="1"
                        {{ old('enabled', $podcast->enabled ? '1' : '0') === '1' ? 'checked' : '' }}
                        class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        Enabled
                    </div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="0"
                        {{ old('enabled', $podcast->enabled ? '1' : '0') === '0' ? 'checked' : '' }}
                        class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        Disabled
                    </div>
                </label>
            </div>
            @error('enabled')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('podcasts.delete.confirm', $podcast) }}"
               class="text-sm text-red-500 hover:text-red-700 font-medium transition">
                Delete this podcast
            </a>
            <div class="flex gap-3">
                <a href="{{ route('podcasts.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                    Cancel
                </a>
                <button
                    type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
                >
                    Save Changes
                </button>
            </div>
        </div>

    </form>

</x-layouts.app>
