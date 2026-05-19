<x-layouts.app title="Generate RSS Feed — Step 1">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Generate RSS Feed</h1>
        <a href="{{ route('post_production.generate_rss_feed.index') }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to Generate RSS Feed
        </a>
    </div>

    {{-- Step dots --}}
    @include('media_platform.podcasts.publishing.post_production.generate_rss_feed._step_dots', ['currentStep' => 1])

    @session('error')
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
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
                <tr class="border-b border-gray-100">
                    <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top w-48">Show</td>
                    <td class="py-2 text-gray-800">{{ $episode->show->title }}</td>
                </tr>
                <tr class="border-b border-gray-100">
                    <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top">Episode</td>
                    <td class="py-2 text-gray-800 font-medium">{{ $episode->title }}</td>
                </tr>
                <tr class="border-b border-gray-100">
                    <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top">Scheduled Date</td>
                    <td class="py-2 text-gray-800">{{ $episode->scheduled_date?->format('M j, Y') ?? '—' }}</td>
                </tr>
                <tr class="border-b border-gray-100">
                    <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top">Publish Date</td>
                    <td class="py-2 text-gray-800">{{ $episode->itunes_pubdate?->format('M j, Y H:i') ?? '—' }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Critical fields — enclosure length and duration --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Critical Fields</div>
    <div class="border border-purple-500 rounded-lg pl-6 pr-4 py-4 mb-8">
        <table class="text-base text-gray-600 border-collapse w-full">
            <tr class="border-b border-gray-100">
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top w-48">Enclosure Length</td>
                <td class="py-2">
                    @if ($episode->itunes_enclosure_length)
                        <span class="text-gray-800 font-mono">{{ number_format((int) $episode->itunes_enclosure_length) }} bytes</span>
                        <span class="ml-3 text-gray-400 text-sm">({{ number_format((int) $episode->itunes_enclosure_length / 1048576, 2) }} MB)</span>
                    @else
                        <span class="text-red-600 font-medium">Not set</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top">Duration</td>
                <td class="py-2">
                    @if ($episode->itunes_duration)
                        <span class="text-gray-800 font-mono">{{ $episode->itunes_duration }}</span>
                    @else
                        <span class="text-red-600 font-medium">Not set</span>
                    @endif
                </td>
            </tr>
        </table>
        <p class="mt-4 text-xs text-gray-400">
            These values were extracted from the production MP3 during upload. If they look wrong,
            edit the episode before proceeding.
        </p>
    </div>

    {{-- Review all fields --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Review All Fields</div>
    <div class="border border-purple-500 rounded-lg px-6 py-4 mb-8 text-sm text-gray-600">
        <p>Before generating the RSS feed, review all episode fields to ensure they are correct.</p>
        <p class="mt-3">
            <a href="{{ route('podcast_episodes.show', $episode) }}"
               target="_blank"
               class="inline-block rounded bg-purple-100 px-4 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-200 transition-colors">
                Open Episode Page &rarr;
            </a>
            <span class="ml-3 text-xs text-gray-400">Opens in a new tab. Return here when you are satisfied.</span>
        </p>
    </div>

    {{-- Confirm --}}
    <div class="flex items-center gap-4">

        <form method="POST"
              action="{{ route('post_production.generate_rss_feed.step1.store', $episode) }}">
            @csrf
            <button type="submit"
                    class="rounded bg-purple-700 px-6 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                Fields Look Good — Continue to Validation
            </button>
        </form>

        <a href="{{ route('post_production.generate_rss_feed.index') }}"
           class="inline-block rounded border border-gray-400 px-6 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
            Cancel
        </a>

    </div>

</x-layouts.app>