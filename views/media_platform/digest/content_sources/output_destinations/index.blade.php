<x-layouts.app title="Output Destinations">

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Output Destinations</h1>
            <p class="text-sm text-gray-500 mt-1">SFTP servers, and WordPress sites, where your digest web pages will be published.</p>
        </div>
        <a href="{{ route('output_destinations.create.step1') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-3 rounded-lg transition">
            + Add Destination
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6">
            <p class="text-sm text-green-700 font-semibold">{{ session('success') }}</p>
        </div>
    @endif

    @if ($destinations->total() === 0)

        <div class="border border-dashed border-gray-300 rounded-lg p-12 text-center">
            <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
            <p class="text-sm font-semibold text-gray-500 mb-1">No output destinations yet</p>
            <p class="text-xs text-gray-400 mb-4">Add a destination to start publishing digests.</p>
            <a href="{{ route('output_destinations.create.step1') }}"
               class="inline-block bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                Add your first destination
            </a>
        </div>

    @else

        <div class="flex flex-col gap-4">
            @foreach ($destinations as $destination)
                <div class="border border-gray-200 rounded-lg px-5 py-4 flex items-center justify-between gap-4">

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <p class="text-sm font-bold text-gray-800">{{ $destination->name }}</p>
                            @if ($destination->enabled)
                                <span class="text-xs bg-green-100 text-green-700 font-semibold px-2 py-0.5 rounded-full">Enabled</span>
                            @else
                                <span class="text-xs bg-gray-100 text-gray-500 font-semibold px-2 py-0.5 rounded-full">Disabled</span>
                            @endif
                        </div>
                        @if ($destination->type === 'sftp')
                            <p class="text-xs text-gray-500">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 mr-1">SFTP</span>
                                <code class="font-mono">{{ $destination->username }}@{{ $destination->host }}:{{ $destination->port }}</code>
                                <span class="mx-1">·</span>
                                <code class="font-mono">{{ $destination->path }}</code>
                                @if ($destination->base_url)
                                    <span class="mx-1">·</span>
                                    <a href="{{ $destination->base_url }}" target="_blank" class="text-purple-700 hover:underline">{{ $destination->base_url }}</a>
                                @endif
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $destination->auth_type === 'ssh_key' ? 'SSH Key' : 'Password' }} authentication
                                @php $listCount = $destination->lists->count(); @endphp
                                @if ($listCount > 0)
                                    <span class="mx-1">·</span>
                                    Used by {{ $listCount }} {{ Str::plural('list', $listCount) }}
                                @endif
                            </p>
                        @else
                            <p class="text-xs text-gray-500">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 mr-1">WordPress</span>
                                <a href="{{ $destination->wordpress_url }}" target="_blank" class="hover:text-purple-700 transition">{{ $destination->wordpress_url }}</a>
                                <span class="mx-1">·</span>
                                {{ $destination->wordpress_username }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                Post status: {{ $destination->wordpress_post_status }}
                                @php $listCount = $destination->lists->count(); @endphp
                                @if ($listCount > 0)
                                    <span class="mx-1">·</span>
                                    Used by {{ $listCount }} {{ Str::plural('list', $listCount) }}
                                @endif
                            </p>
                        @endif

                    <div class="flex items-center gap-3 flex-shrink-0">
                        <a href="{{ route('output_destinations.show', $destination) }}"
                            class="text-sm text-purple-700 hover:underline font-semibold">
                            Details
                        </a>
                        <a href="{{ route('output_destinations.delete.confirm', $destination) }}"
                           class="text-sm text-red-500 hover:text-red-700 font-semibold">
                            Delete
                        </a>
                    </div>

                </div>
            @endforeach
        </div>

    @endif

</x-layouts.app>