<x-layouts.app title="Delete Planning Episode">
<div class="max-w-xl mx-auto px-4 py-8">

    <p class="text-sm text-gray-500 mb-4">
        <a href="{{ route('podcast_episodes_planning.index') }}" class="hover:underline text-purple-700">
            Planning Episodes
        </a>
        &rsaquo;
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}" class="hover:underline text-purple-700">
            {{ $episode->formatted_title }}
        </a>
        &rsaquo; Delete
    </p>

    <div class="border border-red-300 rounded-lg overflow-hidden">
        <div class="bg-red-50 border-b border-red-300 px-5 py-3">
            <h1 class="text-lg font-bold text-red-700">Delete Planning Episode</h1>
        </div>
        <div class="px-5 py-5 space-y-4">
            <p class="text-gray-800">
                Are you sure you want to delete
                <strong>{{ $episode->formatted_title }}</strong>?
            </p>
            <p class="text-sm text-gray-600">
                This is a permanent, hard delete. The planning record and all associated data will be
                removed immediately with no way to recover it.
            </p>

            <div class="flex items-center gap-4 pt-2">
                <form method="POST" action="{{ route('podcast_episodes_planning.destroy', $episode) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-5 py-2 bg-red-600 text-white rounded hover:bg-red-700 font-semibold text-sm">
                        Yes, Delete Permanently
                    </button>
                </form>
                <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
                   class="text-gray-500 hover:underline text-sm">Cancel</a>
            </div>
        </div>
    </div>

</div>
</x-layouts.app>