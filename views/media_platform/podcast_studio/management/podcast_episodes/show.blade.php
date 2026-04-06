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

    {{-- ID --}}
    <div class="border border-purple-500 rounded-lg pl-6 mb-8">
        <table class="text-base text-gray-600 border-collapse w-full">
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">ID</td>
                <td class="py-1">{{ $episode->id }}</td>
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
                    <a href="{{ route('podcast_shows.show', $episode->show) }}"
                       class="text-purple-700 text-base hover:underline">{{ $episode->show?->title }}</a>
                </td>
            </tr>
             <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-700 whitespace-nowrap align-top w-48">Title</td>
                <td class="py-1 text-lg">{{ $episode->title }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Slug</td>
                <td class="py-1 text-gray-800">{{ $episode->slug }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Scheduled</td>
                <td class="py-1 text-gray-800">{{ $episode->scheduled_date?->format('M d, Y') ?? '—' }}</td>
            </tr>
            <tr x-data="{ open: false }" class="border-b border-gray-300">
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top w-32">Draft</td>
                <td class="py-2">
                    <a href="javascript:void(0);" 
                    @click="open = !open" 
                    class="text-purple-700 hover:underline">
                    <span x-text="open ? 'Hide' : 'Show'"></span>
                    </a>
                    <div x-show="open" x-transition class="mt-2 text-gray-800">
                    {{ $episode->draft ?? '(there is no draft)' }}
                    </div>
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Raw Audio</td>
                <td class="py-1 text-gray-800">{{ $episode->raw_input_audio_filename }}</td>
            </tr>
        </table>
    </div>

    {{-- Status --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Status</div>
    <div class="border border-purple-500 rounded-lg pl-6 mb-8">
        <table class="text-base text-gray-600 border-collapse w-full">
             <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Status</td>
                <td class="py-1 text-gray-800">{{ $episode->status?->label() }}</td>
            </tr>
        </table>
    </div>

    {{-- iTunes --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Apple</div>
    <div class="border border-purple-500 rounded-lg pl-6 mb-8">
        <table class="text-base text-gray-600 border-collapse w-full">
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Primary Title</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_title_tag }}</td>
            </tr>
            <tr x-data="{ open: false }" class="border-b border-gray-300">
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top w-32">Description</td>
                <td class="py-2">
                    <a href="javascript:void(0);" 
                    @click="open = !open" 
                    class="text-purple-700 hover:underline">
                    <span x-text="open ? 'Hide' : 'Show'"></span>
                    </a>
                    <div x-show="open" x-transition class="mt-2 text-gray-800">
                    <pre class="text-sm text-gray-800 whitespace-pre-wrap font-sans">{{ $episode->itunes_description }}</pre>
                    </div>
                </td>
            </tr>
            <tr x-data="{ open: false }" class="border-b border-gray-300">
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top w-32">Summary</td>
                <td class="py-2">
                    <a href="javascript:void(0);" 
                    @click="open = !open" 
                    class="text-purple-700 hover:underline">
                    <span x-text="open ? 'Hide' : 'Show'"></span>
                    </a>
                    <div x-show="open" x-transition class="mt-2 text-gray-800">
                    <pre class="text-sm text-gray-800 whitespace-pre-wrap font-sans">{{ $episode->itunes_summary }}</pre>
                    </div>
                </td>
            </tr>
            <tr x-data="{ open: false }" class="border-b border-gray-300">
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top w-32">Content Encoded</td>
                <td class="py-2">
                    <a href="javascript:void(0);" 
                    @click="open = !open" 
                    class="text-purple-700 hover:underline">
                    <span x-text="open ? 'Hide' : 'Show'"></span>
                    </a>
                    <div x-show="open" x-transition class="mt-2 text-gray-800">
                    {{ $episode->itunes_content_encoded }}
                    </div>
                </td>
            </tr>            
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Subtitle</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_subtitle }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Media File URL</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_enclosure_url }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Media File Size</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_enclosure_length }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Media File Type</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_enclosure_type }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">GUID</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_guid }}</td>
            </tr>
             <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Publish  Date</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_pubdate?->format('M d, Y H:i') ?? '—' }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Duration</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_duration }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Website Link</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_link }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Image</td>
                <td class="py-1 text-gray-800">{{ $episode->image ?: '—' }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Explicit</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_explicit ? '✅' : '❌' }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Secondary Title</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_itunestitle_tag }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Episode Number</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_episode }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Season Number</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_season }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Episode Type</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_episode_type }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Block</td>
                <td class="py-1 text-gray-800">{{ $episode->itunes_block ? '✅' : '❌' }}</td>
            </tr>
        </table>
    </div>

    {{-- RSS --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">RSS</div>
    <div class="border border-purple-500 rounded-lg pl-6 mb-8">
        <table class="text-base text-gray-600 border-collapse w-full">
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">RSS Feed</td>
                <td class="py-1">
                    @if ($episode->rss_feed_enabled)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-base font-medium text-green-800">✅</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-base font-medium text-gray-600">❌</span>
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
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top w-32">Content</td>
                <td class="py-2">
                    <a href="javascript:void(0);" 
                    @click="open = !open" 
                    class="text-purple-700 hover:underline">
                    <span x-text="open ? 'Hide' : 'Show'"></span>
                    </a>
                    <div x-show="open" x-transition class="mt-2 text-gray-800">
                    {{ $episode->website_content }}
                    </div>
                </td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Excerpt</td>
                <td class="py-1 text-gray-800">{{ $episode->website_excerpt }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Meta Description</td>
                <td class="py-1 text-gray-800">{{ $episode->website_meta_description }}</td>
            </tr>
            <tr x-data="{ open: false }" class="border-b border-gray-300">
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top w-32">Notes</td>
                <td class="py-2">
                    <a href="javascript:void(0);" 
                    @click="open = !open" 
                    class="text-purple-700 hover:underline">
                    <span x-text="open ? 'Hide' : 'Show'"></span>
                    </a>
                    <div x-show="open" x-transition class="mt-2 text-gray-800">
                    {{ $episode->website_episode_notes ?? '—'}}
                    </div>
                </td>
            </tr>
            <tr x-data="{ open: false }" class="border-b border-gray-300">
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top w-32">Attribution</td>
                <td class="py-2">
                    <a href="javascript:void(0);" 
                    @click="open = !open" 
                    class="text-purple-700 hover:underline">
                    <span x-text="open ? 'Hide' : 'Show'"></span>
                    </a>
                    <div x-show="open" x-transition class="mt-2 text-gray-800">
                    {{ $episode->website_attribution }}
                    </div>
                </td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Image</td>
                <td class="py-1 text-gray-800">{{ $episode->website_featured_image ?? '—' }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Publish On</td>
                <td class="py-1 text-gray-800">{{ $episode->website_publish_on?->format('M d, Y') ?? '—' }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Enabled</td>
                <td class="py-1">
                    @if ($episode->website_enabled)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-base font-medium text-green-800">✅</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-base font-medium text-gray-600">❌</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Timestamp --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Timestamps</div>
    <div class="border border-purple-500 rounded-lg pl-6 mb-8">
        <table class="text-base text-gray-600 border-collapse w-full">
            <tr class="border-b border-gray-300">
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Created</td>
                <td class="py-1 text-gray-800">{{ $episode->created_at->format('M d, Y') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-48">Updated</td>
                <td class="py-1 text-gray-800">{{ $episode->updated_at->format('M d, Y') ?? '—' }}</td>
            </tr>

        </table>
    </div>


    {{-- ── Links ──────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-4 mt-4">
        <h2 class="text-xl font-bold text-gray-800">
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
        <div class="border border-gray-400 rounded-lg px-6 py-8 text-center text-sm text-gray-600 mb-8">
            No links attached to this episode yet.
        </div>
    @else
        <div class="border border-gray-400 rounded-lg overflow-hidden mb-8">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-base font-medium text-gray-500 uppercase tracking-wide">Title</th>
                        <th class="px-6 py-3 text-left text-base font-medium text-gray-500 uppercase tracking-wide">Link</th>
                        <th class="px-6 py-3 text-left text-base font-medium text-gray-500 uppercase tracking-wide">Enabled</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($links as $link)
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
                            <td class="px-6 py-4 text-right">
                                <form method="POST"
                                      action="{{ route('podcast_links.detach', [$episode, $link]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-base text-gray-400 hover:text-red-600 font-medium transition">
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


    {{-- ── Guests ──────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mt-4 mb-4">
        <h2 class="text-xl font-bold text-gray-800">
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
        <div class="border border-gray-400 rounded-lg px-6 py-8 text-center text-sm text-gray-600 mb-8">
            No guests attached to this episode yet.
        </div>
    @else
        <div class="border border-gray-400 rounded-lg overflow-hidden mb-8">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-base font-medium text-gray-500 uppercase tracking-wide">Name</th>
                        <th class="px-6 py-3 text-left text-base font-medium text-gray-500 uppercase tracking-wide">Profile</th>
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
                            <td class="px-6 py-4 text-gray-500 text-base truncate max-w-xs">
                                {{ $guest->profile_short ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST"
                                      action="{{ route('podcast_guests.detach.guest', [$episode, $guest]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-base text-gray-400 hover:text-red-600 font-medium transition">
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


<div class="mt-6 flex items-center justify-between text-sm">
    <a href="{{ route('podcast_episodes.index') }}" class="hover:text-purple-700 transition">
        ← Episodes
    </a>

    <a href="{{ route('post_production.dashboard') }}"
       class="bg-purple-800 hover:bg-purple-400 text-white text-sm font-bold px-5 py-2.5 rounded-lg transition">
        Podcast Post Production Dashboard
    </a>
</div>
</x-layouts.app>