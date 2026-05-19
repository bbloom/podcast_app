<x-layouts.app title="Delete Podcast Episode">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('podcast_episodes.index') }}" class="hover:text-purple-700 transition">Podcast Episodes</a>
            <span>›</span>
            <a href="{{ route('podcast_episodes.show', $episode) }}" class="hover:text-purple-700 transition">{{ $episode->title }}</a>
            <span>›</span>
            <span class="text-gray-700">Delete</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Delete Podcast Episode</h1>
    </div>

    @session('error')
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800">
            {{ $value }}
        </div>
    @endsession

    @if ($blockingReason)

        {{-- Blocked state --}}
        <div class="border border-red-200 bg-red-50 rounded-lg px-6 py-5 mb-8">
            <p class="text-sm font-semibold text-red-700 mb-1">This episode cannot be deleted.</p>
            <p class="text-sm text-red-600">{{ $blockingReason }}</p>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('podcast_episodes.show', $episode) }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                Back
            </a>
        </div>

    @else

        {{-- Safe to delete --}}
        <div class="border border-red-200 bg-red-50 rounded-lg px-6 py-5 mb-8">
            <p class="text-sm font-semibold text-red-700 mb-1">Are you sure you want to delete this episode?</p>
            <p class="text-sm text-red-600">
                <strong>{{ $episode->title }}</strong>
                @if ($episode->show)
                    — {{ $episode->show->title }}
                @endif
            </p>
            <p class="mt-3 text-xs text-red-500">This action cannot be undone.</p>
        </div>

        <form method="POST" action="{{ route('podcast_episodes.destroy', $episode) }}">
            @csrf
            @method('DELETE')
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('podcast_episodes.show', $episode) }}"
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