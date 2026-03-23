<x-layouts.app title="List Created">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Create a List</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Done</span>
            <span class="mx-2">—</span>
            <span>Your list has been created</span>
        </div>
    </div>

    {{-- Success panel --}}
    <div class="bg-green-50 border border-green-300 rounded-lg p-6 mb-8">
        <div class="flex gap-3">
            <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-green-800 mb-1">Your list has been created successfully.</p>
                <p class="text-sm text-green-700">The next step is to add some sources — YouTube channels, podcasts, or RSS feeds. You can do that from each source's wizard.</p>
            </div>
        </div>
    </div>

    {{-- Next actions --}}
    <div class="flex flex-col gap-3">
        <a href="{{ route('lists.index') }}"
           class="flex items-center justify-between border border-gray-200 rounded-lg px-5 py-4 hover:border-purple-300 hover:bg-purple-50 transition group">
            <div>
                <p class="text-sm font-semibold text-gray-800 group-hover:text-purple-700">View all my lists</p>
                <p class="text-xs text-gray-500 mt-0.5">See and manage all your lists.</p>
            </div>
            <svg class="w-4 h-4 text-gray-400 group-hover:text-purple-700 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <a href="{{ route('lists.create.step1') }}"
           class="flex items-center justify-between border border-gray-200 rounded-lg px-5 py-4 hover:border-purple-300 hover:bg-purple-50 transition group">
            <div>
                <p class="text-sm font-semibold text-gray-800 group-hover:text-purple-700">Create another list</p>
                <p class="text-xs text-gray-500 mt-0.5">Add another list with a different schedule or delivery type.</p>
            </div>
            <svg class="w-4 h-4 text-gray-400 group-hover:text-purple-700 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

</x-layouts.app>
