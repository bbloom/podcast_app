<x-layouts.app title="Planning Episodes">
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">All Planning Episodes</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('podcasts.dashboard') }}"
            class="px-4 py-2 bg-green-700 text-white rounded hover:bg-green-800 text-sm font-semibold">
                Podcasts Dashboard
            </a>
            <a href="{{ route('podcast_episodes_planning.wizard.create.step1') }}"
            class="px-4 py-2 bg-purple-700 text-white rounded hover:bg-purple-800 text-sm font-semibold">
                + Create New Episode
            </a>
        </div>
    </div>

    @session('success')
        <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-800 rounded">{{ $value }}</div>
    @endsession

    @session('error')
        <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-800 rounded">{{ $value }}</div>
    @endsession

    @if ($episodes->isEmpty())
        <div class="p-8 text-center text-gray-500 border border-gray-200 rounded-lg">
            No planning episodes yet.
        </div>
    @else

        {{-- Sort helpers --}}
        @php
            $sortLink = fn (string $col) => route('podcast_episodes_planning.index', [
                'sort' => $col,
                'dir'  => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc',
            ]);
            $sortIcon = fn (string $col) => $sort === $col
                ? ($dir === 'asc' ? '↑' : '↓')
                : '↕';
        @endphp

        <div class="border border-purple-300 rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-purple-50 border-b border-purple-300 text-purple-700 font-semibold">
                    <tr>
                        <th class="px-4 py-3">
                            <a href="{{ $sortLink('show') }}" class="inline-flex items-center gap-1 hover:text-purple-900">
                                Show <span class="text-base {{ $sort === 'show' ? 'text-purple-700' : 'text-purple-700' }}">{{ $sortIcon('show') }}</span>
                            </a>
                        </th>
                        <th class="px-4 py-3">
                            <a href="{{ $sortLink('episode_number') }}" class="inline-flex items-center gap-1 hover:text-purple-900">
                                # <span class="text-base {{ $sort === 'episode_number' ? 'text-purple-700' : 'text-purple-700' }}">{{ $sortIcon('episode_number') }}</span>
                            </a>
                        </th>
                        <th class="px-4 py-3">
                            <a href="{{ $sortLink('title') }}" class="inline-flex items-center gap-1 hover:text-purple-900">
                                Title <span class="text-base {{ $sort === 'title' ? 'text-purple-700' : 'text-purple-700' }}">{{ $sortIcon('title') }}</span>
                            </a>
                        </th>                        
                        <th class="px-4 py-3">
                            <a href="{{ $sortLink('status') }}" class="inline-flex items-center gap-1 hover:text-purple-900">
                                Status <span class="text-base {{ $sort === 'status' ? 'text-purple-700' : 'text-purple-700' }}">{{ $sortIcon('status') }}</span>
                            </a>
                        </th>
                        <th class="px-4 py-3">
                            <a href="{{ $sortLink('scheduled_date') }}" class="inline-flex items-center gap-1 hover:text-purple-900">
                                Date <span class="text-base {{ $sort === 'scheduled_date' ? 'text-purple-700' : 'text-purple-700' }}">{{ $sortIcon('scheduled_date') }}</span>
                            </a>
                        </th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-300 ">
                    @foreach ($episodes as $episode)
                        <tr class="hover:bg-gray-50 hover:bg-white">
                            <td class="px-4 py-3">
                                @if ($episode->show->itunes_image)
                                    <img src="{{ $episode->show->itunes_image }}"
                                        alt="{{ $episode->show->title }}"
                                        class="w-16 h-16 rounded object-cover border border-purple-200">
                                @else
                                    <span class="text-gray-600">{{ $episode->show->title ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $episode->episode_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3 font-bold text-gray-800 text-lg">
                                <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
                                   class="hover:text-purple-700 hover:underline">
                                    {{ $episode->title }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                @include('media_platform.podcasts.planning.crud._status_badge', ['status' => $episode->status])
                            </td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                {{ $episode->scheduled_date?->format('M j, Y') ?? '—' }}
                            </td>

                             <td class="px-6 py-3 text-right">
                                <div class="flex flex-col items-end gap-1.5">
                                    <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
                                    class="inline-block bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                        Details
                                    </a>

                                    @if ($episode->status === \MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus::ready_to_record)
                                        <a href="{{ route('podcast_episodes_planning.recording.show', $episode) }}"
                                        class="inline-block bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                            Record
                                        </a>
                                    @endif
                                    
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $episodes->links() }}
        </div>

    @endif
</div>
</x-layouts.app>