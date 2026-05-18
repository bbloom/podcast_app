<x-layouts.app title="Attach Episode to Guest">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('podcast_guests.show', $guest) }}" class="hover:text-purple-700 transition">← {{ $guest->full_name }}</a>
            <span>›</span>
            <span class="text-gray-700">Attach Episode</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Attach an Episode</h1>
        <p class="mt-1 text-sm text-gray-500">Attaching to: <span class="font-semibold text-gray-700">{{ $guest->full_name }}</span></p>
    </div>

    @if ($episodes->isEmpty())

        <div class="text-center py-16 text-gray-400">
            <p class="text-sm font-semibold text-gray-500 mb-1">No available episodes</p>
            <p class="text-sm text-gray-400 mb-6">All episodes are already attached to this guest, or no episodes exist yet.</p>
            <a href="{{ route('podcast_guests.show', $guest) }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold transition">
                Back to Guest
            </a>
        </div>

    @else

        <div class="flex flex-col gap-3">
            @foreach ($episodes as $episode)
                <div class="border border-gray-200 rounded-lg px-5 py-4 flex items-center justify-between hover:border-gray-300 transition">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $episode->title }}</p>
                        <p class="text-xs text-gray-400">{{ $episode->show?->title ?? '—' }}</p>
                    </div>
                    <form method="POST"
                          action="{{ route('podcast_guests.attach.episode', [$guest, $episode]) }}"
                          class="ml-4 flex-shrink-0">
                        @csrf
                        <button type="submit"
                                class="bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                            Attach
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        @if ($episodes->hasPages())
            <div class="mt-6">{{ $episodes->links() }}</div>
        @endif

        <div class="mt-6 text-sm">
            <a href="{{ route('podcast_guests.show', $guest) }}" class="hover:text-purple-700 transition">← Back to Guest</a>
        </div>

    @endif

</x-layouts.app>