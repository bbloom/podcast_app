<x-layouts.app title="Podcast Shows">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Podcast Shows</h1>
        <a href="{{ route('podcast_shows.create') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + New Show
        </a>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    @if ($shows->total() === 0)

        <div class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
            </svg>
            <p class="text-sm font-semibold text-gray-500 mb-1">No podcast shows yet</p>
            <p class="text-sm text-gray-400 mb-6">Create your first podcast show to get started.</p>
            <a href="{{ route('podcast_shows.create') }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Create a Show
            </a>
        </div>

    @else

        <div class="flex flex-col gap-3">
            @foreach ($shows as $show)
                <div class="border border-purple-300 rounded-lg px-5 py-4 flex items-center justify-between hover:border-gray-300 transition">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-sm font-semibold text-gray-400 flex-shrink-0">
                                (id #{{ $show->id }})
                            </span>

                            <a href="{{ route('podcast_shows.show', $show) }}">
                                <img 
                                    src="{{ $show->itunes_image }}" 
                                    alt="" 
                                    class="w-[100px] h-[100px] rounded-lg object-cover border border-gray-200 flex-shrink-0"
                                >
                            </a>
                        </div>
                        <p class="mt-4 text-lg text-purple-700">{{ $show->description }}</p>
                    </div>
                    <div class="flex items-center gap-3 ml-4 flex-shrink-0">
                        <a href="{{ route('podcast_shows.show', $show) }}"
                           class="inline-block bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition"
                        >
                            Details
                        </a>

                        @if ($show->id != 2)
                            <a 
                                href="{{ route('post_production.trigger_builds.select', $show) }}"          class="inline-block bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition"
                         >
                                Trigger Static Site Builds
                            </a>
                        @endif
                        
                    </div>
                </div>                
            @endforeach
        </div>

        @if ($shows->hasPages())
            <div class="mt-4">{{ $shows->links() }}</div>
        @endif

    @endif

</x-layouts.app>