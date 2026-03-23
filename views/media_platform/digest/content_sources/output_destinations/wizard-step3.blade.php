<x-layouts.app title="Add Output Destination — Step 3">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add an Output Destination</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 3</span>
            <span class="mx-2">—</span>
            <span>Server details</span>
        </div>
    </div>

    <form method="POST" action="{{ route('output_destinations.create.step3.submit') }}">
        @csrf

        <div class="mb-6">
            <label for="host" class="block text-sm font-semibold text-gray-700 mb-2">Host</label>
            <input
                type="text"
                id="host"
                name="host"
                value="{{ old('host', session('od_wizard.host')) }}"
                placeholder="e.g. sftp.mysite.com or 192.168.1.1"
                required
                autofocus
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('host') border-red-400 @enderror"
            >
            @error('host')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">The hostname or IP address of your SFTP server.</p>
        </div>

        <div class="mb-6">
            <label for="port" class="block text-sm font-semibold text-gray-700 mb-2">Port</label>
            <input
                type="number"
                id="port"
                name="port"
                value="{{ old('port', session('od_wizard.port', 22)) }}"
                min="1"
                max="65535"
                required
                class="w-32 border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('port') border-red-400 @enderror"
            >
            @error('port')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">Default is 22. Only change this if your server uses a non-standard port.</p>
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('output_destinations.create.step2') }}" class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Next Step →
            </button>
        </div>

    </form>

</x-layouts.app>