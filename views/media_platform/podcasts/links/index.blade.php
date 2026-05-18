<x-layouts.app title="Podcast Links">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Podcast Links</h1>
        <a href="{{ route('podcast_links.create') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + New Link
        </a>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    @if ($links->isEmpty())

        <div class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            <p class="text-sm font-semibold text-gray-500 mb-1">No links yet</p>
            <p class="text-sm text-gray-400 mb-6">Add the first podcast link to get started.</p>
            <a href="{{ route('podcast_links.create') }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Add a Link
            </a>
        </div>

    @else

        {{-- ── Sort controls ─────────────────────────────────────────────── --}}
        @php
            // Helper: build a sort URL toggling direction when the same column is clicked.
            $sortUrl = fn (string $col) => route('podcast_links.index', [
                'sort'      => $col,
                'direction' => ($sort === $col && $direction === 'asc') ? 'desc' : 'asc',
            ]);

            // Arrow indicator for the active column.
            $arrow = fn (string $col) => $sort === $col
                ? ($direction === 'asc' ? ' ↑' : ' ↓')
                : '';
        @endphp

        <div class="flex items-center gap-4 mb-4 text-xs text-gray-500">
            <span class="font-medium">Sort by:</span>
            <a href="{{ $sortUrl('id') }}"
               class="hover:text-purple-700 transition {{ $sort === 'id' ? 'text-purple-700 font-semibold' : '' }}">
                ID{{ $arrow('id') }}
            </a>
            <a href="{{ $sortUrl('title') }}"
               class="hover:text-purple-700 transition {{ $sort === 'title' ? 'text-purple-700 font-semibold' : '' }}">
                Title{{ $arrow('title') }}
            </a>
        </div>

        {{-- ── Links list ─────────────────────────────────────────────────── --}}
        <div class="flex flex-col gap-3">
            @foreach ($links as $link)
                <div class="border border-purple-400 rounded-lg px-5 py-4 flex items-center justify-between hover:border-gray-300 transition">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-xs text-gray-400 font-mono flex-shrink-0">#{{ $link->id }}</span>
                            <p class="text-sm font-semibold text-gray-800 truncate">
                                {{ $link->title ?? '(no title)' }}
                            </p>
                            @if (! $link->enabled)
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium flex-shrink-0">Disabled</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 truncate">{{ $link->link }}</p>
                    </div>
                    <div class="flex items-center gap-3 ml-4 flex-shrink-0">
                        <a href="{{ route('podcast_links.show', $link) }}"
                           class="text-xs text-gray-500 hover:text-purple-700 font-medium transition">
                            Detail
                        </a>
                        <a href="{{ route('podcast_links.edit', $link) }}"
                           class="text-xs text-gray-500 hover:text-purple-700 font-medium transition">
                            Edit
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ── Pagination ──────────────────────────────────────────────────── --}}
        @if ($links->hasPages())
            <div class="mt-6">{{ $links->links() }}</div>
        @endif

    @endif

</x-layouts.app>