<x-layouts.app title="Attach Link — {{ $episode->formatted_title }}">
<div class="max-w-3xl mx-auto px-4 py-8">

    <p class="text-sm text-gray-500 mb-1">
        <a href="{{ route('podcast_episodes_planning.index') }}" class="hover:underline text-purple-700">Planning Episodes</a>
        &rsaquo;
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}" class="hover:underline text-purple-700">{{ $episode->formatted_title }}</a>
        &rsaquo; Attach Link
    </p>

    <h1 class="text-2xl font-bold text-gray-800 mb-6">Attach Link</h1>

    @if ($links->isEmpty())
        <div class="p-6 text-center text-gray-500 border border-gray-200 rounded-lg">
            No available links to attach.
        </div>
    @else
        <div class="border border-purple-300 rounded-lg overflow-hidden">
            <ul class="divide-y divide-gray-100">
                @foreach ($links as $link)
                    <li class="flex items-center justify-between px-5 py-3">
                        <div>
                            <p class="text-gray-800 text-sm font-medium">{{ $link->title }}</p>
                            <p class="text-gray-400 text-xs truncate max-w-md">{{ $link->link }}</p>
                        </div>
                        <form method="POST"
                              action="{{ route('podcast_episodes_planning.links.attach', [$episode, $link]) }}">
                            @csrf
                            <button type="submit"
                                    class="px-3 py-1 text-xs bg-purple-700 text-white rounded hover:bg-purple-800">
                                Attach
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-4">{{ $links->links() }}</div>
    @endif

    <div class="mt-6">
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
           class="text-sm text-gray-500 hover:underline">← Back to episode</a>
    </div>

</div>
</x-layouts.app>