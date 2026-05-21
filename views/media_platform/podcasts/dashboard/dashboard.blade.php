<x-layouts.app title="Podcasts">

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Podcasts</h1>
            <p class="mt-1 text-sm text-gray-500">Your assembly line at a glance</p>
        </div>
    </div>

    {{-- ── Quick Actions ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-3 mb-10">
        <a href="{{ route('podcast_episodes_planning.wizard.create.step1') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + New Planning Episode
        </a>
        <a href="{{ route('podcast_episodes_planning.index') }}"
           class="bg-white border border-purple-300 hover:border-purple-500 text-purple-700 text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            Planning Episodes
        </a>
        <a href="{{ route('post_production.dashboard') }}"
           class="bg-white border border-purple-300 hover:border-purple-500 text-purple-700 text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            Post-Production
        </a>
    </div>

    {{-- ── Shows Overview ──────────────────────────────────────────────────── --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-10">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
            Shows
        </h2>
        <div class="overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Show</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">Planning</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">Post-Production</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($shows as $show)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800">{{ $show->title }}</td>
                            <td class="px-6 py-4 text-center tabular-nums">
                                @if ($show->planning_count > 0)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-purple-100 text-purple-700 text-sm font-semibold">{{ $show->planning_count }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center tabular-nums">
                                @if ($show->in_production_count > 0)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-green-100 text-green-700 text-sm font-semibold">{{ $show->in_production_count }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── The Assembly Line ──────────────────────────────────────────────── --}}

    {{-- Station 1: Planning --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-10">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
            Planning
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $planningEpisodes->count() }})</span>
        </h2>

        @if ($planningEpisodes->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-gray-400">
                No planning episodes yet.
                <a href="{{ route('podcast_episodes_planning.wizard.create.step1') }}" class="text-purple-700 hover:underline">Create one</a>.
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Show</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Ep#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Scheduled</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-100">
                    @foreach ($planningEpisodes as $episode)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <img src="{{ $episode->show->itunes_image }}" alt="{{ $episode->show->title }}"
                                     class="w-[40px] h-[40px] rounded object-cover border border-gray-200">
                            </td>
                            <td class="px-6 py-4 text-gray-600 tabular-nums">{{ $episode->episode_number ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
                                   class="font-medium text-purple-700 hover:underline">{{ $episode->title }}</a>
                            </td>
                            <td class="px-6 py-4">
                                @include('media_platform.podcasts.planning.crud._status_badge', ['status' => $episode->status])
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $episode->scheduled_date?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
                                   class="inline-block bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                    Open
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Station 2: In Post-Production --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-10">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
            Post-Production
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
                <tbody class="divide-y divide-purple-100">
                    @foreach ($episodesInProduction as $episode)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <img src="{{ $episode->show->itunes_image }}" alt="{{ $episode->show->title }}"
                                     class="w-[40px] h-[40px] rounded object-cover border border-gray-200">
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('podcast_episodes.show', $episode) }}"
                                   class="font-medium text-purple-700 hover:underline">{{ $episode->title }}</a>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $episode->status?->label() ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $episode->scheduled_date?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                @if ($episode->status === \MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus::ready_to_record)
                                    <a href="{{ route('podcast_episodes_planning.recording.show', $episode) }}"
                                    class="inline-block bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                        Record
                                    </a>
                                @endif
                                <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
                                class="inline-block bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition ml-2">
                                    Details
                                </a>
                            </td>
                                                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Station 3: Recently Published --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-10">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
            Recently Published
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
                <tbody class="divide-y divide-purple-100">
                    @foreach ($recentlyPublished as $episode)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <img src="{{ $episode->show->itunes_image }}" alt="{{ $episode->show->title }}"
                                     class="w-[40px] h-[40px] rounded object-cover border border-gray-200">
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('podcast_episodes.show', $episode) }}"
                                   class="font-medium text-purple-700 hover:underline">{{ $episode->title }}</a>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $episode->scheduled_date?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('podcast_episodes.show', $episode) }}"
                                   class="inline-block bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition">
                                    View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</x-layouts.app>