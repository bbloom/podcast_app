{{--
    List Sources Panel — reused on the show pages for YouTube channels, podcasts,
    and text-based RSS feeds.

    Required variables passed in by the including view:
      $listSources    — paginated collection of ListSource models (with 'list' eager-loaded)
      $tracking       — collection of ListSourceTracking models keyed by list_source_id
      $availableLists — collection of ListModel records not yet attached to this source
      $attachRoute    — named route for the attach POST  (e.g. 'youtube.channels.list_sources.attach')
      $updateRoute    — named route for the PATCH        (e.g. 'youtube.channels.list_sources.update')
      $detachConfirmRoute — named route for the detach confirm GET
      $sourceParam    — the route parameter value for this source (the model instance)
--}}

{{-- =========================================================================
     Section heading
     ========================================================================= --}}
<div class="mb-4">
    <h2 class="text-lg font-bold text-gray-800">
        Lists
        <span class="ml-1 text-sm font-normal text-gray-400">({{ $listSources->total() }})</span>
    </h2>
</div>

@session('success')
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-300 text-green-800 rounded text-sm">
        {{ $value }}
    </div>
@endsession

@session('error')
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-300 text-red-800 rounded text-sm">
        {{ $value }}
    </div>
@endsession

{{-- =========================================================================
     Existing list memberships table
     ========================================================================= --}}
@if ($listSources->total() === 0)
    <div class="border border-gray-200 rounded-lg px-6 py-10 text-center text-sm text-gray-400 mb-8">
        This source is not attached to any lists yet.
    </div>
@else
    <div class="border border-gray-200 rounded-lg overflow-hidden mb-8">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">List</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Mode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Search Terms</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Last Fetched</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Failures</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($listSources as $listSource)
                    @php $track = $tracking->get($listSource->id); @endphp

                    {{-- ── Read row ─────────────────────────────────────────── --}}
                    <tr class="hover:bg-gray-50" x-data="{ editing: false }">
                        <td class="px-6 py-4">
                            <a href="{{ route('lists.show', $listSource->list) }}"
                               class="font-medium text-purple-700 hover:underline">
                                {{ $listSource->list->name }}
                            </a>
                        </td>

                        {{-- Mode cell: static when not editing, hidden when editing --}}
                        <td class="px-6 py-4 text-gray-700 capitalize" x-show="!editing">
                            {{ $listSource->processing_mode }}
                        </td>

                        {{-- Search terms cell: static when not editing, hidden when editing --}}
                        <td class="px-6 py-4 text-gray-500" x-show="!editing">
                            {{ $listSource->search_terms ?? '—' }}
                        </td>

                        <td class="px-6 py-4">
                            @if ($listSource->suspended)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Suspended</span>
                            @elseif ($listSource->enabled)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-gray-500">
                            {{ $track?->last_fetched_at?->diffForHumans() ?? '—' }}
                        </td>

                        <td class="px-6 py-4">
                            @if (($track?->consecutive_failures ?? 0) > 0)
                                <span class="text-red-600 font-medium">{{ $track->consecutive_failures }}</span>
                            @else
                                <span class="text-gray-400">0</span>
                            @endif
                        </td>

                        {{-- Actions: Edit / Detach buttons --}}
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <button
                                type="button"
                                x-show="!editing"
                                @click="editing = true"
                                class="text-xs text-purple-700 hover:underline font-medium mr-3"
                            >Edit</button>
                            <a href="{{ route($detachConfirmRoute, [$sourceParam, $listSource]) }}"
                               x-show="!editing"
                               class="text-xs text-red-500 hover:underline font-medium"
                            >Detach</a>
                            <button
                                type="button"
                                x-show="editing"
                                x-cloak
                                @click="editing = false"
                                class="text-xs text-gray-500 hover:underline font-medium"
                            >Cancel</button>
                        </td>
                    </tr>

                    {{-- ── Inline edit row (shown below parent row when editing = true) ── --}}
                    <tr x-show="editing" x-cloak x-data="{
                            mode: '{{ $listSource->processing_mode }}',
                            terms: '{{ addslashes($listSource->search_terms ?? '') }}'
                        }">
                        <td colspan="7" class="px-6 pb-5 pt-1 bg-purple-50">
                            <form
                                method="POST"
                                action="{{ route($updateRoute, [$sourceParam, $listSource]) }}"
                                class="space-y-4"
                            >
                                @csrf
                                @method('PATCH')

                                <p class="text-xs font-semibold text-purple-800 mb-2">
                                    Editing processing settings for <span class="font-bold">{{ $listSource->list->name }}</span>
                                </p>

                                {{-- Processing mode selector --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Processing mode</label>
                                    <div class="flex gap-2 max-w-sm">
                                        @foreach (['description' => 'Description', 'summary' => 'Summary', 'search' => 'Search'] as $modeValue => $modeLabel)
                                            <label class="flex-1 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    name="processing_mode"
                                                    value="{{ $modeValue }}"
                                                    x-model="mode"
                                                    class="sr-only peer"
                                                >
                                                <div class="border border-gray-200 rounded-lg px-3 py-2 text-xs font-semibold text-center text-gray-600
                                                            peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50
                                                            hover:border-gray-400 transition">
                                                    {{ $modeLabel }}
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="mt-1.5 text-xs text-gray-400">
                                        <span x-show="mode === 'description'">Store title and description only — no AI processing.</span>
                                        <span x-show="mode === 'summary'" x-cloak>Fetch content and generate an AI summary for every new item.</span>
                                        <span x-show="mode === 'search'" x-cloak>Only summarise items matching your search terms.</span>
                                    </p>
                                </div>

                                {{-- Search terms (only shown in search mode) --}}
                                <div x-show="mode === 'search'" x-cloak>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Search terms</label>
                                    <input
                                        type="text"
                                        name="search_terms"
                                        x-model="terms"
                                        placeholder="e.g. artificial intelligence, machine learning"
                                        class="w-full max-w-lg border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-800
                                               placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500
                                               focus:border-transparent transition"
                                    >
                                    <p class="mt-1 text-xs text-gray-400">Comma-separated. Matched against title, description, then content via AI semantic check.</p>
                                </div>

                                <div>
                                    <button
                                        type="submit"
                                        class="bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold px-4 py-2 rounded-lg transition"
                                    >
                                        Save changes
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($listSources->hasPages())
        <div class="mb-8">{{ $listSources->links() }}</div>
    @endif
@endif

{{-- =========================================================================
     Attach to a new list
     ========================================================================= --}}
@if ($availableLists->isNotEmpty())
    <div
        class="border border-gray-200 rounded-lg p-6 mb-8"
        x-data="{
            open: {{ $errors->has('list_id') || $errors->has('processing_mode') || $errors->has('search_terms') ? 'true' : 'false' }},
            mode: '{{ old('processing_mode', 'description') }}'
        }"
    >
        {{-- Toggle heading --}}
        <button
            type="button"
            @click="open = !open"
            class="flex items-center gap-2 w-full text-left"
        >
            <span class="text-sm font-semibold text-gray-700">+ Attach to another list</span>
            <svg
                class="w-4 h-4 text-gray-400 transition-transform"
                :class="open ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="open" x-cloak class="mt-4">
            <form method="POST" action="{{ route($attachRoute, $sourceParam) }}" class="space-y-4">
                @csrf

                {{-- Validation errors --}}
                @if ($errors->any())
                    <div class="px-4 py-3 bg-red-50 border border-red-300 text-red-700 rounded text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- List selector --}}
                <div>
                    <label for="list_id" class="block text-xs font-semibold text-gray-600 mb-1.5">List</label>
                    <select
                        id="list_id"
                        name="list_id"
                        required
                        class="w-full max-w-sm border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-800
                               focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition
                               @error('list_id') border-red-400 @enderror"
                    >
                        <option value="">— Select a list —</option>
                        @foreach ($availableLists as $list)
                            <option value="{{ $list->id }}" @selected(old('list_id') == $list->id)>
                                {{ $list->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Processing mode --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Processing mode</label>
                    <div class="flex gap-2 max-w-sm">
                        @foreach (['description' => 'Description', 'summary' => 'Summary', 'search' => 'Search'] as $modeValue => $modeLabel)
                            <label class="flex-1 cursor-pointer">
                                <input
                                    type="radio"
                                    name="processing_mode"
                                    value="{{ $modeValue }}"
                                    x-model="mode"
                                    class="sr-only peer"
                                >
                                <div class="border border-gray-200 rounded-lg px-3 py-2 text-xs font-semibold text-center text-gray-600
                                            peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50
                                            hover:border-gray-400 transition">
                                    {{ $modeLabel }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400">
                        <span x-show="mode === 'description'">Store title and description only — no AI processing.</span>
                        <span x-show="mode === 'summary'" x-cloak>Fetch content and generate an AI summary for every new item.</span>
                        <span x-show="mode === 'search'" x-cloak>Only summarise items matching your search terms.</span>
                    </p>
                </div>

                {{-- Search terms (only in search mode) --}}
                <div x-show="mode === 'search'" x-cloak>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Search terms</label>
                    <input
                        type="text"
                        name="search_terms"
                        value="{{ old('search_terms') }}"
                        placeholder="e.g. artificial intelligence, machine learning"
                        class="w-full max-w-lg border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-800
                               placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500
                               focus:border-transparent transition @error('search_terms') border-red-400 @enderror"
                    >
                    <p class="mt-1 text-xs text-gray-400">Comma-separated. Matched against title, description, then content via AI semantic check.</p>
                </div>

                <div>
                    <button
                        type="submit"
                        class="bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold px-4 py-2 rounded-lg transition"
                    >
                        Attach to list
                    </button>
                </div>
            </form>
        </div>
    </div>
@else
    {{-- All lists are already attached --}}
    @if ($listSources->total() > 0)
        <p class="text-sm text-gray-400 mb-8">This source is attached to all of your lists.</p>
    @endif
@endif