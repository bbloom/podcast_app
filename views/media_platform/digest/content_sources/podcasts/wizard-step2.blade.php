<x-layouts.app title="Add a Podcast">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add a Podcast</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 2</span>
            <span>of 4</span>
            <span class="mx-2">—</span>
            <span>Is this the podcast you want to add?</span>
        </div>

        {{-- Step dots --}}
        <div class="flex items-center gap-2 mt-3">
            <div class="w-3 h-3 rounded-full bg-purple-300"></div>
            <div class="w-16 h-px bg-purple-300"></div>
            <div class="w-3 h-3 rounded-full bg-purple-700"></div>
            <div class="w-16 h-px bg-gray-300"></div>
            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
            <div class="w-16 h-px bg-gray-300"></div>
            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
        </div>
    </div>

    {{-- Podcast card --}}
    <div class="border border-purple-500 rounded-lg p-5 mb-8">
        <div class="flex gap-4">

            {{-- Cover art --}}
            <div class="flex-shrink-0">
                @if ($podcast['thumbnail'])
                    <img
                        src="{{ $podcast['thumbnail'] }}"
                        alt="{{ $podcast['title'] }}"
                        class="w-24 h-24 rounded-lg object-cover border border-gray-200"
                    >
                @else
                    <div class="w-24 h-24 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Details --}}
            <div class="flex-1 min-w-0">
                <p class="text-xl font-bold text-gray-800">{{ $podcast['title'] }}</p>

                @if ($podcast['description'])
                    <p class="mt-2 text-sm text-gray-600 line-clamp-3">{{ $podcast['description'] }}</p>
                @endif

                <table class="mt-4 text-sm text-gray-600 border-collapse">
                    @if ($podcast['site_url'])
                        <tr>
                            <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Website</td>
                            <td class="py-1">
                                <a href="{{ $podcast['site_url'] }}" target="_blank" class="text-purple-700 hover:underline break-all">
                                    {{ $podcast['site_url'] }}
                                </a>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">RSS Feed</td>
                        <td class="py-1">
                            <a href="{{ $podcast['rss_url'] }}" target="_blank" class="text-purple-700 hover:underline break-all">
                                {{ $podcast['rss_url'] }}
                            </a>
                        </td>
                    </tr>
                </table>
            </div>

        </div>
    </div>

    {{-- Actions --}}
    <form method="POST" action="{{ route('digest-podcasts.create.step2.submit') }}">
        @csrf
        <div class="flex justify-between items-center">
            <a href="{{ route('digest-podcasts.create.step1') }}"
               class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Start over
            </a>

            <button
                type="submit"
                class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
            >
                Yes, add this podcast...
            </button>
        </div>
    </form>

</x-layouts.app>
