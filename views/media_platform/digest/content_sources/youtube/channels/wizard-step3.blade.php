<x-layouts.app title="Add Youtube Channel Wizard">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add a Youtube Channel</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 3</span>
            <span>of 5</span>
            <span class="mx-2">—</span>
            <span>Please confirm this is the channel you want to add</span>
        </div>

        {{-- Step dots --}}
        <div class="flex items-center gap-2 mt-3">
            <div class="w-3 h-3 rounded-full bg-purple-300"></div>
            <div class="w-16 h-px bg-purple-300"></div>
            <div class="w-3 h-3 rounded-full bg-purple-300"></div>
            <div class="w-16 h-px bg-purple-300"></div>
            <div class="w-3 h-3 rounded-full bg-purple-700"></div>
            <div class="w-16 h-px bg-gray-300"></div>
            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
            <div class="w-16 h-px bg-gray-300"></div>
            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
        </div>
    </div>

    {{-- Channel card --}}
    <div class="border border-purple-500 rounded-lg p-4 mb-8">
        <div class="flex gap-4">

            <div class="flex-shrink-0">
                <img
                    src="{{ $selected['thumbnail'] }}"
                    alt="{{ $selected['title'] }}"
                    class="w-20 h-20 rounded-full object-cover border border-gray-200"
                >
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-xl font-bold text-gray-800">{{ $selected['title'] }}</p>

                @if ($selected['description'])
                    <p class="mt-2 text-lg text-gray-900 line-clamp-2">{{ $selected['description'] }}</p>
                @endif

                <table class="mt-6 text-lg text-gray-600 border-collapse">
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Handle</td>
                        <td class="py-1 font-bold text-xl text-gray-800">{{ $selected['handle'] ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Channel ID</td>
                        <td class="py-1"><code class="font-bold">{{ $selected['channel_id'] }}</code></td>
                    </tr>
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">URL</td>
                        <td class="py-1">
                            <a href="{{ $selected['channel_url'] }}" target="_blank" class="text-purple-700 hover:underline break-all">
                                {{ $selected['channel_url'] }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">RSS Feed</td>
                        <td class="py-1">
                            <a href="{{ $selected['rss_url'] }}" target="_blank" class="text-purple-700 hover:underline break-all">
                                {{ $selected['rss_url'] }}
                            </a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('youtube.channels.create.step3.submit') }}">
        @csrf
        <div class="flex justify-between items-center">
            <a href="{{ route('youtube.channels.create.step1') }}"
               class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Start over
            </a>

            <button
                type="submit"
                class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition flex items-center gap-2"
            >
                Yes, add this channel...
            </button>
        </div>
    </form>

</x-layouts.app>
