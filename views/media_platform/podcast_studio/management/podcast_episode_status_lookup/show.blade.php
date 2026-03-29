<x-layouts.app title="{{ $status->title }}">

    {{-- Breadcrumb + heading --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('podcast_episode_status_lookup.index') }}" class="hover:text-purple-700 transition">← Episode Statuses</a>
            <span>›</span>
            <span class="text-gray-700">{{ $status->title }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $status->title }}</h1>
            @can('admin')
                <a href="{{ route('podcast_episode_status_lookup.edit', $status) }}"
                   class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                    Edit
                </a>
            @endcan
        </div>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    {{-- Status details --}}
    <div class="border border-purple-500 rounded-lg p-6 mb-8">
        <p class="text-xl font-bold text-gray-800 mb-1">{{ $status->title }}</p>
        <p class="text-sm text-gray-500 mb-4">{{ $status->description }}</p>

        <table class="text-sm text-gray-600 border-collapse w-full">

            {{-- Detail --}}
            <tr><td colspan="2" class="pt-4 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Detail</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Title</td>
                <td class="py-1 text-gray-800">{{ $status->title }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Description</td>
                <td class="py-1 text-gray-800">{{ $status->description }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Status</td>
                <td class="py-1">
                    @if ($status->enabled)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                    @endif
                </td>
            </tr>

            {{-- Record --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Record</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Created</td>
                <td class="py-1 text-gray-800">{{ $status->created_at->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Updated</td>
                <td class="py-1 text-gray-800">{{ $status->updated_at->format('d M Y') }}</td>
            </tr>

        </table>
    </div>

    {{-- Episodes using this status --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-800">
            Episodes
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $status->episodes()->count() }})</span>
        </h2>
    </div>

    @php $episodes = $status->episodes()->with('show')->orderByDesc('created_at')->get(); @endphp

    @if ($episodes->isEmpty())
        <div class="border border-gray-200 rounded-lg px-6 py-10 text-center text-sm text-gray-400">
            No episodes are currently using this status.
        </div>
    @else
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Show</th>
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
                            <td class="px-6 py-4 text-gray-600">
                                <a href="{{ route('podcast_shows.show', $episode->show) }}"
                                   class="hover:text-purple-700 transition">
                                    {{ $episode->show?->title ?? '—' }}
                                </a>
                            </td>
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

    @can('admin')
        <div class="mt-6 mb-2">
            <a href="{{ route('podcast_episode_status_lookup.delete.confirm', $status) }}"
               class="text-sm text-red-500 hover:text-red-700 font-medium transition">
                Delete this status
            </a>
        </div>
    @endcan

    <div class="mt-6 text-sm">
        <a href="{{ route('podcast_episode_status_lookup.index') }}" class="hover:text-purple-700 transition">← Episode Statuses</a>
    </div>

</x-layouts.app>