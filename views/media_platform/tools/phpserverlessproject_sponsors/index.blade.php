<x-layouts.app title="PHPServerlessProject Sponsors">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">PHPServerlessProject Sponsors</h1>
        <a href="{{ route('phpserverlessproject_sponsors.create') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + New Sponsor
        </a>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    @if ($sponsors->isEmpty())

        <div class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
            </svg>
            <p class="text-sm font-semibold text-gray-500 mb-1">No sponsors yet</p>
            <p class="text-sm text-gray-400 mb-6">Add the first sponsor to get started.</p>
            <a href="{{ route('phpserverlessproject_sponsors.create') }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Add a Sponsor
            </a>
        </div>

    @else

        <div class="flex flex-col gap-3">
            @foreach ($sponsors as $sponsor)
                <div class="border border-gray-200 rounded-lg px-5 py-4 flex items-center justify-between hover:border-gray-300 transition">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <p class="text-sm font-semibold text-gray-800">{{ $sponsor->full_name }}</p>
                            @if ($sponsor->former_sponsor)
                                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-medium flex-shrink-0">Former</span>
                            @endif
                            @if (! $sponsor->enabled)
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium flex-shrink-0">Disabled</span>
                            @endif
                        </div>
                        @if ($sponsor->profile_short)
                            <p class="text-xs text-gray-500 truncate">{{ $sponsor->profile_short }}</p>
                        @endif
                        <div class="flex items-center gap-3 mt-1">
                            @if ($sponsor->umbrella_sponsor)
                                <span class="text-xs text-purple-600 font-medium">Umbrella</span>
                            @endif
                            @if ($sponsor->basecamp_sponsor)
                                <span class="text-xs text-purple-600 font-medium">Basecamp</span>
                            @endif
                            @if ($sponsor->restream_sponsor)
                                <span class="text-xs text-purple-600 font-medium">Restream</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3 ml-4 flex-shrink-0">
                        <a href="{{ route('phpserverlessproject_sponsors.show', $sponsor) }}"
                           class="text-xs text-gray-500 hover:text-purple-700 font-medium transition">
                            Detail
                        </a>
                        <a href="{{ route('phpserverlessproject_sponsors.delete.confirm', $sponsor) }}"
                           class="text-xs text-gray-400 hover:text-red-600 font-medium transition">
                            Delete
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

    @endif

</x-layouts.app>