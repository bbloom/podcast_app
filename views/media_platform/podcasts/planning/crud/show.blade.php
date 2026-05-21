<x-layouts.app title="{{ $episode->formatted_title }}">
<div class="max-w-4xl mx-auto px-4 py-8">

    @session('success')
        <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-800 rounded">{{ $value }}</div>
    @endsession
    @session('error')
        <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-800 rounded">{{ $value }}</div>
    @endsession
    @session('info')
        <div class="mb-4 p-3 bg-blue-100 border border-blue-300 text-blue-800 rounded text-sm">{{ $value }}</div>
    @endsession

    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500 mb-1">
                <a href="{{ route('podcast_episodes_planning.index') }}" class="hover:underline text-purple-700">
                    Planning Episodes
                </a> &rsaquo; {{ $episode->show->title ?? '—' }}
            </p>
            <h1 class="text-2xl font-bold text-gray-800">{{ $episode->formatted_title }}</h1>
        </div>
        <div class="flex gap-2 mt-1 shrink-0 flex-wrap justify-end">
            <a href="{{ route('podcast_episodes_planning.wizard.create.step1') }}"
               class="px-3 py-1.5 text-sm border border-purple-400 text-purple-700 rounded hover:bg-purple-50">
                + New Episode
            </a>
            <a href="{{ route('podcast_episodes_planning.edit', $episode) }}"
               class="px-3 py-1.5 text-sm bg-purple-700 text-white rounded hover:bg-purple-800">
                Edit
            </a>
            <a href="{{ route('podcast_episodes_planning.delete.confirm', $episode) }}"
               class="px-3 py-1.5 text-sm border border-red-400 text-red-600 rounded hover:bg-red-50">
                Delete
            </a>
        </div>
    </div>

    {{-- Wizard / Editor Action Buttons --}}
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">Actions</span>
        </div>
        <div class="px-4 py-4 flex flex-wrap gap-3">

            {{-- Edit Theme --}}
            <a href="{{ route('podcast_episodes_planning.theme.show', $episode) }}"
               class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                Edit Theme
            </a>

            {{-- Edit Script --}}
            <a href="{{ route('podcast_episodes_planning.script.show', $episode) }}"
               class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                Edit Script
            </a>

            {{-- Finalize Script Wizard — only when status is correct --}}
            @if ($episode->status === \MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus::ready_to_finalize_the_script)
                <a href="{{ route('podcast_episodes_planning.wizard.finalize.step1', $episode) }}"
                   class="px-4 py-2 text-sm bg-amber-600 text-white rounded hover:bg-amber-700 font-semibold">
                    ✦ Finalize Script
                </a>
            @endif

            {{-- View for Recording — only when status is ready_to_record --}}
            @if ($episode->status === \MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus::ready_to_record)
                <a href="{{ route('podcast_episodes_planning.recording.show', $episode) }}"
                class="px-4 py-2 text-sm bg-teal-600 text-white rounded hover:bg-teal-700 font-semibold">
                    ✦ View for Recording
                </a>
            @endif

            {{-- Prepare for Publishing Wizard — only when status is correct --}}
            @if ($episode->status === \MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus::ready_for_publishing)
                <a href="{{ route('podcast_episodes_planning.wizard.publish.step1', $episode) }}"
                   class="px-4 py-2 text-sm bg-green-600 text-white rounded hover:bg-green-700 font-semibold">
                    ✦ Prepare for Publishing
                </a>
            @endif

        </div>
    </div>

    {{-- Status + quick change --}}
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">Status</span>
        </div>
        <div class="px-4 py-4 flex items-center gap-6">
            <div>
                @include('media_platform.podcasts.planning.crud._status_badge', ['status' => $episode->status])
            </div>
            <form method="POST" action="{{ route('podcast_episodes_planning.update', $episode) }}"
                  class="flex items-center gap-2">
                @csrf
                @method('PUT')
                <input type="hidden" name="title"          value="{{ $episode->title }}">
                <input type="hidden" name="episode_number" value="{{ $episode->episode_number }}">
                <input type="hidden" name="scheduled_date" value="{{ $episode->scheduled_date?->format('Y-m-d') }}">
                <select name="status"
                        class="text-sm border border-gray-300 rounded px-3 py-1.5 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    @foreach ($manualStatuses as $s)
                        <option value="{{ $s->value }}" @selected($episode->status === $s)>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
                <button type="submit"
                        class="px-3 py-1.5 bg-purple-700 text-white text-sm rounded hover:bg-purple-800">
                    Change
                </button>
            </form>
        </div>
    </div>

    {{-- Key details --}}
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">Details</span>
        </div>
        <div class="px-4 py-4">
            <table class="text-sm w-full">
                <tr>
                    <td class="text-gray-500 pr-6 py-1 w-40 align-top">ID</td>
                    <td class="text-gray-800 py-1">{{ $episode->id }}</td>
                </tr>
                <tr>
                    <td class="text-gray-500 pr-6 py-1 align-top">Show</td>
                    <td class="text-gray-800 py-1">{{ $episode->show->title ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="text-gray-500 pr-6 py-1 align-top">Episode #</td>
                    <td class="text-gray-800 py-1">{{ $episode->episode_number ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="text-gray-500 pr-6 py-1 align-top">Scheduled Date</td>
                    <td class="text-gray-800 py-1">{{ $episode->scheduled_date?->format('F j, Y') ?? '—' }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Guests --}}
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">
                Guests ({{ $episode->guests->count() }})
            </span>
            <a href="{{ route('podcast_episodes_planning.guests.attach.index', $episode) }}"
               class="text-xs text-purple-700 hover:underline">+ Attach Guest</a>
        </div>
        @if ($episode->guests->isEmpty())
            <p class="px-4 py-3 text-sm text-gray-400">No guests attached.</p>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($episode->guests as $guest)
                    <li class="flex items-center justify-between px-4 py-3">
                        <a href="{{ route('podcast_guests.show', $guest) }}"
                           class="text-sm text-purple-700 hover:underline">{{ $guest->full_name }}</a>
                        <form method="POST"
                              action="{{ route('podcast_episodes_planning.guests.detach', [$episode, $guest]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-xs text-red-500 hover:underline">Detach</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Links --}}
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">
                Links ({{ $episode->links->count() }})
            </span>
            <a href="{{ route('podcast_episodes_planning.links.attach.index', $episode) }}"
               class="text-xs text-purple-700 hover:underline">+ Attach Link</a>
        </div>
        @if ($episode->links->isEmpty())
            <p class="px-4 py-3 text-sm text-gray-400">No links attached.</p>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($episode->links as $link)
                    <li class="flex items-center justify-between px-4 py-3">
                        <div>
                            <p class="text-sm text-gray-800">{{ $link->title }}</p>
                            <p class="text-xs text-gray-400 truncate max-w-sm">{{ $link->link }}</p>
                        </div>
                        <form method="POST"
                              action="{{ route('podcast_episodes_planning.links.detach', [$episode, $link]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-xs text-red-500 hover:underline">Detach</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Notes --}}
    @if ($episode->notes)
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">Notes</span>
        </div>
        <div class="px-4 py-4 text-sm text-gray-800 whitespace-pre-wrap">{{ $episode->notes }}</div>
    </div>
    @endif

    {{-- Theme --}}
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">Theme</span>
            <a href="{{ route('podcast_episodes_planning.theme.show', $episode) }}"
               class="text-xs text-purple-700 hover:underline">Edit Theme</a>
        </div>
        <div class="px-4 py-4 text-sm text-gray-800">
            @if ($episode->theme)
                <div class="whitespace-pre-wrap">{{ $episode->theme }}</div>
            @else
                <span class="text-gray-400">No theme set.</span>
            @endif
        </div>
    </div>

    {{-- Script --}}
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden" x-data="{ open: false }">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">Script</span>
            <div class="flex items-center gap-3">
                @if ($episode->script)
                    <button @click="open = !open"
                            class="text-xs text-purple-700 hover:underline" x-text="open ? 'Hide' : 'Show'"></button>
                @endif
                <a href="{{ route('podcast_episodes_planning.script.show', $episode) }}"
                   class="text-xs text-purple-700 hover:underline">Edit Script</a>
            </div>
        </div>
        <div class="px-4 py-4 text-sm text-gray-800">
            @if ($episode->script)
                <div x-show="open" x-transition class="whitespace-pre-wrap font-mono text-xs">{{ $episode->script }}</div>
                <div x-show="!open" class="text-gray-500 text-xs italic">Script is set — click Show to view.</div>
            @else
                <span class="text-gray-400">No script yet.</span>
            @endif
        </div>
    </div>

    {{-- Website content --}}
    @if ($episode->website_content || $episode->website_excerpt)
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">Website Content</span>
        </div>
        <div class="px-4 py-4 space-y-4 text-sm">
            @if ($episode->website_excerpt)
                <div>
                    <p class="text-gray-500 mb-1 text-xs uppercase tracking-wide">Excerpt</p>
                    <p class="text-gray-800">{{ $episode->website_excerpt }}</p>
                </div>
            @endif
            @if ($episode->website_content)
                <div>
                    <p class="text-gray-500 mb-1 text-xs uppercase tracking-wide">Content</p>
                    <div class="markdown-content text-gray-800">{!! Str::markdown($episode->website_content) !!}</div>
                </div>
            @endif
        </div>
    </div>
    @endif

</div>
</x-layouts.app>