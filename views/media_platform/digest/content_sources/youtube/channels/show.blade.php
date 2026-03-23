<x-layouts.app title="{{ $youtubeChannel->title }}">

    {{-- Breadcrumb + heading --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('youtube.channels.index') }}" class="hover:text-purple-700 transition">← My Youtube Channels</a>
            <span>›</span>
            <span class="text-gray-700">{{ $youtubeChannel->title }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $youtubeChannel->title }}</h1>
            <a href="{{ route('youtube.channels.edit', $youtubeChannel) }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Edit
            </a>
        </div>
    </div>

    {{-- Channel details card --}}
    <div class="border border-purple-500 rounded-lg p-4 mb-8">
        <div class="flex gap-4">

            <div class="flex-shrink-0">
                @if ($youtubeChannel->thumbnail)
                    <img src="{{ $youtubeChannel->thumbnail }}"
                         alt="{{ $youtubeChannel->title }}"
                         class="w-20 h-20 rounded-full object-cover border border-gray-200">
                @else
                    <div class="w-20 h-20 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M15 10l4.553-2.276A1 1 0 0121 8.723v6.554a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
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
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Status</td>
                        <td class="py-1">
                            @if ($youtubeChannel->enabled)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                            @endif
                        </td>
                    </tr>
                    @if ($youtubeChannel->handle)
                        <tr>
                            <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Handle</td>
                            <td class="py-1 text-gray-800">{{ $youtubeChannel->handle }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Channel ID</td>
                        <td class="py-1"><code class="font-bold">{{ $youtubeChannel->channel_id }}</code></td>
                    </tr>
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">URL</td>
                        <td class="py-1">
                            <a href="{{ $youtubeChannel->channel_url }}" target="_blank"
                               class="text-purple-700 hover:underline break-all">
                                {{ $youtubeChannel->channel_url }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">RSS Feed</td>
                        <td class="py-1">
                            <a href="{{ $youtubeChannel->rss_url }}" target="_blank"
                               class="text-purple-700 hover:underline break-all">
                                {{ $youtubeChannel->rss_url }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Added</td>
                        <td class="py-1 text-gray-800">{{ $youtubeChannel->created_at->format('d M Y') }}</td>
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
        'attachRoute'        => 'youtube.channels.list_sources.attach',
        'updateRoute'        => 'youtube.channels.list_sources.update',
        'detachConfirmRoute' => 'youtube.channels.list_sources.detach.confirm',
        'sourceParam'        => $youtubeChannel,
    ])

    <div class="mt-6 text-sm">
        <a href="{{ route('youtube.channels.index') }}" class="hover:text-purple-700 transition">← My Youtube Channels</a>
    </div>

</x-layouts.app>