<x-layouts.app title="{{ $episode->title }}">

    {{-- Breadcrumb + heading --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('podcast_episodes.index') }}" class="hover:text-purple-700 transition">← Episodes</a>
            <span>›</span>
            <span class="text-gray-700">{{ $episode->title }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $episode->title }}</h1>
            <a href="{{ route('podcast_episodes.edit', $episode) }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Edit
            </a>
        </div>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    <div class="border border-purple-500 rounded-lg p-6 mb-8">

        <table class="text-sm text-gray-600 border-collapse w-full">

            {{-- Core --}}
            <tr><td colspan="2" class="pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Core</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Show</td>
                <td class="py-1">
                    <a href="{{ route('podcast_shows.show', $episode->show) }}"
                       class="text-purple-700 hover:underline">{{ $episode->show?->title ?? '—' }}</a>
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Status</td>
                <td class="py-1 text-gray-800">{{ $episode->status?->title ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Slug</td>
                <td class="py-1 text-gray-800 font-mono text-xs">{{ $episode->slug }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Scheduled</td>
                <td class="py-1 text-gray-800">{{ $episode->scheduled_date?->format('M d, Y') ?? '—' }}</td>
            </tr>
            @if ($episode->raw_input_audio_filename)
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Raw Audio</td>
                <td class="py-1 text-gray-800 font-mono text-xs">{{ $episode->raw_input_audio_filename }}</td>
            </tr>
            @endif

            {{-- iTunes --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">iTunes</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Episode #</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_episode ?: '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Season #</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_season ?: '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Episode Type</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_episode_type }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Explicit</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_explicit ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Block</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_block ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Pub Date</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_pubdate?->format('M d, Y H:i') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Duration</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_duration ?? '—' }}</td>
            </tr>
            @if ($episode->itunes_enclosure_url)
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Audio File</td>
                <td class="py-1">
                    <a href="{{ $episode->itunes_enclosure_url }}" class="text-purple-700 hover:underline text-xs" target="_blank">
                        {{ $episode->itunes_enclosure_url }}
                    </a>
                </td>
            </tr>
            @endif

            {{-- Publishing --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Publishing</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">RSS Feed</td>
                <td class="py-1">
                    @if ($episode->rss_feed_enabled)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Website</td>
                <td class="py-1">
                    @if ($episode->website_enabled)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Publish On</td>
                <td class="py-1 text-gray-800">{{ $episode->website_publish_on?->format('M d, Y') ?? '—' }}</td>
            </tr>

            {{-- Record --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Record</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Created</td>
                <td class="py-1 text-gray-800">{{ $episode->created_at->format('M d, Y') }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Updated</td>
                <td class="py-1 text-gray-800">{{ $episode->updated_at->format('M d, Y') }}</td>
            </tr>

        </table>
    </div>

    {{-- ── Guests ──────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-800">
            Guests
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $episode->guests()->count() }})</span>
        </h2>
        <a href="{{ route('podcast_guests.attach.guest.index', $episode) }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
            + Attach Guest
        </a>
    </div>

    @php $guests = $episode->guests()->orderBy('full_name')->get(); @endphp

    @if ($guests->isEmpty())
        <div class="border border-gray-200 rounded-lg px-6 py-8 text-center text-sm text-gray-400 mb-8">
            No guests attached to this episode yet.
        </div>
    @else
        <div class="border border-gray-200 rounded-lg overflow-hidden mb-8">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Profile</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($guests as $guest)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('podcast_guests.show', $guest) }}"
                                   class="font-medium text-purple-700 hover:underline">
                                    {{ $guest->full_name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs truncate max-w-xs">
                                {{ $guest->profile_short ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST"
                                      action="{{ route('podcast_guests.detach.guest', [$episode, $guest]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-xs text-gray-400 hover:text-red-600 font-medium transition">
                                        Detach
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ── Links ──────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-800">
            Links
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $episode->links()->count() }})</span>
        </h2>
        <a href="{{ route('podcast_links.attach.index', $episode) }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
            + Attach Link
        </a>
    </div>

    @php $links = $episode->links()->orderBy('title')->get(); @endphp

    @if ($links->isEmpty())
        <div class="border border-gray-200 rounded-lg px-6 py-8 text-center text-sm text-gray-400 mb-8">
            No links attached to this episode yet.
        </div>
    @else
        <div class="border border-gray-200 rounded-lg overflow-hidden mb-8">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Link</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Enabled</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($links as $link)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800 max-w-xs truncate">
                                {{ $link->title ?? '(no title)' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500 max-w-xs truncate">
                                <a href="{{ $link->link }}" target="_blank"
                                   class="text-purple-700 hover:underline">
                                    {{ $link->link }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                @if ($link->enabled)
                                    <span title="Enabled">✅</span>
                                @else
                                    <span title="Disabled">❌</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST"
                                      action="{{ route('podcast_links.detach', [$episode, $link]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-xs text-gray-400 hover:text-red-600 font-medium transition">
                                        Detach
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-6 text-sm">
        <a href="{{ route('podcast_episodes.index') }}" class="hover:text-purple-700 transition">← Episodes</a>
    </div>

</x-layouts.app>