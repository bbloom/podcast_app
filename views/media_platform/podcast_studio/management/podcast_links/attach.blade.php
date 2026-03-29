<x-layouts.app title="Attach Link to Episode">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('podcast_episodes.show', $episode) }}" class="hover:text-purple-700 transition">← {{ $episode->title }}</a>
            <span>›</span>
            <span class="text-gray-700">Attach Link</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Attach a Link</h1>
        <p class="mt-1 text-sm text-gray-500">Attaching to: <span class="font-semibold text-gray-700">{{ $episode->title }}</span></p>
    </div>

    @if ($links->isEmpty())

        <div class="text-center py-16 text-gray-400">
            <p class="text-sm font-semibold text-gray-500 mb-1">No available links</p>
            <p class="text-sm text-gray-400 mb-6">All enabled links are already attached to this episode, or no enabled links exist yet.</p>
            <div class="flex items-center justify-center gap-4">
                <a href="{{ route('podcast_links.create') }}"
                   class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                    Create a New Link
                </a>
                <a href="{{ route('podcast_episodes.show', $episode) }}"
                   class="text-sm text-gray-500 hover:text-gray-700 font-semibold transition">
                    Back to Episode
                </a>
            </div>
        </div>

    @else

        <div class="flex flex-col gap-3">
            @foreach ($links as $link)
                <div class="border border-gray-200 rounded-lg px-5 py-4 flex items-center justify-between hover:border-gray-300 transition">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">
                            {{ $link->title ?? '(no title)' }}
                        </p>
                        <p class="text-xs text-gray-400 truncate">{{ $link->link }}</p>
                    </div>
                    <form method="POST"
                          action="{{ route('podcast_links.attach', [$episode, $link]) }}"
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

        {{-- Pagination --}}
        @if ($links->hasPages())
            <div class="mt-6">{{ $links->links() }}</div>
        @endif

        <div class="mt-6 text-sm">
            <a href="{{ route('podcast_episodes.show', $episode) }}" class="hover:text-purple-700 transition">← Back to Episode</a>
        </div>

    @endif

</x-layouts.app>