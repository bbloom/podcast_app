<x-layouts.app title="Attach Guest — {{ $episode->formatted_title }}">
<div class="max-w-3xl mx-auto px-4 py-8">

    {{-- Breadcrumb --}}
    <p class="text-base text-gray-500 mb-4">
        <a href="{{ route('podcast_episodes_planning.index') }}" class="hover:underline text-purple-700">Planning Episodes</a>
        &rsaquo;
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}" class="hover:underline text-purple-700">{{ $episode->formatted_title }}</a>
        &rsaquo; Attach Guest
    </p>

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        @if ($episode->show->itunes_image)
            <img src="{{ $episode->show->itunes_image }}"
                 alt="{{ $episode->show->title }}"
                 class="w-16 h-16 rounded object-cover border border-purple-200 flex-shrink-0">
        @endif
        <h1 class="text-3xl font-bold text-gray-800">Attach Guest</h1>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('podcast_episodes_planning.guests.attach.index', $episode) }}"
          class="mb-4 flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search guests… (case sensitive!)"
               class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none">
        <button type="submit"
                class="px-4 py-2 bg-purple-700 text-white text-sm font-semibold rounded hover:bg-purple-800">
            Search
        </button>
        @if (request('search'))
            <a href="{{ route('podcast_episodes_planning.guests.attach.index', $episode) }}"
               class="px-4 py-2 border border-gray-400 text-gray-700 text-sm font-semibold rounded hover:bg-gray-50">
                Clear
            </a>
        @endif
    </form>



    @if ($guests->isEmpty())
        <div class="p-6 text-center text-base text-gray-500 border border-gray-200 rounded-lg">
            No available guests to attach.
        </div>
    @else
        <div class="border border-purple-300 rounded-lg overflow-hidden">
            <ul class="divide-y divide-gray-100">
                @foreach ($guests as $guest)
                    <li class="flex items-center justify-between px-5 py-3 bg-gray-50 hover:bg-white">
                        @if ($guest->image_thumbnail_url)
                            <img src="{{ $guest->image_thumbnail_url }}"
                                 alt="{{ $guest->full_name }}"
                                 class="w-24 h-24 rounded object-cover border border-purple-200">
                        @endif
                        <span class="text-base text-gray-800">{{ $guest->full_name }}</span>
                        <form method="POST"
                              action="{{ route('podcast_episodes_planning.guests.attach', [$episode, $guest]) }}">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-1.5 text-sm font-semibold bg-purple-700 text-white rounded hover:bg-purple-800">
                                Attach
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-4">{{ $guests->links() }}</div>
    @endif

    <div class="mt-6">
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
           class="px-4 py-2 text-sm font-semibold border border-gray-400 text-gray-700 rounded hover:bg-gray-50">
            ← Back to Episode
        </a>
    </div>

</div>
</x-layouts.app>