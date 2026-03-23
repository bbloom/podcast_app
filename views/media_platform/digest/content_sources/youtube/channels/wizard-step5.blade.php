<x-layouts.app title="Add Youtube Channel Wizard">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add a Youtube Channel</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 5</span>
            <span>of 5</span>
            <span class="mx-2">—</span>
            <span>All done!</span>
        </div>

        {{-- Step dots --}}
        <div class="flex items-center gap-2 mt-3">
            <div class="w-3 h-3 rounded-full bg-purple-300"></div>
            <div class="w-16 h-px bg-purple-300"></div>
            <div class="w-3 h-3 rounded-full bg-purple-300"></div>
            <div class="w-16 h-px bg-purple-300"></div>
            <div class="w-3 h-3 rounded-full bg-purple-300"></div>
            <div class="w-16 h-px bg-purple-300"></div>
            <div class="w-3 h-3 rounded-full bg-purple-300"></div>
            <div class="w-16 h-px bg-purple-300"></div>
            <div class="w-3 h-3 rounded-full bg-purple-700"></div>
        </div>
    </div>

    {{-- Success message --}}
    <div class="bg-green-50 border border-green-300 rounded-lg p-6 mb-8 text-center">
        <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <p class="text-xl font-bold text-gray-800">Channel Added Successfully!</p>
        <p class="text-lg text-gray-600 mt-1">
            <span class="font-semibold text-purple-700">{{ $title }}</span> has been added to your channels and assigned to {{ $listCount }} {{ Str::plural('list', $listCount) }}.
        </p>
    </div>

    {{-- Actions --}}
    <div class="flex justify-between items-center">
        <a href="{{ route('youtube.channels.create.step1') }}"
           class="text-sm text-purple-700 hover:underline flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add another channel
        </a>

        <a href="{{ route('youtube.channels.index') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
            View all my channels
        </a>
    </div>

</x-layouts.app>
