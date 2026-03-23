<x-layouts.app title="Add Output Destination — Step 1">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add an Output Destination</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 1</span>
            <span class="mx-2">—</span>
            <span>Name your destination</span>
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-300 rounded-lg p-4 mb-6">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div class="text-sm text-amber-800">
                <p class="font-semibold mb-1">Before you begin</p>
                <p>This wizard will test your connection before saving. Please ensure your destination is set up and your credentials are ready before proceeding.</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('output_destinations.create.step1.submit') }}">
        @csrf

        <div class="mb-6">
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Destination Name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name') }}"
                placeholder="e.g. My Tech Blog or Client Website"
                required
                autofocus
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('name') border-red-400 @enderror"
            >
            @error('name')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">Choose a memorable name to identify this destination.</p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Next Step →
            </button>
        </div>

    </form>

</x-layouts.app>