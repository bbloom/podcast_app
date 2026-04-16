<x-layouts.app title="{{ $show->title }}">

    {{-- Breadcrumb + heading --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('podcast_shows.index') }}" class="hover:text-purple-700 transition">← Podcast Shows</a>
            <span>›</span>
            <span class="text-gray-700">{{ $show->title }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $show->title }}</h1>
            <a href="{{ route('podcast_shows.edit', $show) }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Edit
            </a>
        </div>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    {{-- Show details --}}
    <div class="border border-purple-500 rounded-lg p-6 mb-8">
        <div class="text-xl font-bold text-gray-800 mb-1 flex items-end gap-3"">
            <img 
                alt="{{  $show->title }} "
                class="h-[100px] w-[100px] object-cover border border-gray-200"
                src="{{ $show->itunes_image }}" 
            />
            
        </div>
        <p class="text-sm text-gray-500 mb-4">{{ $show->description }}</p>

        <table class="text-sm text-gray-600 border-collapse w-full">

            {{-- Core --}}
            <tr><td colspan="2" class="pt-4 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Core</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Slug</td>
                <td class="py-1 text-gray-800 font-mono text-xs">{{ $show->slug }}</td>
            </tr>
            @if ($show->rss_link)
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">RSS Link</td>
                <td class="py-1"><a href="{{ $show->rss_link }}" class="text-purple-700 hover:underline text-xs" target="_blank">{{ $show->rss_link }}</a></td>
            </tr>
            @endif

            {{-- iTunes --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">iTunes</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Language</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_language ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Type</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_type ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Author</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_author ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Category</td>
                <td class="py-1 text-gray-800">
                    {{ $show->itunes_category_primary ?? '—' }}
                    @if ($show->itunes_category_secondary)
                        / {{ $show->itunes_category_secondary }}
                    @endif
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Explicit</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_explicit ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Block</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_block ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Complete</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_complete ? 'Yes' : 'No' }}</td>
            </tr>

            {{-- Spotify --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Spotify</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Episode Limit</td>
                <td class="py-1 text-gray-800">{{ $show->spotify_limit === 0 ? 'No limit' : $show->spotify_limit }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Country of Origin</td>
                <td class="py-1 text-gray-800">{{ $show->spotify_country_of_origin }}</td>
            </tr>

            {{-- Website --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Website</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Visible</td>
                <td class="py-1">
                    @if ($show->website_enabled)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Publish On</td>
                <td class="py-1 text-gray-800">{{ $show->website_publish_on?->format('d M Y') ?? '—' }}</td>
            </tr>

            {{-- Meta --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Record</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Created</td>
                <td class="py-1 text-gray-800">{{ $show->created_at->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Updated</td>
                <td class="py-1 text-gray-800">{{ $show->updated_at->format('d M Y') }}</td>
            </tr>
        </table>
    </div>

    {{-- Episodes --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-800">
            Episodes
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $show->episodes()->count() }})</span>
        </h2>
        <a href="{{ route('pre_production_create_podcast_episode.step1') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
            + New Episode
        </a>
    </div>

    @php $episodes = $show->episodes()->orderByDesc('created_at')->get(); @endphp

    @if ($episodes->isEmpty())
        <div class="border border-gray-200 rounded-lg px-6 py-10 text-center text-sm text-gray-400">
            No episodes yet for this show.
        </div>
    @else
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Scheduled</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">RSS</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Website</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($episodes as $episode)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('podcast_episodes.show', $episode) }}"
                                   class="font-medium text-purple-700 hover:underline">
                                    {{ $episode->title }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $episode->status?->title ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $episode->scheduled_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-6 py-4">
                                @if ($episode->rss_feed_enabled)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">On</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Off</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($episode->website_enabled)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">On</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Off</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-6 text-sm">
        <a href="{{ route('podcast_shows.index') }}" class="hover:text-purple-700 transition">← Podcast Shows</a>
    </div>

</x-layouts.app>