<x-layouts.app title="Podcast Studio">
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- ── Page header ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Podcasts Main Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500"></p>
        </div>
    </div>

    {{-- ── Quick Actions ────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-3 mb-10">
        <a href="{{ route('podcast_episodes_planning.wizard.create.step1') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + New Planning Episode
        </a>
        <a href="{{ route('podcast_episodes_planning.index') }}"
           class="bg-white border border-purple-300 hover:border-purple-500 text-purple-700 text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            All Planning Episodes
        </a>
        <a href="{{ route('post_production.dashboard') }}"
           class="bg-white border border-purple-300 hover:border-purple-500 text-purple-700 text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            Post-Production
        </a>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- PLANNING                                                               --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div class="border-2 border-purple-300 rounded-lg overflow-hidden mb-10">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
            Episodes in Planning
            <span class="ml-1 text-sm font-normal text-gray-600">({{ $planningByShow->flatten()->count() }})</span>
        </h2>

        <table class="w-full text-base">
            <thead class="bg-gray-50 border-b border-purple-300">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide w-16">Ep#</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide w-64">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Scheduled</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tr class="bg-gray-100 border-b border-purple-300"><td colspan="5" class="py-3">&nbsp;</td></tr>
        </table>

        @if ($planningByShow->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-gray-400">
                No planning episodes yet.
                <a href="{{ route('podcast_episodes_planning.wizard.create.step1') }}" class="text-purple-700 hover:underline">Create one</a>.
            </div>
        @else
            <table class="w-full text-sm">

                <tbody>
                    @foreach ($planningByShow as $showId => $episodes)
                        
                        {{-- Show header row --}}
                        <tr class="bg-purple-200 border-t border-b border-purple-200">
                            <td colspan="5" class="px-6 py-2">
                                <div class="flex items-center gap-3">
                                    @if ($episodes->first()->show->itunes_image)
                                        <img src="{{ $episodes->first()->show->itunes_image }}"
                                             alt="{{ $episodes->first()->show->title }}"
                                             class="w-16 h-16 rounded object-cover border border-purple-200 flex-shrink-0">
                                    @endif
                                    <span class="text-base font-bold text-purple-700 uppercase tracking-wider">
                                        {{ $episodes->first()->show->title }}
                                    </span>
                                    <span class="text-base text-gray-600">({{ $episodes->count() }})</span>
                                </div>
                            </td>
                        </tr>

                        {{-- Episode rows for this show --}}
                        @foreach ($episodes as $episode)
                            <tr class="bg-white border-b border-purple-300 hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm text-gray-500 tabular-nums">EP# {{ $episode->episode_number ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
                                       class="font-bold text-base text-purple-700 hover:underline">
                                        {{ $episode->title }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-base">
                                    @include('media_platform.podcasts.planning.crud._status_badge', ['status' => $episode->status])
                                </td>
                                <td class="px-6 py-3 font-bold text-base text-gray-500 whitespace-nowrap">
                                    {{ $episode->scheduled_date?->format('D, M j, Y') ?? '—' }}
                                </td>
                                
                                <td class="px-6 py-3 text-right">
                                    <div class="flex flex-col items-end gap-1.5">
                                        <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
                                        class="inline-block bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                            Details
                                        </a>

                                        @if ($episode->status === \MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus::ready_to_record)
                                            <a href="{{ route('podcast_episodes_planning.recording.show', $episode) }}"
                                            class="inline-block bg-green-100 text-orange-700 text-xs font-semibold px-3 py-1.5 rounded-lg transition  hover:bg-green-800 hover:text-white">
                                                 ✦ View for Recording
                                            </a>
                                        @endif

                                        @if ($episode->status === \MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus::ready_to_finalize_the_script)
                                            <a href="{{ route('podcast_episodes_planning.wizard.finalize.step1', $episode) }}"
                                            class="px-4 py-2 text-sm font-semibold bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-700 hover:text-white">
                                                ✦ Finalize Script
                                            </a>
                                        @endif

                                        @if ($episode->status === \MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus::ready_for_publishing)
                                            <a href="{{ route('podcast_episodes_planning.wizard.publish.step1', $episode) }}"
                                            class="px-4 py-2 text-sm font-semibold bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-800  hover:text-white">
                                                ✦ Prepare for Publishing
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        <tr class="bg-gray-100 border-b border-purple-300"><td colspan="5" class="py-4">&nbsp;</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- POST-PRODUCTION EPISODES                                               --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-10">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
            Episodes that are in Post-Production — Needs Attention
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $episodesInProduction->count() }})</span>
        </h2>

        @if ($episodesInProduction->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-gray-400">
                No episodes in post-production.
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Show</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Scheduled</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($episodesInProduction as $episode)
                        <tr class="bg-white hover:bg-gray-50">
                            <td class="px-6 py-3">
                                @if ($episode->show->itunes_image)
                                    <img src="{{ $episode->show->itunes_image }}"
                                         alt="{{ $episode->show->title }}"
                                         class="w-16 h-16 rounded object-cover border border-purple-200 flex-shrink-0">
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <a href="{{ route('podcast_episodes.show', $episode) }}"
                                   class="font-medium text-purple-700 hover:underline">
                                    {{ $episode->title }}
                                </a>
                            </td>
                            <td class="px-6 py-3 align-middle">
                                <span class="inline-flex items-center px-4 py-1.5 text-sm font-semibold text-green-700 bg-green-50 border border-green-300 rounded-full shadow-sm">
                                    {{ $episode->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-500">
                                {{ $episode->scheduled_date?->format('M d, Y') ?? '—' }}
                            </td>
                            <td class="px-6 py-3 text-right">
                                {{-- Continue links directly to the relevant pipeline page for this status.   --}}
                                {{-- processing_at_auphonic shows the polling UI — labelled Monitor since     --}}
                                {{-- no user action is required, but the page shows live state.               --}}
                                @if ($episode->status === \MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus::processing_at_auphonic)
                                    <a href="{{ route($episode->status->postProductionShowRoute(), $episode) }}"
                                       class="inline-block bg-gray-400 hover:bg-gray-500 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                        Monitor
                                    </a>
                                @else
                                    <a href="{{ route($episode->status->postProductionShowRoute(), $episode) }}"
                                       class="inline-block bg-green-700 hover:bg-green-800 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                        Continue
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- RECENTLY PUBLISHED EPISODES                                            --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-10">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
            Recently Published Episodes
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $recentlyPublished->count() }})</span>
        </h2>

        @if ($recentlyPublished->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-gray-400">
                No published episodes yet.
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Show</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Published</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($recentlyPublished as $episode)
                        <tr class="bg-white hover:bg-gray-50">
                            <td class="px-6 py-3">
                                @if ($episode->show->itunes_image)
                                    <img src="{{ $episode->show->itunes_image }}"
                                         alt="{{ $episode->show->title }}"
                                         class="w-16 h-16 rounded object-cover border border-purple-200 flex-shrink-0">
                                @endif
                            </td>
                            <td class="px-6 py-3 font-medium text-gray-800">{{ $episode->title }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $episode->scheduled_date?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('podcast_episodes.show', $episode) }}"
                                   class="inline-block bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                    Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>
</x-layouts.app>