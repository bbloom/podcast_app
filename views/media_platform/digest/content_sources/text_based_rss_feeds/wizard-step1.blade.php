<x-layouts.app title="Add a Text Based RSS Feed">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add a Text Based RSS Feed</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 1</span>
            <span>of 4</span>
            <span class="mx-2">—</span>
            <span>Enter the RSS feed URL</span>
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
        </div>
    </div>

    {{-- Instructions --}}
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6 text-sm text-gray-700">
        <p class="font-semibold text-purple-800 mb-2">What is a text based RSS feed?</p>
        <p class="text-gray-600">Text based RSS feeds are blogs, news sites, press releases, and similar sources that publish written content. This is different from podcasts, which contain audio. The RSS feed URL will look something like:</p>
        <code class="block mt-2 bg-white px-3 py-2 rounded border border-gray-200 text-purple-700 break-all">https://feeds.example.com/blog</code>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('text_based_rss_feeds.create.step1.submit') }}">
        @csrf

        <div class="mb-6">
            <label for="rss_url" class="block text-sm font-semibold text-gray-700 mb-2">
                RSS Feed URL
            </label>
            <input
                type="url"
                id="rss_url"
                name="rss_url"
                value="{{ old('rss_url') }}"
                placeholder="e.g. https://feeds.example.com/blog"
                required
                autofocus
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('rss_url') border-red-400 @enderror"
            >
            @error('rss_url')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end">
            <button
                type="submit"
                class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
            >
                Next Step...
            </button>
        </div>

    </form>

</x-layouts.app>
