<x-layouts.app title="Podcast Studio">

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Podcast Studio</h1>
            <p class="mt-1 text-sm text-gray-500">Your assembly line at a glance</p>
        </div>
    </div>

    {{-- ── Quick Actions ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-3 mb-10">
        <a href="{{ route('podcast_episode_drafts.create.step1') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + New Draft
        </a>
        <a href="{{ route('draft_pre_production.step1') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            Pre-Production
        </a>
        <a href="{{ route('post_production.dashboard') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            Post-Production
        </a>
    </div>

    {{-- ── Show Overview ──────────────────────────────────────────────────── --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-10">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
            Shows
        </h2>
        <div class="overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Show</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">Drafting</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">Ready</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">In Production</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($shows as $show)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $show->itunes_image }}" alt="{{ $show->title }}"
                                         class="w-[40px] h-[40px] rounded object-cover border border-gray-200">
                                    <a href="{{ route('podcast_shows.show', $show) }}"
                                       class="font-medium text-purple-700 hover:underline">{{ $show->title }}</a>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center tabular-nums">
                                @if ($show->drafts_in_progress_count > 0)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 text-gray-700 text-sm font-semibold">{{ $show->drafts_in_progress_count }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center tabular-nums">
                                @if ($show->drafts_ready_count > 0)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-green-100 text-green-700 text-sm font-semibold">{{ $show->drafts_ready_count }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center tabular-nums">
                                @if ($show->episodes_in_production_count > 0)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-purple-100 text-purple-700 text-sm font-semibold">{{ $show->episodes_in_production_count }}</span>
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

    {{-- Station 1: Drafts in Progress --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-10">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
            Drafting
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $draftsInProgress->count() }})</span>
        </h2>

        @if ($draftsInProgress->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-gray-400">
                No drafts in progress.
                <a href="{{ route('podcast_episode_drafts.create.step1') }}" class="text-purple-700 hover:underline">Create one</a>.
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Show</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Ep#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-400">
                    @foreach ($draftsInProgress as $draft)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <img src="{{ $draft->show->itunes_image }}" alt="{{ $draft->show->title }}"
                                     class="w-[40px] h-[40px] rounded object-cover border border-gray-200">
                            </td>
                            <td class="px-6 py-4 text-gray-600 tabular-nums">{{ $draft->episode_number ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('podcast_episode_drafts.show', $draft) }}"
                                   class="font-medium text-purple-700 hover:underline">{{ $draft->title }}</a>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $draft->date?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('podcast_episode_drafts.edit', $draft) }}"
                                   class="inline-block bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                    Work on Draft
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Station 2: Ready for Production --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-10">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
            Ready to Create Production Episode
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $draftsReadyForProduction->count() }})</span>
        </h2>

        @if ($draftsReadyForProduction->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-gray-400">
                No drafts ready for production yet.
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Show</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Ep#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-400">
                    @foreach ($draftsReadyForProduction as $draft)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <img src="{{ $draft->show->itunes_image }}" alt="{{ $draft->show->title }}"
                                     class="w-[40px] h-[40px] rounded object-cover border border-gray-200">
                            </td>
                            <td class="px-6 py-4 text-gray-600 tabular-nums">{{ $draft->episode_number ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('podcast_episode_drafts.show', $draft) }}"
                                   class="font-medium text-purple-700 hover:underline">{{ $draft->title }}</a>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $draft->date?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('podcast_episode_drafts.show', $draft) }}"
                                   class="inline-block bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                    Create Episode
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Station 3: In Production --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-10">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
            In Production
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $episodesInProduction->count() }})</span>
        </h2>

        @if ($episodesInProduction->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-gray-400">
                No episodes in production.
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
                <tbody class="divide-y divide-purple-400">
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
                                <a href="{{ route('podcast_episodes.show', $episode) }}"
                                   class="inline-block bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                    Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Station 4: Recently Published --}}
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
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
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
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- ── Management ─────────────────────────────────────────────────────── --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-10">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
            Management
        </h2>
        <div class="p-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('podcast_shows.index') }}"
                   class="border border-gray-200 rounded-lg px-4 py-4 text-center hover:bg-gray-50 transition">
                    <div class="text-sm font-semibold text-gray-700">Shows</div>
                </a>
                <a href="{{ route('podcast_episodes.index') }}"
                   class="border border-gray-200 rounded-lg px-4 py-4 text-center hover:bg-gray-50 transition">
                    <div class="text-sm font-semibold text-gray-700">Episodes</div>
                </a>
                <a href="{{ route('podcast_episode_drafts.index') }}"
                   class="border border-gray-200 rounded-lg px-4 py-4 text-center hover:bg-gray-50 transition">
                    <div class="text-sm font-semibold text-gray-700">Drafts</div>
                </a>
                <a href="{{ route('podcast_links.index') }}"
                   class="border border-gray-200 rounded-lg px-4 py-4 text-center hover:bg-gray-50 transition">
                    <div class="text-sm font-semibold text-gray-700">Links</div>
                </a>
                @can('admin')
                    <a href="{{ route('podcast_guests.index') }}"
                       class="border border-gray-200 rounded-lg px-4 py-4 text-center hover:bg-gray-50 transition">
                        <div class="text-sm font-semibold text-gray-700">Guests</div>
                    </a>
                    <a href="{{ route('deploy_hooks.index') }}"
                       class="border border-gray-200 rounded-lg px-4 py-4 text-center hover:bg-gray-50 transition">
                        <div class="text-sm font-semibold text-gray-700">Deploy Hooks</div>
                    </a>
                @endcan
            </div>
        </div>
    </div>

</x-layouts.app>