<x-layouts.app title="Add Output Destination — Step 4">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add an Output Destination</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 4</span>
            <span class="mx-2">—</span>
            <span>Username</span>
        </div>
    </div>

    <form method="POST" action="{{ route('output_destinations.create.step4.submit') }}">
        @csrf

        <div class="mb-6">
            <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">SFTP Username</label>
            <input
                type="text"
                id="username"
                name="username"
                value="{{ old('username', session('od_wizard.username')) }}"
                placeholder="e.g. deploy or forge"
                required
                autofocus
                autocomplete="off"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('username') border-red-400 @enderror"
            >
            @error('username')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">The username you use to connect to your SFTP server.</p>
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('output_destinations.create.step3') }}" class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Next Step →
            </button>
        </div>

    </form>

</x-layouts.app>