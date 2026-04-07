<x-layouts.app title="Generate RSS Feed — Step 4">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Generate RSS Feed</h1>
        <a href="{{ route('post_production.generate_rss_feed.step3', $episode) }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to Step 3
        </a>
    </div>

    @include('media_platform.podcast_studio.post_production.generate_rss_feed._step_dots', ['currentStep' => 4])

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
                <tr class="border-b border-gray-100">
                    <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top w-48">Show</td>
                    <td class="py-2 text-gray-800">{{ $episode->show->title }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top">Episode</td>
                    <td class="py-2 text-gray-800 font-medium">{{ $episode->title }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Staging URL --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Your Staging Feed URL</div>
    <div class="border border-purple-500 rounded-lg px-6 py-4 mb-8">
        <p class="text-xs text-gray-500 mb-2">
            This feed has not been promoted to live yet. Paste this URL into the validators below.
        </p>
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
                <span class="ml-2 text-xs text-gray-400">castfeedvalidator.com — the primary validator for Apple Podcasts</span>
            </li>
            <li>
                <a href="https://podba.se/validate/" target="_blank"
                   class="text-purple-700 hover:underline font-semibold text-sm">
                    Podbase &nearr;
                </a>
                <span class="ml-2 text-xs text-gray-400">podba.se — validates against Apple, Spotify, and Google specifications</span>
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

    {{-- Actions --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">What Next?</div>
    <div class="border border-purple-500 rounded-lg px-6 py-6 mb-8">

        <p class="text-sm text-gray-600 mb-6">
            Once you have validated the feed and are satisfied, promote it to live.
            If something failed, return to the episode to fix the issue and regenerate.
        </p>

        <div class="flex items-center gap-4">

            {{-- Promote to live --}}
            <form method="POST"
                  action="{{ route('post_production.generate_rss_feed.step5', $episode) }}">
                @csrf
                <button type="submit"
                        class="rounded bg-purple-700 px-6 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                    Validation Passed — Promote to Live &rarr;
                </button>
            </form>

            {{-- Something failed --}}
            <form method="POST"
                  action="{{ route('post_production.generate_rss_feed.step4.failed', $episode) }}">
                @csrf
                <button type="submit"
                        class="rounded border border-red-400 px-6 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 transition-colors">
                    Something Failed — Back to Episode
                </button>
            </form>

        </div>

    </div>

</x-layouts.app>