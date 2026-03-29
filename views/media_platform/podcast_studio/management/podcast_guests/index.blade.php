<x-layouts.app title="Podcast Guests">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Podcast Guests</h1>
        <a href="{{ route('podcast_guests.create') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + New Guest
        </a>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    @if ($guests->isEmpty())

        <div class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
            </svg>
            <p class="text-sm font-semibold text-gray-500 mb-1">No guests yet</p>
            <p class="text-sm text-gray-400 mb-6">Add the first podcast guest to get started.</p>
            <a href="{{ route('podcast_guests.create') }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Add a Guest
            </a>
        </div>

    @else

        {{-- ── Sort controls ─────────────────────────────────────────────── --}}
        @php
            $sortUrl = fn (string $col) => route('podcast_guests.index', [
                'sort'      => $col,
                'direction' => ($sort === $col && $direction === 'asc') ? 'desc' : 'asc',
            ]);
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
            <a href="{{ $sortUrl('full_name') }}"
               class="hover:text-purple-700 transition {{ $sort === 'full_name' ? 'text-purple-700 font-semibold' : '' }}">
                Name{{ $arrow('full_name') }}
            </a>
        </div>

        <div class="flex flex-col gap-3">
            @foreach ($guests as $guest)
                <div class="border border-gray-200 rounded-lg px-5 py-4 flex items-center justify-between hover:border-gray-300 transition">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-xs text-gray-400 font-mono flex-shrink-0">#{{ $guest->id }}</span>
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $guest->full_name }}</p>
                            @if (! $guest->enabled)
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium flex-shrink-0">Disabled</span>
                            @endif
                        </div>
                        @if ($guest->profile_short)
                            <p class="text-xs text-gray-500 truncate">{{ $guest->profile_short }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 ml-4 flex-shrink-0">
                        <a href="{{ route('podcast_guests.show', $guest) }}"
                           class="text-xs text-gray-500 hover:text-purple-700 font-medium transition">
                            Detail
                        </a>
                        <a href="{{ route('podcast_guests.edit', $guest) }}"
                           class="text-xs text-gray-500 hover:text-purple-700 font-medium transition">
                            Edit
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($guests->hasPages())
            <div class="mt-6">{{ $guests->links() }}</div>
        @endif

    @endif

</x-layouts.app>