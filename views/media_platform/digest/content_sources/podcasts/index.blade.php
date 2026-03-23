<x-layouts.app title="My Podcasts">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">My Podcasts</h1>
        <a href="{{ route('podcasts.create.step1') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + Add Podcast
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($podcasts->total() === 0)

        <div class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
            </svg>
            <p class="text-sm font-semibold text-gray-500 mb-1">No podcasts yet</p>
            <p class="text-sm text-gray-400 mb-6">Add your first podcast to start building digests.</p>
            <a href="{{ route('podcasts.create.step1') }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Add a Podcast
            </a>
        </div>

    @else

        <div class="flex flex-col gap-3">
            @foreach ($podcasts as $podcast)
                <div class="border border-gray-200 rounded-lg p-4 flex items-center gap-4 hover:border-gray-300 transition">

                    {{-- Cover art --}}
                    <div class="flex-shrink-0">
                        @if ($podcast->thumbnail)
                            <img
                                src="{{ $podcast->thumbnail }}"
                                alt="{{ $podcast->title }}"
                                class="w-12 h-12 rounded-lg object-cover border border-gray-200"
                            >
                        @else
                            <div class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- Details --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $podcast->title }}</p>
                            @if (! $podcast->enabled)
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium flex-shrink-0">Disabled</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 truncate">
                            @if ($podcast->site_url)
                                <a href="{{ $podcast->site_url }}" target="_blank" class="hover:text-purple-700 transition">
                                    {{ $podcast->site_url }}
                                </a>
                                <span class="mx-1.5">·</span>
                            @endif
                            <a href="{{ $podcast->rss_url }}" target="_blank" class="hover:text-purple-700 transition">
                                RSS feed
                            </a>
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <a href="{{ route('podcasts.show', $podcast) }}"
                            class="text-xs text-gray-500 hover:text-purple-700 font-medium transition">
                            Details
                        </a>
                        <a href="{{ route('podcasts.delete.confirm', $podcast) }}"
                           class="text-xs text-gray-400 hover:text-red-600 font-medium transition">
                            Delete
                        </a>
                    </div>

                </div>
            @endforeach
        </div>

    @endif

</x-layouts.app>
