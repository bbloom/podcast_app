<x-layouts.app title="Delete Episode Draft">

    {{-- Breadcrumb --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('podcast_episode_drafts.index') }}" class="hover:text-purple-700 transition">Podcastd Episode Drafts</a>
            <span>›</span>
            <a href="{{ route('podcast_episode_drafts.show', $draft) }}" class="hover:text-purple-700 transition">{{ $draft->title }}</a>
            <span>›</span>
            <span class="text-gray-700">Delete</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Delete Podcast Episode Draft</h1>
    </div>

    {{-- Confirmation --}}
    <div class="border border-red-200 bg-red-50 rounded-lg px-6 py-5 mb-8">
        <p class="text-sm font-semibold text-red-700 mb-1">Are you sure you want to delete this draft?</p>
        <p class="text-sm text-red-600">
            <strong>{{ $draft->title }}</strong>
            @if ($draft->show)
                — {{ $draft->show->title }}
            @endif
        </p>
        @if ($draft->links->isNotEmpty())
            <p class="mt-2 text-sm text-red-600">
                This draft has {{ $draft->links->count() }} {{ Str::plural('link', $draft->links->count()) }} attached.
                They will be detached (not deleted) when the draft is removed.
            </p>
        @endif
        <p class="mt-3 text-xs text-red-500">This action cannot be undone.</p>
    </div>

    <form method="POST" action="{{ route('podcast_episode_drafts.destroy', $draft) }}">
        @csrf
        @method('DELETE')
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('podcast_episode_drafts.show', $draft) }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                Cancel
            </a>
            <button type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Yes, Delete
            </button>
        </div>
    </form>

</x-layouts.app>