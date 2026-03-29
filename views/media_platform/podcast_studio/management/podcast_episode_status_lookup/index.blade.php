<x-layouts.app title="Episode Status Lookup">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Episode Status Lookup</h1>
        @can('admin')
            <a href="{{ route('podcast_episode_status_lookup.create') }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                + New Status
            </a>
        @endcan
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    @if ($statuses->isEmpty())

        <div class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-sm font-semibold text-gray-500 mb-1">No statuses yet</p>
            @can('admin')
                <p class="text-sm text-gray-400 mb-6">Add the first episode status to get started.</p>
                <a href="{{ route('podcast_episode_status_lookup.create') }}"
                   class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                    Create a Status
                </a>
            @endcan
        </div>

    @else

        <div class="flex flex-col gap-3">
            @foreach ($statuses as $status)
                <div class="border border-gray-200 rounded-lg px-5 py-4 flex items-center justify-between hover:border-gray-300 transition">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <p class="text-sm font-semibold text-gray-800">({{ $status->id }}) {{ $status->title }}</p>
                            @if (! $status->enabled)
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium flex-shrink-0">Disabled</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500">{{ $status->description }}</p>
                    </div>

                    @can('admin')
                        <div class="flex items-center gap-3 ml-4 flex-shrink-0">
                            <a href="{{ route('podcast_episode_status_lookup.show', $status) }}"
                                class="text-xs text-gray-500 hover:text-purple-700 font-medium transition">
                                    Details
                            </a>
                            <a href="{{ route('podcast_episode_status_lookup.delete.confirm', $status) }}"
                               class="text-xs text-gray-400 hover:text-red-600 font-medium transition">
                                Delete
                            </a>
                        </div>
                    @endcan
                </div>
            @endforeach
        </div>

    @endif

</x-layouts.app>