<x-layouts.app title="Recording: {{ $episode->formatted_title }}">
<div class="max-w-3xl mx-auto px-4 py-8">

    {{-- ── Breadcrumb ──────────────────────────────────────────────────────── --}}
    <p class="text-sm text-gray-500 mb-6">
        <a href="{{ route('podcast_episodes_planning.index') }}" class="hover:underline text-purple-700">Planning Episodes</a>
        &rsaquo;
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}" class="hover:underline text-purple-700">{{ $episode->formatted_title }}</a>
        &rsaquo; Recording View
    </p>

    {{-- ── Episode Header ──────────────────────────────────────────────────── --}}
    <div class="mb-8">
        <p class="text-sm font-medium text-purple-600 mb-1">{{ $episode->show->title ?? '—' }}</p>
        <h1 class="text-3xl font-bold text-gray-900">{{ $episode->formatted_title }}</h1>
        @if ($episode->scheduled_date)
            <p class="mt-1 text-sm text-gray-400">{{ $episode->scheduled_date->format('F j, Y') }}</p>
        @endif
    </div>

    {{-- ── Script ──────────────────────────────────────────────────────────── --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Script</div>
    <div class="border border-purple-500 rounded-lg px-6 py-6 mb-10">
        @if ($episode->script)
            {{--
                Rendered as Markdown — the script contains the full intro and outro,
                assembled by the Finalize Script Wizard.
            --}}
            <div class="markdown-content text-gray-800 leading-relaxed text-base">
                {!! Str::markdown($episode->script) !!}
            </div>
        @else
            <p class="text-sm text-gray-400 italic">No script found for this episode.</p>
        @endif
    </div>

    {{-- ── Guests ──────────────────────────────────────────────────────────── --}}
    @if ($episode->guests->isNotEmpty())
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">
            {{ Str::plural('Guest', $episode->guests->count()) }}
        </div>
        <div class="border border-purple-500 rounded-lg overflow-hidden mb-10">
            @foreach ($episode->guests as $guest)
                <div class="{{ ! $loop->last ? 'border-b border-purple-200' : '' }} px-6 py-6">

                    {{-- Name + thumbnail --}}
                    <div class="flex items-center gap-4 mb-4">
                        @if ($guest->image_thumbnail_url)
                            <img src="{{ $guest->image_thumbnail_url }}"
                                 alt="{{ $guest->full_name }}"
                                 class="w-14 h-14 rounded-full object-cover border border-gray-200 flex-shrink-0">
                        @endif
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">{{ $guest->full_name }}</h2>

                            {{-- Guest website link — opens in new tab --}}
                            @if ($guest->link_to_guest_website)
                                <a href="{{ $guest->link_to_guest_website }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1 text-sm text-purple-700 hover:underline mt-0.5">
                                    {{ $guest->link_to_guest_website }}
                                    {{-- External link icon --}}
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Full profile --}}
                    @if ($guest->profile_full)
                        <div class="markdown-content text-gray-700 text-sm leading-relaxed">
                            {!! Str::markdown($guest->profile_full) !!}
                        </div>
                    @endif

                </div>
            @endforeach
        </div>
    @endif

    {{-- ── Links ───────────────────────────────────────────────────────────── --}}
    @if ($episode->links->isNotEmpty())
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Links</div>
        <div class="border border-purple-500 rounded-lg px-6 py-4 mb-10">
            <ul class="space-y-3">
                @foreach ($episode->links as $link)
                    <li>
                        <a href="{{ $link->link }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 text-purple-700 hover:underline text-sm font-medium">
                            {{ $link->title ?? $link->link }}
                            {{-- External link icon --}}
                            <svg class="w-3.5 h-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Back link ───────────────────────────────────────────────────────── --}}
    <div class="mt-4">
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
           class="text-sm text-purple-700 hover:underline flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Episode
        </a>
    </div>

</div>
</x-layouts.app>