<x-layouts.app title="{{ $link->title ?? 'Link #' . $link->id }}">

    {{-- Breadcrumb + heading --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('podcast_links.index') }}" class="hover:text-purple-700 transition">← Podcast Links</a>
            <span>›</span>
            <span class="text-gray-700">{{ $link->title ?? 'Link #' . $link->id }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $link->title ?? 'Link #' . $link->id }}</h1>
            <a href="{{ route('podcast_links.edit', $link) }}"
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

    {{-- Link details --}}
    <div class="border border-purple-500 rounded-lg p-6 mb-8">

        <table class="text-sm text-gray-600 border-collapse w-full">

            {{-- Core --}}
            <tr><td colspan="2" class="pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Core</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Title</td>
                <td class="py-1 text-gray-800">{{ $link->title ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">URL</td>
                <td class="py-1">
                    <a href="{{ $link->link }}" target="_blank"
                       class="text-purple-700 hover:underline text-xs break-all">
                        {{ $link->link }}
                    </a>
                </td>
            </tr>
            @if ($link->description)
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Description</td>
                <td class="py-1 text-gray-800">{{ $link->description }}</td>
            </tr>
            @endif

            {{-- Status --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Status</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Enabled</td>
                <td class="py-1">
                    @if ($link->enabled)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                    @endif
                </td>
            </tr>
            @if ($link->comments)
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Comments</td>
                <td class="py-1 text-gray-800">{{ $link->comments }}</td>
            </tr>
            @endif
        </table>

        {{-- Record --}}
        <table class="text-sm text-gray-600 border-collapse w-full mt-4">
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Record</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Created</td>
                <td class="py-1 text-gray-800">{{ $link->created_at->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Updated</td>
                <td class="py-1 text-gray-800">{{ $link->updated_at->format('d M Y') }}</td>
            </tr>
        </table>
    </div>


    @php $episodes = $link->episodes()->orderBy('title')->get(); @endphp

    <div class="border border-purple-500 rounded-lg p-6 mb-8">
        {{-- Episodes --}}
        <div class="pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">
            Attached to Episodes
            <span class="ml-1 text-xs font-normal text-gray-400">({{ $link->episodes()->count() }})</span>
        </div>
        @if ($episodes->isEmpty())
            <p class="text-xs text-gray-400 mt-2">Not attached to any episodes.</p>
        @else
            <div class="mt-2 flex flex-col gap-1">
                @foreach ($episodes as $episode)
                    <a href="{{ route('podcast_episodes.show', $episode) }}"
                       class="text-sm text-gray-700 hover:underline">
                        {{ $episode->title }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mt-2 mb-6">
        <a href="{{ route('podcast_links.delete.confirm', $link) }}"
           class="text-sm text-red-500 hover:text-red-700 font-medium transition">
            Delete this link
        </a>
    </div>

    <div class="mt-6 text-sm">
        <a href="{{ route('podcast_links.index') }}" class="hover:text-purple-700 transition">← Podcast Links</a>
    </div>

</x-layouts.app>