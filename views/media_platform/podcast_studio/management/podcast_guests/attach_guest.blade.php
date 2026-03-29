<x-layouts.app title="Attach Guest to Episode">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('podcast_episodes.show', $episode) }}" class="hover:text-purple-700 transition">← {{ $episode->title }}</a>
            <span>›</span>
            <span class="text-gray-700">Attach Guest</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Attach a Guest</h1>
        <p class="mt-1 text-sm text-gray-500">Attaching to: <span class="font-semibold text-gray-700">{{ $episode->title }}</span></p>
    </div>

    @if ($guests->isEmpty())

        <div class="text-center py-16 text-gray-400">
            <p class="text-sm font-semibold text-gray-500 mb-1">No available guests</p>
            <p class="text-sm text-gray-400 mb-6">All enabled guests are already attached to this episode, or no enabled guests exist yet.</p>
            <div class="flex items-center justify-center gap-4">
                <a href="{{ route('podcast_guests.create') }}"
                   class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                    Create a New Guest
                </a>
                <a href="{{ route('podcast_episodes.show', $episode) }}"
                   class="text-sm text-gray-500 hover:text-gray-700 font-semibold transition">
                    Back to Episode
                </a>
            </div>
        </div>

    @else

        <div class="flex flex-col gap-3">
            @foreach ($guests as $guest)
                <div class="border border-gray-200 rounded-lg px-5 py-4 flex items-center justify-between hover:border-gray-300 transition">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $guest->full_name }}</p>
                        @if ($guest->profile_short)
                            <p class="text-xs text-gray-400 truncate">{{ $guest->profile_short }}</p>
                        @endif
                    </div>
                    <form method="POST"
                          action="{{ route('podcast_guests.attach.guest', [$episode, $guest]) }}"
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

        @if ($guests->hasPages())
            <div class="mt-6">{{ $guests->links() }}</div>
        @endif

        <div class="mt-6 text-sm">
            <a href="{{ route('podcast_episodes.show', $episode) }}" class="hover:text-purple-700 transition">← Back to Episode</a>
        </div>

    @endif

</x-layouts.app>