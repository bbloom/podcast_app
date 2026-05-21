<x-layouts.app title="Recording: {{ $episode->formatted_title }}">
<div class="max-w-3xl mx-auto px-4 py-8">

    {{-- Breadcrumb --}}
    <p class="text-base text-gray-500 mb-4">
        <a href="{{ route('podcast_episodes_planning.index') }}" class="hover:underline text-purple-700">Planning Episodes</a>
        &rsaquo;
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}" class="hover:underline text-purple-700">{{ $episode->formatted_title }}</a>
        &rsaquo; Recording View
    </p>

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-8">
        @if ($episode->show->itunes_image)
            <img src="{{ $episode->show->itunes_image }}"
                 alt="{{ $episode->show->title }}"
                 class="w-16 h-16 rounded object-cover border border-purple-200 flex-shrink-0">
        @endif
        <div>
            <p class="text-base font-medium text-purple-600 mb-1">{{ $episode->show->title ?? '—' }}</p>
            <h1 class="text-3xl font-bold text-gray-900">{{ $episode->formatted_title }}</h1>
            @if ($episode->scheduled_date)
                <p class="mt-1 text-base text-gray-400">{{ $episode->scheduled_date->format('F j, Y') }}</p>
            @endif
        </div>
    </div>

    {{-- ── Script ──────────────────────────────────────────────────────────── --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Script</div>
    <div class="border border-purple-500 rounded-lg px-6 py-6 mb-10">
        @if ($episode->script)
            <div class="markdown-content text-gray-800 leading-relaxed text-base">
                {!! Str::markdown($episode->script) !!}
            </div>
        @else
            <p class="text-base text-gray-400 italic">No script found for this episode.</p>
        @endif
    </div>

    {{-- ── Guests ──────────────────────────────────────────────────────────── --}}
    @if ($episode->guests->isNotEmpty())
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">
            {{ Str::plural('Guest', $episode->guests->count()) }}
        </div>
        <div class="border border-purple-500 rounded-lg overflow-hidden mb-10">
            @foreach ($episode->guests as $guest)
                <div class="{{ ! $loop->last ? 'border-b border-purple-200' : '' }} px-6 py-5">
                    <div class="flex items-start gap-4">
                        @if ($guest->image_url)
                            <img src="{{ $guest->image_url }}"
                                 alt="{{ $guest->full_name }}"
                                 class="w-16 h-16 rounded-full object-cover flex-shrink-0">
                        @endif
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-base font-bold text-gray-800">{{ $guest->full_name }}</h3>
                                @if ($guest->link_to_guest_website)
                                    <a href="{{ $guest->link_to_guest_website }}"
                                       target="_blank" rel="noopener noreferrer"
                                       class="text-purple-700 hover:underline text-sm flex items-center gap-1">
                                        Website
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                            @if ($guest->profile_full)
                                <p class="text-base text-gray-700 leading-relaxed">{{ $guest->profile_full }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── Links ───────────────────────────────────────────────────────────── --}}
    @if ($episode->links->isNotEmpty())
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Links</div>
        <div class="border border-purple-500 rounded-lg overflow-hidden mb-10">
            <ul class="divide-y divide-purple-200">
                @foreach ($episode->links as $link)
                    <li class="px-6 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-base font-semibold text-gray-800">{{ $link->title }}</p>
                                @if ($link->description)
                                    <p class="text-base text-gray-600 mt-1">{{ $link->description }}</p>
                                @endif
                            </div>
                            <a href="{{ $link->link }}"
                               target="_blank" rel="noopener noreferrer"
                               class="flex-shrink-0 text-purple-700 hover:underline text-sm flex items-center gap-1">
                                Open
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</div>
</x-layouts.app>