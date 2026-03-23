<x-layouts.app title="Fix Connection — Username">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Fix Connection</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Username</span>
            <span class="mx-2">—</span>
            <span>Correct your username, then return to the connection test</span>
        </div>
    </div>

    <div class="bg-red-50 border border-red-300 rounded-lg p-4 mb-6">
        <p class="text-sm text-red-800 font-semibold">The connection test failed — the username may be incorrect.</p>
        <p class="text-sm text-red-700 mt-1">Update the username below, save, and the test will run again automatically.</p>
    </div>

    <form method="POST" action="{{ route('output_destinations.fix.sftp.username.submit') }}">
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
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('output_destinations.create.step7') }}" class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to test
            </a>
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Save &amp; Retry Test →
            </button>
        </div>

    </form>

</x-layouts.app>