<x-layouts.app title="Podcast Episodes">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Podcast Episodes</h1>
        <a href="{{ route('pre_production_create_podcast_episode.step1') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + New Episode
        </a>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    @if ($episodes->total() === 0)

        <div class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
            </svg>
            <p class="text-sm font-semibold text-gray-500 mb-1">No episodes yet</p>
            <p class="text-sm text-gray-400 mb-6">Create your first podcast episode to get started.</p>
            <a href="{{ route('pre_production_create_podcast_episode.step1') }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Create an Episode
            </a>
        </div>

    @else

        @php
            /**
             * Build a sort URL for a given column.
             * If the column is already active, flip the direction; otherwise default to asc.
             */
            $sortUrl = function (string $key) use ($sort, $dir) {
                $newDir = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
                return request()->fullUrlWithQuery(['sort' => $key, 'dir' => $newDir, 'page' => 1]);
            };

            $sortIcon = function (string $key) use ($sort, $dir) {
                if ($sort !== $key) return '↕';
                return $dir === 'asc' ? '↑' : '↓';
            };
        @endphp

        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                            <a href="{{ $sortUrl('id') }}" class="hover:text-purple-700 transition">
                                ID <span class="text-[10px]">{{ $sortIcon('id') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                            <a href="{{ $sortUrl('title') }}" class="hover:text-purple-700 transition">
                                Title <span class="text-[10px]">{{ $sortIcon('title') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                            <a href="{{ $sortUrl('show') }}" class="hover:text-purple-700 transition">
                                Show <span class="text-[10px]">{{ $sortIcon('show') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                            <a href="{{ $sortUrl('status') }}" class="hover:text-purple-700 transition">
                                Status <span class="text-[10px]">{{ $sortIcon('status') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                            <a href="{{ $sortUrl('scheduled_date') }}" class="hover:text-purple-700 transition">
                                Scheduled <span class="text-[10px]">{{ $sortIcon('scheduled_date') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">RSS</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Website</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-400">
                    @foreach ($episodes as $episode)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-gray-500 tabular-nums">{{ $episode->id }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('podcast_episodes.show', $episode) }}"
                                   class="font-medium text-purple-700 hover:underline">
                                    {{ $episode->title }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('podcast_shows.show', $episode->show) }}"
                                   class="hover:text-purple-700 transition">
                                    <img 
                                        src="{{ $episode->show->itunes_image }}" 
                                        alt="{{ $episode->show->title }}" 
                                        class="w-[75px] h-[75px] rounded-lg object-cover border border-gray-200 flex-shrink-0"
                                    >
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $episode->status?->label() ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $episode->scheduled_date?->format('M d Y') ?? '—' }}</td>
                            <td class="px-6 py-4">
                                @if ($episode->rss_feed_enabled)
                                    <span class="inline-flex items-center px-2 py-0.5">✅</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5">❌</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($episode->website_enabled)
                                    <span class="inline-flex items-center px-2 py-0.5">✅</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5">❌</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a 
                                    href="{{ route('podcast_episodes.show', $episode) }}"             class="inline-block bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition"
                                >
                                    Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($episodes->hasPages())
            <div class="mt-4">{{ $episodes->links() }}</div>
        @endif

    @endif

</x-layouts.app>