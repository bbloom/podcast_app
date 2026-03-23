<x-layouts.app title="Edit Youtube Channel">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('youtube.channels.index') }}" class="hover:text-purple-700 transition">My Youtube Channels</a>
            <span>›</span>
            <span class="text-gray-700">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Edit Youtube Channel</h1>
    </div>

    {{-- Channel summary (read-only) --}}
    <div class="border border-purple-500 rounded-lg p-4 mb-8">
        <div class="flex gap-4">

            <div class="flex-shrink-0">
                @if ($youtubeChannel->thumbnail)
                    <img
                        src="{{ $youtubeChannel->thumbnail }}"
                        alt="{{ $youtubeChannel->title }}"
                        class="w-20 h-20 rounded-full object-cover border border-gray-200"
                    >
                @else
                    <div class="w-20 h-20 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                        </svg>
                    </div>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-xl font-bold text-gray-800">{{ $youtubeChannel->title }}</p>

                @if ($youtubeChannel->description)
                    <p class="mt-2 text-sm text-gray-600 line-clamp-2">{{ $youtubeChannel->description }}</p>
                @endif

                <table class="mt-4 text-sm text-gray-600 border-collapse">
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Handle</td>
                        <td class="py-1 font-semibold text-gray-800">{{ $youtubeChannel->handle ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Channel ID</td>
                        <td class="py-1"><code class="font-bold">{{ $youtubeChannel->channel_id }}</code></td>
                    </tr>
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">URL</td>
                        <td class="py-1">
                            <a href="{{ $youtubeChannel->channel_url }}" target="_blank" class="text-purple-700 hover:underline break-all">
                                {{ $youtubeChannel->channel_url }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">RSS Feed</td>
                        <td class="py-1">
                            <a href="{{ $youtubeChannel->rss_url }}" target="_blank" class="text-purple-700 hover:underline break-all">
                                {{ $youtubeChannel->rss_url }}
                            </a>
                        </td>
                    </tr>
                </table>
            </div>

        </div>
    </div>

    {{-- Edit form --}}
    <form method="POST" action="{{ route('youtube.channels.update', $youtubeChannel) }}">
        @csrf
        @method('PUT')

        <div class="mb-8">
            <label class="block text-sm font-semibold text-gray-700 mb-3">Status</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="1"
                        {{ old('enabled', $youtubeChannel->enabled ? '1' : '0') === '1' ? 'checked' : '' }}
                        class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        Enabled
                    </div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="0"
                        {{ old('enabled', $youtubeChannel->enabled ? '1' : '0') === '0' ? 'checked' : '' }}
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
            <a href="{{ route('youtube.channels.delete.confirm', $youtubeChannel) }}"
               class="text-sm text-red-500 hover:text-red-700 font-medium transition">
                Delete this channel
            </a>
            <div class="flex gap-3">
                <a href="{{ route('youtube.channels.index') }}"
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
