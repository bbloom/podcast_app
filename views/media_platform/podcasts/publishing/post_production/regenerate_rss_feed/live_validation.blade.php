<x-layouts.app title="Live RSS Validation — {{ $show->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Live RSS Validation</h1>
        <a href="{{ route('post_production.regenerate_rss_feed.index') }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to Show Selection
        </a>
    </div>

    @session('error')
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $value }}
        </div>
    @endsession

    {{-- Show --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Show</div>
    <div class="border border-purple-500 rounded-lg pl-6 pr-4 py-4 mb-8">
        <div class="flex items-center gap-5">
            @if ($show->itunes_image)
                <img src="{{ $show->itunes_image }}"
                     alt="{{ $show->title }}"
                     class="w-[75px] h-[75px] rounded-lg object-cover flex-shrink-0">
            @endif
            <div class="text-lg font-medium text-gray-800">{{ $show->title }}</div>
        </div>
    </div>

    @if ($sessionExpired)

        {{-- Session expired --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Session Expired</div>
        <div class="border border-amber-400 rounded-lg px-6 py-6 mb-8">
            <p class="text-sm text-amber-800 font-semibold mb-2">Your session has expired.</p>
            <p class="text-sm text-gray-600 mb-4">
                The RSS feed has already been uploaded to live S3, but the URL is no longer
                available in this session. Regenerate the feed to produce a fresh session
                and revalidate.
            </p>
            <a href="{{ route('post_production.regenerate_rss_feed.stage', $show) }}"
               class="inline-block rounded bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                Regenerate RSS Feed
            </a>
        </div>

    @else

        {{-- Live S3 URL --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Live RSS Feed URL</div>
        <div class="border border-green-400 rounded-lg px-6 py-4 mb-8">
            <div class="flex items-center gap-2 text-green-700 mb-3">
                <span>✓</span>
                <span class="text-sm font-semibold">RSS feed uploaded to live S3. Validate before promoting to R2.</span>
            </div>
            <p class="text-xs text-gray-500 mb-2">Paste this URL into the validators below:</p>
            <div class="flex items-center gap-3">
                <code class="text-xs text-gray-800 bg-gray-100 px-3 py-2 rounded font-mono break-all flex-1">{{ $liveS3Url }}</code>
                <button onclick="navigator.clipboard.writeText('{{ $liveS3Url }}')"
                        class="flex-shrink-0 rounded border border-gray-300 px-3 py-2 text-xs text-gray-600 hover:bg-gray-50 transition-colors">
                    Copy
                </button>
            </div>
        </div>

        {{-- External validators --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">External Validators</div>
        <div class="border border-purple-500 rounded-lg px-6 py-4 mb-8">
            <p class="text-sm text-gray-600 mb-5">
                Copy the URL above and paste it into each validator.
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

        {{-- Promote to R2 --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Validation Passed?</div>
        <div class="border border-purple-500 rounded-lg px-6 py-6 mb-8">
            <p class="text-sm text-gray-600 mb-4">
                Once validation passes, promote the feed to Cloudflare R2 — the public CDN
                polled by Apple Podcasts, Spotify, and other directories.
            </p>
            <form method="POST"
                  action="{{ route('post_production.regenerate_rss_feed.live_validation.promote', $show) }}">
                @csrf
                <button type="submit"
                        class="rounded bg-purple-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                    Validation Passed — Promote to R2 &rarr;
                </button>
            </form>
        </div>

        {{-- Something went wrong --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Something Went Wrong?</div>
        <div class="border border-gray-200 rounded-lg px-6 py-6">
            <p class="text-sm text-gray-500 mb-4">
                If the feed has errors, regenerate it or return to the show page.
            </p>
            <div class="flex flex-col gap-3">
                <a href="{{ route('post_production.regenerate_rss_feed.stage', $show) }}"
                   class="inline-flex items-center gap-2 text-sm font-medium text-purple-700 hover:underline">
                    Regenerate RSS feed
                    <span class="text-xs text-gray-400">→ regenerate from scratch</span>
                </a>
                <a href="{{ route('podcast_shows.show', $show) }}"
                   class="inline-flex items-center gap-2 text-sm font-medium text-purple-700 hover:underline">
                    Return to show
                    <span class="text-xs text-gray-400">→ {{ $show->title }}</span>
                </a>
            </div>
        </div>

    @endif

</x-layouts.app>