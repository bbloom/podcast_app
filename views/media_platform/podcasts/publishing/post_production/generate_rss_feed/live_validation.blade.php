<x-layouts.app title="Live RSS Validation — {{ $episode->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Live RSS Validation</h1>
        <a href="{{ route('post_production.dashboard') }}"
           class="text-sm text-purple-700 hover:underline">
            Post-Production Dashboard
        </a>
    </div>

    @session('error')
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $value }}
        </div>
    @endsession

    {{-- Episode --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Episode</div>
    <div class="border border-purple-500 rounded-lg pl-6 pr-4 py-4 mb-8">
        <div class="flex items-start gap-5">
            @if ($episode->show->itunes_image)
                <img src="{{ $episode->show->itunes_image }}"
                     alt="{{ $episode->show->title }}"
                     class="w-[75px] h-[75px] rounded-lg object-cover flex-shrink-0 mt-1">
            @endif
            <table class="text-base text-gray-600 border-collapse w-full">
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Show</td>
                    <td class="py-1 text-gray-800">{{ $episode->show->title }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Episode</td>
                    <td class="py-1 text-gray-800 font-medium">{{ $episode->title }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Scheduled Date</td>
                    <td class="py-1 text-gray-800">{{ $episode->scheduled_date?->format('M j, Y') ?? '—' }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if ($sessionExpired)

        {{-- Session expired — recovery section --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Session Expired</div>
        <div class="border border-amber-400 rounded-lg px-6 py-6 mb-8">
            <p class="text-sm text-amber-800 font-semibold mb-2">Your session has expired.</p>
            <p class="text-sm text-gray-600 mb-4">
                The RSS feed has already been uploaded to the live S3 bucket from your previous session,
                but the URL is no longer available here. To revalidate, restart the wizard to regenerate
                and re-upload the feed.
            </p>
            <a href="{{ route('post_production.generate_rss_feed.restart', $episode) }}"
               class="inline-block rounded bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                Restart RSS Wizard
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
                Copy the URL above and paste it into each validator. The episode webpage and audio file
                are already live, so all links in the feed should resolve correctly.
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
                  action="{{ route('post_production.generate_rss_feed.live_validation.promote', $episode) }}">
                @csrf
                <button type="submit"
                        class="rounded bg-purple-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                    Validation Passed — Promote to R2 &rarr;
                </button>
            </form>
        </div>

        {{-- Something went wrong --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Something Went Wrong?</div>
        <div class="border border-gray-200 rounded-lg px-6 py-6 mb-8">
            <p class="text-sm text-gray-500 mb-5">
                If the feed has errors, choose how to proceed:
            </p>
            <div class="flex flex-col gap-3">

                {{-- Fix episode data --}}
                <a href="{{ route('podcast_episodes.show', $episode) }}"
                   class="inline-flex items-center gap-2 text-sm font-medium text-purple-700 hover:underline">
                    Fix episode data
                    <span class="text-xs text-gray-400">→ episode show page</span>
                </a>

                {{-- Regenerate --}}
                <a href="{{ route('post_production.generate_rss_feed.step2', $episode) }}"
                   class="inline-flex items-center gap-2 text-sm font-medium text-purple-700 hover:underline">
                    Regenerate RSS feed
                    <span class="text-xs text-gray-400">→ return to field validation (Step 2)</span>
                </a>

                {{-- Mark as needs attention --}}
                <div class="mt-2 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-400 mb-3">
                        Need to step away and come back to this later?
                        Mark the episode as needing attention — it will appear on your dashboard.
                    </p>
                    <form method="POST"
                          action="{{ route('post_production.generate_rss_feed.live_validation.fail', $episode) }}">
                        @csrf
                        <button type="submit"
                                class="rounded border border-red-300 px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors">
                            Mark as Needs Attention
                        </button>
                    </form>
                </div>
            </div>
        </div>

    @endif

</x-layouts.app>