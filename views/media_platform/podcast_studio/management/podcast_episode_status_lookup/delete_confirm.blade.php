<x-layouts.app title="Delete Episode Status">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('podcast_episode_status_lookup.index') }}" class="hover:text-purple-700 transition">Episode Statuses</a>
            <span>›</span>
            <span class="text-gray-700">Delete</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Delete Episode Status: {{ strtoupper($status->title) }}</h1>
    </div>

    @session('error')
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800">
            {{ $value }}
        </div>
    @endsession

    <p class="text-sm text-gray-600 mb-8">
        Are you sure you want to delete <span class="font-semibold text-gray-800">{{ $status->title }}</span>? This action cannot be undone.
    </p>

    <form method="POST" action="{{ route('podcast_episode_status_lookup.destroy', $status) }}">
        @csrf
        @method('DELETE')

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('podcast_episode_status_lookup.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                Cancel
            </a>
            <button type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Delete "{{ $status->title }}"
            </button>
        </div>

    </form>

</x-layouts.app>