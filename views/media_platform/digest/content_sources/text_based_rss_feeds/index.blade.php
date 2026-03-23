<x-layouts.app title="My Text Based RSS Feeds">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">My Text Based RSS Feeds</h1>
        <a href="{{ route('text_based_rss_feeds.create.step1') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + Add Feed
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($feeds->total() === 0)

        <div class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 5c7.18 0 13 5.82 13 13M6 11a7 7 0 017 7M6 17a1 1 0 110-2 1 1 0 010 2z"/>
            </svg>
            <p class="text-sm font-semibold text-gray-500 mb-1">No feeds yet</p>
            <p class="text-sm text-gray-400 mb-6">Add your first RSS feed to start building digests.</p>
            <a href="{{ route('text_based_rss_feeds.create.step1') }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Add a Feed
            </a>
        </div>

    @else

        <div class="flex flex-col gap-3">
            @foreach ($feeds as $feed)
                <div class="border border-gray-200 rounded-lg p-4 flex items-center gap-4 hover:border-gray-300 transition">

                    {{-- Feed image --}}
                    <div class="flex-shrink-0">
                        @if ($feed->thumbnail)
                            <img
                                src="{{ $feed->thumbnail }}"
                                alt="{{ $feed->title }}"
                                class="w-12 h-12 rounded-lg object-cover border border-gray-200"
                            >
                        @else
                            <div class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 5c7.18 0 13 5.82 13 13M6 11a7 7 0 017 7M6 17a1 1 0 110-2 1 1 0 010 2z"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- Details --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $feed->title }}</p>
                            @if (! $feed->enabled)
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium flex-shrink-0">Disabled</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 truncate">
                            @if ($feed->site_url)
                                <a href="{{ $feed->site_url }}" target="_blank" class="hover:text-purple-700 transition">
                                    {{ $feed->site_url }}
                                </a>
                                <span class="mx-1.5">·</span>
                            @endif
                            <a href="{{ $feed->rss_url }}" target="_blank" class="hover:text-purple-700 transition">
                                RSS feed
                            </a>
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <a href="{{ route('text_based_rss_feeds.show', $feed) }}"
                            class="text-xs text-gray-500 hover:text-purple-700 font-medium transition">
                            Details
                        </a>
                        <a href="{{ route('text_based_rss_feeds.delete.confirm', $feed) }}"
                           class="text-xs text-gray-400 hover:text-red-600 font-medium transition">
                            Delete
                        </a>
                    </div>

                </div>
            @endforeach
        </div>

    @endif

</x-layouts.app>
