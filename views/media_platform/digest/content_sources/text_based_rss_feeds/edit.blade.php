<x-layouts.app title="Edit Text Based RSS Feed">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('text_based_rss_feeds.index') }}" class="hover:text-purple-700 transition">My Text Based RSS Feeds</a>
            <span>›</span>
            <span class="text-gray-700">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Edit Text Based RSS Feed</h1>
    </div>

    {{-- Feed summary (read-only) --}}
    <div class="border border-purple-500 rounded-lg p-4 mb-8">
        <div class="flex gap-4">

            <div class="flex-shrink-0">
                @if ($textBasedRssFeed->thumbnail)
                    <img
                        src="{{ $textBasedRssFeed->thumbnail }}"
                        alt="{{ $textBasedRssFeed->title }}"
                        class="w-20 h-20 rounded-lg object-cover border border-gray-200"
                    >
                @else
                    <div class="w-20 h-20 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 5c7.18 0 13 5.82 13 13M6 11a7 7 0 017 7M6 17a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </div>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-xl font-bold text-gray-800">{{ $textBasedRssFeed->title }}</p>

                @if ($textBasedRssFeed->description)
                    <p class="mt-2 text-sm text-gray-600 line-clamp-2">{{ $textBasedRssFeed->description }}</p>
                @endif

                <table class="mt-4 text-sm text-gray-600 border-collapse">
                    @if ($textBasedRssFeed->site_url)
                        <tr>
                            <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Website</td>
                            <td class="py-1">
                                <a href="{{ $textBasedRssFeed->site_url }}" target="_blank" class="text-purple-700 hover:underline break-all">
                                    {{ $textBasedRssFeed->site_url }}
                                </a>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">RSS Feed</td>
                        <td class="py-1">
                            <a href="{{ $textBasedRssFeed->rss_url }}" target="_blank" class="text-purple-700 hover:underline break-all">
                                {{ $textBasedRssFeed->rss_url }}
                            </a>
                        </td>
                    </tr>
                </table>
            </div>

        </div>
    </div>

    {{-- Edit form --}}
    <form method="POST" action="{{ route('text_based_rss_feeds.update', $textBasedRssFeed) }}">
        @csrf
        @method('PUT')

        <div class="mb-8">
            <label class="block text-sm font-semibold text-gray-700 mb-3">Status</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="1"
                        {{ old('enabled', $textBasedRssFeed->enabled ? '1' : '0') === '1' ? 'checked' : '' }}
                        class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        Enabled
                    </div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="0"
                        {{ old('enabled', $textBasedRssFeed->enabled ? '1' : '0') === '0' ? 'checked' : '' }}
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
            <a href="{{ route('text_based_rss_feeds.delete.confirm', $textBasedRssFeed) }}"
               class="text-sm text-red-500 hover:text-red-700 font-medium transition">
                Delete this feed
            </a>
            <div class="flex gap-3">
                <a href="{{ route('text_based_rss_feeds.index') }}"
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
