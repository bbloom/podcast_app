<x-layouts.app title="{{ $guest->full_name }}">

    {{-- Breadcrumb + heading --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('podcast_guests.index') }}" class="hover:text-purple-700 transition">← Podcast Guests</a>
            <span>›</span>
            <span class="text-gray-700">{{ $guest->full_name }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $guest->full_name }}</h1>
            <a href="{{ route('podcast_guests.edit', $guest) }}"
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

    {{-- Guest details --}}
    <div class="border border-purple-500 rounded-lg p-6 mb-8">

        @if ($guest->image_url || $guest->image_thumbnail_url)
            <div class="flex items-center gap-4 mb-6">
                @if ($guest->image_thumbnail_url)
                    <img src="{{ $guest->image_thumbnail_url }}" alt="{{ $guest->full_name }}"
                         class="w-16 h-16 rounded-full object-cover border border-gray-200">
                @endif
                @if ($guest->image_url)
                    <img src="{{ $guest->image_url }}" alt="{{ $guest->full_name }}"
                         class="w-24 h-24 rounded-lg object-cover border border-gray-200">
                @endif
            </div>
        @endif

        <p class="text-xl font-bold text-gray-800 mb-1">{{ $guest->full_name }}</p>
        @if ($guest->profile_short)
            <p class="text-sm text-gray-500 mb-4">{{ $guest->profile_short }}</p>
        @endif

        <table class="text-sm text-gray-600 border-collapse w-full">

            {{-- Profile --}}
            <tr><td colspan="2" class="pt-4 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Profile</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Full Profile</td>
                <td class="py-1 text-gray-800">{{ $guest->profile_full }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Email</td>
                <td class="py-1 text-gray-800">{{ $guest->email_address }}</td>
            </tr>
            @if ($guest->link_to_guest_website)
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Website</td>
                <td class="py-1">
                    <a href="{{ $guest->link_to_guest_website }}" target="_blank"
                       class="text-purple-700 hover:underline text-xs">
                        {{ $guest->link_to_guest_website }}
                    </a>
                </td>
            </tr>
            @endif

            {{-- Status --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Status</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Enabled</td>
                <td class="py-1">
                    @if ($guest->enabled)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                    @endif
                </td>
            </tr>
            @if ($guest->internal_comment)
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Internal Comment</td>
                <td class="py-1 text-gray-800">{{ $guest->internal_comment }}</td>
            </tr>
            @endif

            {{-- Record --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Record</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Created</td>
                <td class="py-1 text-gray-800">{{ $guest->created_at->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Updated</td>
                <td class="py-1 text-gray-800">{{ $guest->updated_at->format('d M Y') }}</td>
            </tr>

        </table>
    </div>

    {{-- ── Episodes ────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-800">
            Episodes
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $guest->episodes()->count() }})</span>
        </h2>
        <a href="{{ route('podcast_guests.attach.episode.index', $guest) }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
            + Attach Episode
        </a>
    </div>

    @php $episodes = $guest->episodes()->orderBy('title')->get(); @endphp

    @if ($episodes->isEmpty())
        <div class="border border-gray-200 rounded-lg px-6 py-8 text-center text-sm text-gray-400 mb-8">
            No episodes attached to this guest yet.
        </div>
    @else
        <div class="border border-gray-200 rounded-lg overflow-hidden mb-8">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Show</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Scheduled</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($episodes as $episode)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('podcast_episodes.show', $episode) }}"
                                   class="font-medium text-purple-700 hover:underline">
                                    {{ $episode->title }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $episode->show?->title ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $episode->scheduled_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST"
                                      action="{{ route('podcast_guests.detach.episode', [$guest, $episode]) }}">
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

    <div class="mt-2 mb-6">
        <a href="{{ route('podcast_guests.delete.confirm', $guest) }}"
           class="text-sm text-red-500 hover:text-red-700 font-medium transition">
            Delete this guest
        </a>
    </div>

    <div class="mt-6 text-sm">
        <a href="{{ route('podcast_guests.index') }}" class="hover:text-purple-700 transition">← Podcast Guests</a>
    </div>

</x-layouts.app>