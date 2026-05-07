<x-layouts.app :title="'Delete — ' . $video->title">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('videos.index') }}" class="hover:text-purple-700 transition">← Videos</a>
            <span>›</span>
            <a href="{{ route('videos.show', $video) }}" class="hover:text-purple-700 transition">{{ $video->title }}</a>
            <span>›</span>
            <span class="text-gray-700">Delete</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Delete Video</h1>
    </div>

    <div class="border border-red-300 rounded-lg p-6 bg-red-50 max-w-xl">
        <p class="text-sm text-red-800 mb-4">
            Are you sure you want to delete <strong>{{ $video->title }}</strong>? This action cannot be undone.
        </p>

        <div class="flex items-center gap-4">
            <form method="POST" action="{{ route('videos.destroy', $video) }}">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                    Yes, Delete
                </button>
            </form>
            <a href="{{ route('videos.show', $video) }}"
               class="text-sm text-gray-500 hover:text-purple-700 transition">Cancel</a>
        </div>
    </div>

</x-layouts.app>