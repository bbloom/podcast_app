<x-layouts.app title="Add Output Destination — Done">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add an Output Destination</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Done!</span>
        </div>
    </div>

    <div class="bg-green-50 border border-green-300 rounded-lg p-6 mb-8 text-center">
        <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <p class="text-xl font-bold text-gray-800">Output Destination Saved!</p>
        <p class="text-lg text-gray-600 mt-1">Your destination has been saved and is ready to use.</p>
    </div>

    <div class="flex justify-between items-center">
        <a href="{{ route('output_destinations.create.step1') }}" class="text-sm text-purple-700 hover:underline flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add another destination
        </a>
        <a href="{{ route('output_destinations.index') }}" class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
            View All Destinations
        </a>
    </div>

</x-layouts.app>