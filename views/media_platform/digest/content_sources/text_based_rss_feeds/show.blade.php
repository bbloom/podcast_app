<x-layouts.app title="{{ $textBasedRssFeed->title }}">

    {{-- Breadcrumb + heading --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('text_based_rss_feeds.index') }}" class="hover:text-purple-700 transition">← My Text Based RSS Feeds</a>
            <span>›</span>
            <span class="text-gray-700">{{ $textBasedRssFeed->title }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $textBasedRssFeed->title }}</h1>
            <a href="{{ route('text_based_rss_feeds.edit', $textBasedRssFeed) }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Edit
            </a>
        </div>
    </div>

    {{-- Feed details card --}}
    <div class="border border-purple-500 rounded-lg p-4 mb-8">
        <div class="flex gap-4">

            <div class="flex-shrink-0">
                @if ($textBasedRssFeed->thumbnail)
                    <img src="{{ $textBasedRssFeed->thumbnail }}"
                         alt="{{ $textBasedRssFeed->title }}"
                         class="w-20 h-20 rounded-lg object-cover border border-gray-200">
                @else
                    <div class="w-20 h-20 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M6 5c7.18 0 13 5.82 13 13M6 11a7 7 0 017 7M6 17a1 1 0 110-2 1 1 0 010 2z"/>
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
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Status</td>
                        <td class="py-1">
                            @if ($textBasedRssFeed->enabled)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                            @endif
                        </td>
                    </tr>
                    @if ($textBasedRssFeed->site_url)
                        <tr>
                            <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Website</td>
                            <td class="py-1">
                                <a href="{{ $textBasedRssFeed->site_url }}" target="_blank"
                                   class="text-purple-700 hover:underline break-all">
                                    {{ $textBasedRssFeed->site_url }}
                                </a>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">RSS Feed</td>
                        <td class="py-1">
                            <a href="{{ $textBasedRssFeed->rss_url }}" target="_blank"
                               class="text-purple-700 hover:underline break-all">
                                {{ $textBasedRssFeed->rss_url }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Added</td>
                        <td class="py-1 text-gray-800">{{ $textBasedRssFeed->created_at->format('d M Y') }}</td>
                    </tr>
                </table>
            </div>

        </div>
    </div>

    {{-- List memberships panel (attach / edit / detach) --}}
    @include('components.list-sources-panel', [
        'listSources'        => $listSources,
        'tracking'           => $tracking,
        'availableLists'     => $availableLists,
        'attachRoute'        => 'text_based_rss_feeds.list_sources.attach',
        'updateRoute'        => 'text_based_rss_feeds.list_sources.update',
        'detachConfirmRoute' => 'text_based_rss_feeds.list_sources.detach.confirm',
        'sourceParam'        => $textBasedRssFeed,
    ])

    <div class="mt-6 text-sm">
        <a href="{{ route('text_based_rss_feeds.index') }}" class="hover:text-purple-700 transition">← My Text Based RSS Feeds</a>
    </div>

</x-layouts.app>