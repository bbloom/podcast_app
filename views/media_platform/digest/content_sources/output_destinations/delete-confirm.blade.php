<x-layouts.app title="Delete Output Destination">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Delete Output Destination</h1>
    </div>

    {{-- Warning --}}
    <div class="bg-red-50 border border-red-300 rounded-lg p-4 mb-6">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div class="text-sm text-red-800">
                <p class="font-semibold mb-1">This action cannot be undone.</p>
                <p>Deleting this destination will remove all its credentials permanently. Any lists currently using this destination will lose their output destination assignment.</p>
            </div>
        </div>
    </div>

    {{-- Destination summary --}}
    <div class="border border-red-300 rounded-lg p-6 mb-8">
        <table class="text-sm text-gray-600 border-collapse">
            <tr>
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap font-semibold w-32">Name</td>
                <td class="py-2 text-gray-800 font-bold">{{ $outputDestination->name }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap font-semibold">Host</td>
                <td class="py-2 text-gray-800"><code>{{ $outputDestination->host }}</code></td>
            </tr>
            <tr>
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap font-semibold">Port</td>
                <td class="py-2 text-gray-800"><code>{{ $outputDestination->port }}</code></td>
            </tr>
            <tr>
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap font-semibold">Username</td>
                <td class="py-2 text-gray-800"><code>{{ $outputDestination->username }}</code></td>
            </tr>
        </table>
    </div>

    <form method="POST" action="{{ route('output_destinations.destroy', $outputDestination) }}">
        @csrf
        @method('DELETE')
        <div class="flex justify-between items-center">
            <a href="{{ route('output_destinations.index') }}"
               class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Cancel, take me back
            </a>
            <button
                type="submit"
                class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
            >
                Yes, permanently delete this destination
            </button>
        </div>
    </form>

</x-layouts.app>
