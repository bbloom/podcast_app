<x-layouts.app title="List Created">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Create a List</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Done!</span>
        </div>
    </div>

    <div class="bg-green-50 border border-green-300 rounded-lg p-6 mb-8 text-center">
        <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <p class="text-xl font-bold text-gray-800">List Created!</p>
        <p class="text-lg text-gray-600 mt-1">Your list is ready. Now add content sources to it.</p>
    </div>

    {{-- Static Site: deploy hook CTA --}}
    @if ($list && $list->output_type === \MediaPlatform\Digest\Enums\OutputType::StaticSite)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
            <p class="text-sm font-semibold text-blue-800 mb-2">Next step: Add a deploy hook</p>
            <p class="text-sm text-blue-700 mb-4">
                Your list uses Static Site delivery, which requires a deploy hook to trigger your static site rebuild automatically. Add one now so your digest pipeline is fully connected.
            </p>
            <a href="{{ route('deploy_hooks.create', ['triggerable_type' => 'digest_list', 'triggerable_id' => $list->id, 'redirect_to' => 'lists.show']) }}"
               class="inline-block bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Add Deploy Hook →
            </a>
        </div>
    @endif

    <div class="flex justify-between items-center">
        <a href="{{ route('lists.create.step1') }}" class="text-sm text-purple-700 hover:underline flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create another list
        </a>
        <a href="{{ route('lists.index') }}" class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
            View All Lists
        </a>
    </div>

</x-layouts.app>