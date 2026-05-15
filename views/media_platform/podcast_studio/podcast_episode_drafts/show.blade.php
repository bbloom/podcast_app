<x-layouts.app title="{{ $draft->title }}">

    {{-- Breadcrumb + heading --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('podcast_episode_drafts.index') }}" class="hover:text-purple-700 transition">← Podcast Episode Drafts</a>
            <span>›</span>
            <span class="text-gray-700">{{ $draft->title }}</span>
        </div>

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $draft->title }}</h1>

            <div class="flex items-center gap-3">
                <a href="{{ route('podcast_episode_drafts.delete.confirm', $draft) }}"
                   class="bg-red-700 hover:bg-red-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                    Delete
                </a>
                <a href="{{ route('podcast_episode_drafts.edit', $draft) }}"
                   class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                    Edit
                </a>
            </div>
        </div>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    {{-- ID --}}
    <div class="border border-purple-500 rounded-lg pl-6 mb-8">
        <table class="text-base text-gray-600 border-collapse w-full">
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">ID</td>
                <td class="py-1">{{ $draft->id }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Status</td>
                <td class="py-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $draft->status === \MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Enums\PodcastEpisodeDraftStatus::ready_to_create_production_episode
                            ? 'bg-green-100 text-green-800'
                            : 'bg-gray-100 text-gray-800' }}">
                        {{ $draft->status->label() }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    {{-- General --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">General</div>
    <div class="border border-purple-500 rounded-lg pl-6 mb-8">
        <table class="text-base text-gray-600 border-collapse w-full">
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Show</td>
                <td class="py-1 text-left">
                    <a href="{{ route('podcast_shows.show', $draft->show) }}"
                       class="text-purple-700 text-base hover:underline">{{ $draft->show?->title }}</a>
                </td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-700 whitespace-nowrap align-top w-48">Title</td>
                <td class="py-1 text-lg">{{ $draft->title }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Episode Number</td>
                <td class="py-1 text-gray-800">{{ $draft->episode_number ?? '—' }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Tentative Date</td>
                <td class="py-1 text-gray-800">{{ $draft->date?->format('M d, Y') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Comments</td>
                <td class="py-1 text-gray-800">{{ $draft->comments ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- Draft --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Draft</div>
    <div class="border border-purple-500 rounded-lg pl-6 mb-8">
        <table class="text-base text-gray-600 border-collapse w-full">
            <tr x-data="{ open: false }">
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top w-48">Content</td>
                <td class="py-2">
                    @if ($draft->draft)
                        <a href="javascript:void(0);"
                           @click="open = !open"
                           class="text-purple-700 hover:underline">
                            <span x-text="open ? 'Hide' : 'Show'"></span>
                        </a>
                        <div x-show="open" x-transition class="mt-2 markdown-content text-gray-800">{!! Str::markdown($draft->draft) !!}</div>
                    @else
                        <span class="text-gray-400">(no draft content yet)</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Guest --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Guest</div>
    <div class="border border-purple-500 rounded-lg pl-6 mb-8">
        <table class="text-base text-gray-600 border-collapse w-full">
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Guest Notes</td>
                <td class="py-1 text-gray-800">{{ $draft->guest_notes ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- Basecamp --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Basecamp</div>
    <div class="border border-purple-500 rounded-lg pl-6 mb-8">
        <table class="text-base text-gray-600 border-collapse w-full">
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">URL</td>
                <td class="py-1 text-gray-800">
                    @if ($draft->basecamp_url)
                        <a href="{{ $draft->basecamp_url }}" target="_blank" class="text-purple-700 hover:underline">{{ $draft->basecamp_url }}</a>
                    @else
                        —
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Website --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Website</div>
    <div class="border border-purple-500 rounded-lg pl-6 mb-8">
        <table class="text-base text-gray-600 border-collapse w-full">
            <tr x-data="{ open: false }" class="border-b border-gray-300">
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top w-48">Content</td>
                <td class="py-2">
                    @if ($draft->website_content)
                        <a href="javascript:void(0);"
                           @click="open = !open"
                           class="text-purple-700 hover:underline">
                            <span x-text="open ? 'Hide' : 'Show'"></span>
                        </a>
                        <div x-show="open" x-transition class="mt-2 text-gray-800 whitespace-pre-wrap">{{ $draft->website_content }}</div>
                    @else
                        <span class="text-gray-400">(not yet written)</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Excerpt</td>
                <td class="py-1 text-gray-800">{{ $draft->website_excerpt ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- Timestamps --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Timestamps</div>
    <div class="border border-purple-500 rounded-lg pl-6 mb-8">
        <table class="text-base text-gray-600 border-collapse w-full">
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Created</td>
                <td class="py-1 text-gray-800">{{ $draft->created_at->format('M d, Y') }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Updated</td>
                <td class="py-1 text-gray-800">{{ $draft->updated_at->format('M d, Y') }}</td>
            </tr>
        </table>
    </div>

    {{-- ── Links ──────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-4 mt-4">
        <h2 class="text-xl font-bold text-gray-800">
            Links
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $draft->links->count() }})</span>
        </h2>
    </div>

    @if ($draft->links->isEmpty())
        <div class="border border-gray-400 rounded-lg px-6 py-8 text-center text-sm text-gray-600 mb-8">
            No links attached to this draft yet.
        </div>
    @else
        <div class="border border-gray-400 rounded-lg overflow-hidden mb-8">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-base font-medium text-gray-500 uppercase tracking-wide">Title</th>
                        <th class="px-6 py-3 text-left text-base font-medium text-gray-500 uppercase tracking-wide">Link</th>
                        <th class="px-6 py-3 text-left text-base font-medium text-gray-500 uppercase tracking-wide">Enabled</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($draft->links as $link)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800 max-w-xs truncate">
                                {{ $link->title ?? '(no title)' }}
                            </td>
                            <td class="px-6 py-4 text-base text-gray-500 max-w-xs truncate">
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
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ── Guests ──────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mt-4 mb-4">
        <h2 class="text-xl font-bold text-gray-800">
            Guests
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $draft->guests->count() }})</span>
        </h2>
    </div>

    @if ($draft->guests->isEmpty())
        <div class="border border-gray-400 rounded-lg px-6 py-8 text-center text-sm text-gray-600 mb-8">
            No guests attached to this draft yet.
        </div>
    @else
        <div class="border border-gray-400 rounded-lg overflow-hidden mb-8">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-base font-medium text-gray-500 uppercase tracking-wide">Name</th>
                        <th class="px-6 py-3 text-left text-base font-medium text-gray-500 uppercase tracking-wide">Profile</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($draft->guests as $guest)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('podcast_guests.show', $guest) }}"
                                   class="font-medium text-purple-700 hover:underline">
                                    {{ $guest->full_name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-base truncate max-w-xs">
                                {{ $guest->profile_short ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between text-sm">
        <a href="{{ route('podcast_episode_drafts.index') }}" class="hover:text-purple-700 transition">
            ← Podcast Episode Drafts
        </a>
    </div>

</x-layouts.app>