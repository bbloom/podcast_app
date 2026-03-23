<x-layouts.app title="Add Output Destination — Step 6">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add an Output Destination</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 6</span>
            <span class="mx-2">—</span>
            <span>Remote path and public URL</span>
        </div>
    </div>

    <form method="POST" action="{{ route('output_destinations.create.step6.submit') }}">
        @csrf

        <div class="mb-6">
            <label for="path" class="block text-sm font-semibold text-gray-700 mb-2">Remote Path</label>
            <input
                type="text"
                id="path"
                name="path"
                value="{{ old('path', session('od_wizard.path')) }}"
                placeholder="e.g. /public_html/digests"
                required
                autofocus
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('path') border-red-400 @enderror"
            >
            @error('path')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">The full path on the server where digest files will be uploaded. This directory must already exist and be writable.</p>
        </div>

        <div class="mb-6">
            <label for="base_url" class="block text-sm font-semibold text-gray-700 mb-2">
                Public URL <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input
                type="url"
                id="base_url"
                name="base_url"
                value="{{ old('base_url', session('od_wizard.base_url')) }}"
                placeholder="e.g. https://mysite.com/digests"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('base_url') border-red-400 @enderror"
            >
            @error('base_url')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">The publicly accessible URL for the remote path. Used in notification emails so subscribers can click through to the digest.</p>
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('output_destinations.create.step5') }}" class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Next Step →
            </button>
        </div>

    </form>

</x-layouts.app>