<x-layouts.app title="Delete Podcast Show">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('podcast_shows.index') }}" class="hover:text-purple-700 transition">Podcast Shows</a>
            <span>›</span>
            <a href="{{ route('podcast_shows.show', $show) }}" class="hover:text-purple-700 transition">{{ $show->title }}</a>
            <span>›</span>
            <span class="text-gray-700">Delete</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Delete Podcast Show</h1>

        <img 
            src="{{ $show->itunes_image }}" 
            alt="" 
            class="w-[100px] h-[100px] mt-4 rounded-lg object-cover border border-gray-200 flex-shrink-0"
        >
    </div>

    @session('error')
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800">
            {{ $value }}
        </div>
    @endsession

    @php $episodeCount = $show->episodes()->count(); @endphp

    @if ($episodeCount > 0)

        {{-- Blocked state — show has episodes --}}
        <div class="border border-red-200 bg-red-50 rounded-lg px-6 py-5 mb-8">
            <p class="text-sm font-semibold text-red-700 mb-1">This show cannot be deleted.</p>
            <p class="text-sm text-red-600">
                <strong>{{ $show->title }}</strong> has {{ $episodeCount }} {{ Str::plural('episode', $episodeCount) }}. 
                Please delete all episodes before deleting this show.
            </p>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('podcast_shows.show', $show) }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                Back
            </a>
        </div>

    @else

        {{-- Safe to delete --}}
        <div class="border border-red-200 bg-red-50 rounded-lg px-6 py-5 mb-8">
            <p class="text-sm font-semibold text-red-700 mb-1">Are you sure you want to delete this show?</p>
            <p class="text-sm text-red-600"><strong>{{ $show->title }}</strong></p>
            <p class="mt-3 text-xs text-red-500">This action cannot be undone.</p>
        </div>

        <form method="POST" action="{{ route('podcast_shows.destroy', $show) }}">
            @csrf
            @method('DELETE')
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('podcast_shows.show', $show) }}"
                   class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                    Yes, Delete
                </button>
            </div>
        </form>

    @endif

</x-layouts.app>