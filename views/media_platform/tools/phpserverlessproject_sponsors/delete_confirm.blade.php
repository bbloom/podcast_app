<x-layouts.app title="Delete Sponsor">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('phpserverlessproject_sponsors.index') }}" class="hover:text-purple-700 transition">PHPServerlessProject Sponsors</a>
            <span>›</span>
            <a href="{{ route('phpserverlessproject_sponsors.show', $sponsor) }}" class="hover:text-purple-700 transition">{{ $sponsor->full_name }}</a>
            <span>›</span>
            <span class="text-gray-700">Delete</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Delete Sponsor</h1>
    </div>

    <div class="border border-red-200 bg-red-50 rounded-lg px-6 py-5 mb-8">
        <p class="text-sm font-semibold text-red-700 mb-1">Are you sure you want to delete this sponsor?</p>
        <p class="text-sm text-red-600"><strong>{{ $sponsor->full_name }}</strong></p>
        <p class="mt-3 text-xs text-red-500">This action cannot be undone.</p>
    </div>

    <form method="POST" action="{{ route('phpserverlessproject_sponsors.destroy', $sponsor) }}">
        @csrf
        @method('DELETE')
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('phpserverlessproject_sponsors.show', $sponsor) }}"
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