<x-layouts.app title="Planning Episodes">
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Planning Episodes</h1>
        <a href="{{ route('podcast_episodes_planning.wizard.create.step1') }}"
           class="px-4 py-2 bg-purple-700 text-white rounded hover:bg-purple-800 text-sm font-semibold">
            + Create New Episode
        </a>
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
                ? ($dir === 'asc' ? '▲' : '▼')
                : '';
        @endphp

        <div class="border border-purple-300 rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-purple-50 border-b border-purple-300 text-purple-700 font-semibold">
                    <tr>
                        <th class="px-4 py-3">
                            <a href="{{ $sortLink('episode_number') }}">
                                # {{ $sortIcon('episode_number') }}
                            </a>
                        </th>
                        <th class="px-4 py-3">
                            <a href="{{ $sortLink('title') }}">
                                Title {{ $sortIcon('title') }}
                            </a>
                        </th>
                        <th class="px-4 py-3">
                            <a href="{{ $sortLink('show') }}">
                                Show {{ $sortIcon('show') }}
                            </a>
                        </th>
                        <th class="px-4 py-3">
                            <a href="{{ $sortLink('status') }}">
                                Status {{ $sortIcon('status') }}
                            </a>
                        </th>
                        <th class="px-4 py-3">
                            <a href="{{ $sortLink('scheduled_date') }}">
                                Date {{ $sortIcon('scheduled_date') }}
                            </a>
                        </th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($episodes as $episode)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500">
                                {{ $episode->episode_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-800">
                                <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
                                   class="hover:text-purple-700">
                                    {{ $episode->title }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $episode->show->title ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @include('media_platform.podcasts.planning.crud._status_badge', ['status' => $episode->status])
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $episode->scheduled_date?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 space-x-3 whitespace-nowrap">
                                <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
                                   class="text-purple-700 hover:underline">View</a>
                                <a href="{{ route('podcast_episodes_planning.edit', $episode) }}"
                                   class="text-purple-700 hover:underline">Edit</a>
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