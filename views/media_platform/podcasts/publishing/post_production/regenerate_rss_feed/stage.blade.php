<x-layouts.app title="Regenerate RSS Feed — {{ $show->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Regenerate RSS Feed</h1>
        <a href="{{ route('post_production.regenerate_rss_feed.index') }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to Show Selection
        </a>
    </div>

    {{-- Show --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Show</div>
    <div class="border border-purple-500 rounded-lg pl-6 pr-4 py-4 mb-8">
        <div class="flex items-center gap-5">
            @if ($show->itunes_image)
                <img src="{{ $show->itunes_image }}"
                     alt="{{ $show->title }}"
                     class="w-[75px] h-[75px] rounded-lg object-cover flex-shrink-0">
            @endif
            <div>
                <div class="text-lg font-medium text-gray-800">{{ $show->title }}</div>
                <div class="text-sm text-gray-500 font-mono mt-1">{{ $filename }}</div>
            </div>
        </div>
    </div>

    {{-- Upload result --}}
    @if ($uploadError)

        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Staging Upload</div>
        <div class="border border-red-400 rounded-lg px-6 py-4 mb-8">
            <p class="text-sm text-red-700 font-semibold mb-2">The RSS file could not be uploaded to the staging bucket.</p>
            <p class="text-sm text-gray-600 mb-4">{{ $uploadError }}</p>
            <a href="{{ route('post_production.regenerate_rss_feed.stage', $show) }}"
               class="inline-block rounded bg-purple-700 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                Try Again
            </a>
        </div>

    @else

        {{-- Staging URL --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Staging Feed URL</div>
        <div class="border border-green-400 rounded-lg px-6 py-4 mb-8">
            <div class="flex items-center gap-3 text-green-700 mb-4">
                <span class="text-lg">✓</span>
                <span class="text-sm font-semibold">RSS feed regenerated and uploaded to staging successfully.</span>
            </div>
            <p class="text-xs text-gray-500 mb-2">Paste this URL into the validators below:</p>
            <div class="flex items-center gap-3">
                <code class="text-xs text-gray-800 bg-gray-100 px-3 py-2 rounded font-mono break-all flex-1">{{ $stagingUrl }}</code>
                <button onclick="navigator.clipboard.writeText('{{ $stagingUrl }}')"
                        class="flex-shrink-0 rounded border border-gray-300 px-3 py-2 text-xs text-gray-600 hover:bg-gray-50 transition-colors">
                    Copy
                </button>
            </div>
        </div>

        {{-- External validators --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">External Validators</div>
        <div class="border border-purple-500 rounded-lg px-6 py-4 mb-8">
            <p class="text-sm text-gray-600 mb-5">
                Copy the staging URL above, paste it into each validator, and check for errors or warnings.
            </p>
            <ul class="space-y-3">
                <li>
                    <a href="https://www.castfeedvalidator.com" target="_blank"
                       class="text-purple-700 hover:underline font-semibold text-sm">
                        Cast Feed Validator &nearr;
                    </a>
                    <span class="ml-2 text-xs text-gray-400">castfeedvalidator.com — primary validator for Apple Podcasts</span>
                </li>
                <li>
                    <a href="https://podba.se/validate/" target="_blank"
                       class="text-purple-700 hover:underline font-semibold text-sm">
                        Podbase &nearr;
                    </a>
                    <span class="ml-2 text-xs text-gray-400">podba.se — validates against Apple, Spotify, and Google</span>
                </li>
                <li>
                    <a href="https://podcastpage.io/tool/podcast-feed-validator" target="_blank"
                       class="text-purple-700 hover:underline font-semibold text-sm">
                        Podcastpage Feed Validator &nearr;
                    </a>
                    <span class="ml-2 text-xs text-gray-400">podcastpage.io — useful secondary check</span>
                </li>
            </ul>
        </div>

        {{-- Promote --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Promote to Live</div>
        <div class="border border-purple-500 rounded-lg px-6 py-6 mb-8">
            <p class="text-sm text-gray-600 mb-6">
                Once validation passes, promote the feed to live. This replaces the current live RSS feed
                for <strong>{{ $show->title }}</strong> on both S3 and Cloudflare R2.
            </p>
            <form method="POST"
                  action="{{ route('post_production.regenerate_rss_feed.promote', $show) }}">
                @csrf
                <button type="submit"
                        class="rounded bg-purple-700 px-6 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                    Validation Passed — Promote to Live &rarr;
                </button>
            </form>
        </div>

    @endif

</x-layouts.app>