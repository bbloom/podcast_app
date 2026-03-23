<x-layouts.app title="Add Youtube Channel Wizard">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add a Youtube Channel</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 1</span>
            <span>of 5</span>
            <span class="mx-2">—</span>
            <span>Enter your Youtube channel</span>
        </div>

        {{-- Step dots --}}
        <div class="flex items-center gap-2 mt-3">
            <div class="w-3 h-3 rounded-full bg-purple-700"></div>
            <div class="w-16 h-px bg-gray-300"></div>
            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
            <div class="w-16 h-px bg-gray-300"></div>
            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
            <div class="w-16 h-px bg-gray-300"></div>
            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
            <div class="w-16 h-px bg-gray-300"></div>
            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
        </div>
    </div>

    {{-- Instructions --}}
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6 text-sm text-gray-700">
        <p class="font-semibold text-purple-800 mb-2">You have four ways to add your Youtube channel:</p>
        <ul class="space-y-2 list-disc list-inside text-gray-600">
            <li>Type the channel's handle: <code class="bg-white px-1 rounded border border-gray-200 text-purple-700">@ChannelName</code></li>
            <li>Type the full URL with the channel's handle: <code class="bg-white px-1 rounded border border-gray-200 text-purple-700">https://www.youtube.com/@ChannelName</code></li>
            <li>Type the full URL with the channel's ID: <code class="bg-white px-1 rounded border border-gray-200 text-purple-700">https://www.youtube.com/channel/UCxxxxxxxxxxxxxx</code></li>
            <li>Or just search by name or keywords: <code class="bg-white px-1 rounded border border-gray-200 text-purple-700">The Midnight Special</code></li>
        </ul>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('youtube.channels.create.step1.submit') }}">
        @csrf

        <div class="mb-6">
            <label for="query" class="block text-sm font-semibold text-gray-700 mb-2">
                Youtube Channel URL, Handle, or Keywords
            </label>
            <input
                type="text"
                id="query"
                name="query"
                value="{{ old('query') }}"
                placeholder="e.g. @ChannelName, https://www.youtube.com/@ChannelName, or The Midnight Special"
                required
                autofocus
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('query') border-red-400 @enderror"
            >
            @error('query')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end">
            <button
                type="submit"
                class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition flex items-center gap-2"
            >
                Next Step...
            </button>
        </div>

    </form>

</x-layouts.app>
