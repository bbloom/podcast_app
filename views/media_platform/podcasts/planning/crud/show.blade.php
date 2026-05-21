<x-layouts.app title="{{ $episode->formatted_title }}">
<div class="max-w-4xl mx-auto px-4 py-8">

    @session('success')
        <div class="text-xl mb-4 p-6 bg-green-100 border-2 border-green-800 text-green-800 rounded-full">{{ $value }}</div><br>
    @endsession
    @session('error')
        <div class="text-xl mb-4 p-6 bg-red-100 border border-red-700 text-red-800 rounded-full">{{ $value }}</div><br>
    @endsession
    @session('info')
        <div class="text-xl mb-4 p-6 bg-blue-100 border border-blue-700 text-blue-800 rounded-full">{{ $value }}</div><br>
    @endsession

    {{-- Breadcrumb --}}
    <p class="text-base text-gray-500 mb-4">
        <a href="{{ route('podcast_episodes_planning.index') }}" class="hover:underline text-purple-700">
            Planning Episodes
        </a>
        &rsaquo; {{ $episode->title ?? '—' }}
    </p>

    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div class="flex items-center gap-4">
            <h1 class="text-3xl font-bold text-purple-700 bg-sky-100 border-2 border-sky-700 rounded-lg px-6 py-4 mb-8 mt-4 shadow-sm">
                {{ $episode->title }}
            </h1>
        </div>

        <
        
    </div>

    {{-- ── Core info ───────────────────────────────────────────────────────── --}}
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden bg-white">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">Episode Details</span>
        </div>
        <div class="px-4 py-4">
            <table class="w-full text-base border-collapse">
                <tbody>
                    <tr>
                        <td colspan="2" class="py-2 border-b border-purple-300">
                            @if ($episode->show->itunes_image)
                                <div class="flex items-center gap-3">
                                    <img src="{{ $episode->show->itunes_image }}"
                                        alt="{{ $episode->show->title }}"
                                        class="w-24 h-24 rounded object-cover border border-purple-200">
                                </div>
                            @else
                                <span class="text-gray-800">{{ $episode->show->title ?? '—' }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="w-1 text-gray-500 pr-6 py-2 align-top border-b border-purple-300 whitespace-nowrap">Episode #</td>
                        <td class="font-bold text-gray-800 py-2 border-b border-purple-300">{{ $episode->episode_number ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="w-1 text-gray-500 pr-6 py-2 align-top border-b border-purple-300 whitespace-nowrap">Title</td>
                        <td class="font-bold text-gray-800 py-2 border-b border-purple-300">{{ $episode->title ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="w-1 text-gray-500 pr-6 py-2 align-top whitespace-nowrap">Scheduled Date</td>
                        <td class="font-bold text-gray-800 py-2">{{ $episode->scheduled_date?->format('l, M j, Y') ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Status ───────────────────────────────────────────── --}}
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">Status</span>
        </div>
        <div class="px-4 py-4 bg-white">
               @include('media_platform.podcasts.planning.crud._status_badge', ['status' => $episode->status])
        </div>
    </div>

    {{-- ── Notes ───────────────────────────────────────────────────────────── --}}
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">Notes</span>
            <a href="{{ route('podcast_episodes_planning.edit', $episode) }}"
               class="px-3 py-1 text-xs font-semibold bg-purple-700 text-white rounded hover:bg-purple-800">
                Edit Notes
            </a>
        </div>
        <div class="px-4 py-4 text-base text-gray-800 bg-white">
            @if ($episode->notes)
                <div class="whitespace-pre-wrap">{{ $episode->notes }}</div>
            @else
                <span class="text-gray-400">No notes.</span>
            @endif
        </div>
    </div>

    {{-- ── Theme ───────────────────────────────────────────────────────────── --}}
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">Theme</span>
            <a href="{{ route('podcast_episodes_planning.theme.show', $episode) }}"
               class="px-3 py-1 text-xs font-semibold bg-purple-700 text-white rounded hover:bg-purple-800">
                Edit Theme
            </a>
        </div>
        <div class="px-4 py-4 text-base text-gray-800 bg-white">
            @if ($episode->theme)
                <div class="whitespace-pre-wrap">{{ $episode->theme }}</div>
            @else
                <span class="text-gray-400">No theme set.</span>
            @endif
        </div>
    </div>

    {{-- ── Script ──────────────────────────────────────────────────────────── --}}
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden" x-data="{ open: false }">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">Script</span>
            <div class="flex items-center gap-2">
                @if ($episode->script)
                    <button @click="open = !open"
                            class="px-3 py-1 text-xs font-semibold border border-purple-400 text-purple-700 rounded hover:bg-purple-100"
                            x-text="open ? 'Hide' : 'Show Script'">
                    </button>
                @endif
                <a href="{{ route('podcast_episodes_planning.script.show', $episode) }}"
                   class="px-3 py-1 text-xs font-semibold bg-purple-700 text-white rounded hover:bg-purple-800">
                    Edit Script
                </a>
            </div>
        </div>
        <div x-show="open" x-cloak class="px-4 py-4 bg-white">
            @if ($episode->script)
                <div class="text-base text-gray-800 whitespace-pre-wrap font-mono leading-relaxed">{{ $episode->script }}</div>
            @else
                <span class="text-base text-gray-400">No script yet.</span>
            @endif
        </div>
        @if (!$episode->script)
            <div class="px-4 py-4 bg-white">
                <span class="text-base text-gray-400">No script yet.</span>
            </div>
        @endif
    </div>


    {{-- ── Guests ──────────────────────────────────────────────────────────── --}}
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">
                Guests ({{ $episode->guests->count() }})
            </span>
            <a href="{{ route('podcast_episodes_planning.guests.attach.index', $episode) }}"
               class="px-3 py-1 text-xs font-semibold bg-purple-700 text-white rounded hover:bg-purple-800">
                + Attach Guest
            </a>
        </div>
        <div class="bg-white">
            @if ($episode->guests->isEmpty())
                <p class="px-4 py-3 text-base text-gray-400">No guests attached.</p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($episode->guests as $guest)
                        <li class="flex items-center justify-between px-4 py-3">
                            <a href="{{ route('podcast_guests.show', $guest) }}"
                            class="text-base text-purple-700 hover:underline">{{ $guest->full_name }}</a>
                            <form method="POST"
                                action="{{ route('podcast_episodes_planning.guests.detach', [$episode, $guest]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1 text-xs font-semibold bg-red-600 text-white rounded hover:bg-red-700">
                                    Detach
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- ── Links ───────────────────────────────────────────────────────────── --}}
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">
                Links ({{ $episode->links->count() }})
            </span>
            <a href="{{ route('podcast_episodes_planning.links.attach.index', $episode) }}"
               class="px-3 py-1 text-xs font-semibold bg-purple-700 text-white rounded hover:bg-purple-800">
                + Attach Link
            </a>
        </div>
        <div class="bg-white">
            @if ($episode->links->isEmpty())
                <p class="px-4 py-3 text-base text-gray-400">No links attached.</p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($episode->links as $link)
                        <li class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-base text-gray-800">{{ $link->title }}</p>
                                <p class="text-xs text-gray-400 truncate max-w-sm">{{ $link->link }}</p>
                            </div>
                            <form method="POST"
                                action="{{ route('podcast_episodes_planning.links.detach', [$episode, $link]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1 text-xs font-semibold bg-red-600 text-white rounded hover:bg-red-700">
                                    Detach
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>


    {{-- ── Actions ─────────────────────────────────────────────────────────── --}} 
    <div class="mb-6 border border-purple-300 rounded-lg overflow-hidden bg-white">

        <!-- Header Title -->
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">Episode Management</span>
        </div>

        <!-- Main Content Container (Stacks the 4 rows vertically) -->
        <div class="p-4 flex flex-col gap-5">

            <!-- LINE 1: Theme, etc. (Side-by-side) -->
            <div class="w-full flex items-center gap-4 text-sm text-gray-700">
                <a href="{{ route('podcast_episodes_planning.theme.show', $episode) }}"
                class="px-4 py-2 text-sm font-semibold border border-gray-400 text-gray-700 rounded hover:bg-gray-50">
                    Edit Theme
                </a>

                <a href="{{ route('podcast_episodes_planning.script.show', $episode) }}"
                class="px-4 py-2 text-sm font-semibold border border-gray-400 text-gray-700 rounded hover:bg-gray-50">
                    Edit Script
                </a>

                @if ($episode->status === \MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus::ready_to_finalize_the_script)
                    <a href="{{ route('podcast_episodes_planning.wizard.finalize.step1', $episode) }}"
                    class="px-4 py-2 text-sm font-semibold bg-orange-100 text-orange-700 rounded hover:bg-orange-700 hover:text-white">
                        ✦ Finalize Script
                    </a>
                @endif

                @if ($episode->status === \MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus::ready_to_record)
                    <a href="{{ route('podcast_episodes_planning.recording.show', $episode) }}"
                    class="px-4 py-2 text-sm font-semibold bg-green-100 text-green-700 rounded hover:bg-green-800 hover:text-white">
                        ✦ View for Recording
                    </a>
                @endif

                @if ($episode->status === \MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus::ready_for_publishing)
                    <a href="{{ route('podcast_episodes_planning.wizard.publish.step1', $episode) }}"
                    class="px-4 py-2 text-sm font-semibold bg-purple-100 text-purple-700 rounded hover:bg-purple-800 hover:text-white">
                        ✦ Prepare for Publishing
                    </a>
                @endif
            </div>

            <hr class="border-purple-300" />

            <!-- LINE 2: EDIT and DELETE Action Buttons (Side-by-side) -->
            <div class="w-full flex items-center gap-3">
                <a href="{{ route('podcast_episodes_planning.edit', $episode) }}"
                class="px-4 py-2 text-sm font-semibold bg-purple-700 text-white rounded hover:bg-purple-800">
                    Edit
                </a>
                <a href="{{ route('podcast_episodes_planning.delete.confirm', $episode) }}"
                class="px-4 py-2 text-sm font-semibold bg-red-600 text-white rounded hover:bg-red-700">
                    Delete
                </a>
            </div>

            <hr class="border-purple-300" />

            <!-- LINE 3: Status Form (Inputs side-by-side on their own line) -->
            <div class="w-full">
                <form method="POST" action="{{ route('podcast_episodes_planning.update', $episode) }}"
                    class="flex items-center gap-3">
                    @csrf
                    @method('PUT')

                    {{-- Required by PodcastEpisodePlanningRequest --}}
                    <input type="hidden" name="title" value="{{ $episode->title }}">
                    <input type="hidden" name="episode_number" value="{{ $episode->episode_number }}">
                    <input type="hidden" name="scheduled_date" value="{{ $episode->scheduled_date?->format('Y-m-d') }}">

                    
                    <select name="status"
                            class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none">
                        @foreach (\MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus::manualStatuses() as $s)
                            <option value="{{ $s->value }}" @selected($episode->status === $s)>
                                {{ $s->label() }}
                            </option>
                        @endforeach
                    </select>
                    
                    <button type="submit"
                            class="px-4 py-1.5 text-sm font-semibold bg-indigo-700 text-white rounded hover:bg-indigo-800 transition">
                        Update Status
                    </button>
                </form>
            </div>
        </div>

    </div>

    <div class="flex justify-center items-center gap-2 w-full pt-6">
        <a href="{{ route('podcasts.dashboard') }}"
            class="px-4 py-2 text-sm font-semibold bg-sky-700 text-white rounded hover:bg-sky-800">
                Podcasts Dashboard
        </a>
    </div>
 
    

</div>
</x-layouts.app>