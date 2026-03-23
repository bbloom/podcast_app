{{-- 
    Processing mode selector — included inside each list checkbox row.
    
    Expects Alpine.js scope with:
      - listStates[listId].checked  (boolean)
      - listStates[listId].mode     (string: description|summary|search)
      - listStates[listId].terms    (string)
    
    Required variable: $list (the ListModel instance)
--}}

<div x-show="listStates[{{ $list->id }}].checked" x-cloak class="mt-3 pl-1 space-y-3">

    {{-- Processing mode --}}
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Processing mode</label>
        <div class="flex gap-2">
            @foreach (['description' => 'Description', 'summary' => 'Summary', 'search' => 'Search'] as $modeValue => $modeLabel)
                <label class="flex-1 cursor-pointer">
                    <input
                        type="radio"
                        name="processing_modes[{{ $list->id }}]"
                        value="{{ $modeValue }}"
                        x-model="listStates[{{ $list->id }}].mode"
                        class="sr-only peer"
                    >
                    <div class="border border-gray-200 rounded-lg px-3 py-2 text-xs font-semibold text-center text-gray-600 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        {{ $modeLabel }}
                    </div>
                </label>
            @endforeach
        </div>
        <p class="mt-1.5 text-xs text-gray-400">
            <span x-show="listStates[{{ $list->id }}].mode === 'description'">Store title and description only — no AI processing.</span>
            <span x-show="listStates[{{ $list->id }}].mode === 'summary'" x-cloak>Fetch content and generate an AI summary for every new item.</span>
            <span x-show="listStates[{{ $list->id }}].mode === 'search'" x-cloak>Only summarise items matching your search terms.</span>
        </p>
    </div>

    {{-- Search terms (shown only in search mode) --}}
    <div x-show="listStates[{{ $list->id }}].mode === 'search'" x-cloak>
        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Search terms</label>
        <input
            type="text"
            name="search_terms[{{ $list->id }}]"
            x-model="listStates[{{ $list->id }}].terms"
            placeholder="e.g. artificial intelligence, machine learning, LLM"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
        >
        <p class="mt-1 text-xs text-gray-400">Comma-separated. Matched against title, description, then content via AI semantic check.</p>
    </div>

</div>
