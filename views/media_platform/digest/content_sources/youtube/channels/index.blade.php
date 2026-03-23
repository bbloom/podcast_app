<x-layouts.app title="My Youtube Channels">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">My YouTube Channels</h1>
        <a href="{{ route('youtube.channels.create.step1') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + Add Channel
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($channels->total() === 0)

        <div class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
            </svg>
            <p class="text-sm font-semibold text-gray-500 mb-1">No channels yet</p>
            <p class="text-sm text-gray-400 mb-6">Add your first Youtube channel to start building digests.</p>
            <a href="{{ route('youtube.channels.create.step1') }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Add a Channel
            </a>
        </div>

    @else

        <div class="flex flex-col gap-3">
            @foreach ($channels as $channel)
                <div class="border border-gray-200 rounded-lg p-4 flex items-center gap-4 hover:border-gray-300 transition">

                    {{-- Thumbnail --}}
                    <div class="flex-shrink-0">
                        @if ($channel->thumbnail)
                            <img
                                src="{{ $channel->thumbnail }}"
                                alt="{{ $channel->title }}"
                                class="w-12 h-12 rounded-full object-cover border border-gray-200"
                            >
                        @else
                            <div class="w-12 h-12 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- Details --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $channel->title }}</p>
                            @if (! $channel->enabled)
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium flex-shrink-0">Disabled</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500">
                            @if ($channel->handle)
                                {{ $channel->handle }}
                                <span class="mx-1.5">·</span>
                            @endif
                            <a href="{{ $channel->channel_url }}" target="_blank" class="hover:text-purple-700 transition">
                                youtube.com
                            </a>
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <a href="{{ route('youtube.channels.show', $channel) }}"
                        class="text-xs text-gray-500 hover:text-purple-700 font-medium transition">
                            Details
                        </a>
                        <a href="{{ route('youtube.channels.delete.confirm', $channel) }}"
                           class="text-xs text-gray-400 hover:text-red-600 font-medium transition">
                            Delete
                        </a>
                    </div>

                </div>
            @endforeach
        </div>

    @endif

</x-layouts.app>
