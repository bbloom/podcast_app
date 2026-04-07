<x-layouts.app title="Generate RSS Feed — Step 2">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Generate RSS Feed</h1>
        <a href="{{ route('post_production.generate_rss_feed.step1', $episode) }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to Step 1
        </a>
    </div>

    @include('media_platform.podcast_studio.post_production.generate_rss_feed._step_dots', ['currentStep' => 2])

    @session('success')
        <div class="mb-6 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-700">
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
                <tr>
                    <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top">Episode</td>
                    <td class="py-2 text-gray-800 font-medium">{{ $episode->title }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- VALIDATION FAILURES                                               --}}
    {{-- ================================================================ --}}
    @if ($result->failures())

        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Validation Failures</div>
        <div class="border border-red-400 rounded-lg px-6 py-4 mb-8">

            <p class="text-sm text-red-700 font-semibold mb-4">
                The following fields must be corrected before the RSS feed can be generated.
                Edit the episode, then return here to re-run validation.
            </p>

            <ul class="space-y-3">
                @foreach ($result->failures() as $failure)
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 text-red-500 flex-shrink-0">✗</span>
                        <div>
                            <span class="text-sm font-mono text-gray-700">{{ $failure['field'] }}</span>
                            <span class="mx-2 text-gray-300">—</span>
                            <span class="text-sm text-gray-600">{{ $failure['message'] }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">
                <a href="{{ route('podcast_episodes.edit', $episode) }}"
                   target="_blank"
                   class="inline-block rounded bg-purple-700 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                    Edit Episode &rarr;
                </a>
                <span class="ml-3 text-xs text-gray-400">Opens in a new tab. Return here when done.</span>
            </div>

        </div>

    @endif

    {{-- ================================================================ --}}
    {{-- R2 DOWNLOAD FAILED — MANUAL CONFIRMATION                         --}}
    {{-- ================================================================ --}}
    @if ($result->r2DownloadFailed())

        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Enclosure Verification</div>
        <div class="border border-yellow-400 rounded-lg px-6 py-6 mb-8">

            <p class="text-sm text-yellow-800 font-semibold mb-2">
                Could not reach R2 to verify the production file automatically.
            </p>
            <p class="text-sm text-gray-600 mb-6">
                Please confirm or correct the file size and duration values below.
                Submitting this form will save the values and bypass the automatic check.
            </p>

            <form method="POST"
                  action="{{ route('post_production.generate_rss_feed.step2.store', $episode) }}">
                @csrf

                {{-- itunes_enclosure_length --}}
                <div class="mb-5">
                    <label for="itunes_enclosure_length"
                           class="block text-sm font-semibold text-gray-700 mb-2">
                        Media File Size (bytes)
                    </label>
                    <input type="number"
                           id="itunes_enclosure_length"
                           name="itunes_enclosure_length"
                           value="{{ old('itunes_enclosure_length', $episode->itunes_enclosure_length) }}"
                           min="1"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_enclosure_length') border-red-400 @enderror">
                    @error('itunes_enclosure_length')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-400 list-disc list-outside pl-5">
                        <li>The file size in bytes — no commas. e.g. 47823104</li>
                        <li>Check the file size in your S3 or R2 console if unsure.</li>
                    </ul>
                </div>

                {{-- itunes_duration --}}
                <div class="mb-6">
                    <label for="itunes_duration"
                           class="block text-sm font-semibold text-gray-700 mb-2">
                        Duration (HH:MM:SS or MM:SS)
                    </label>
                    <input type="text"
                           id="itunes_duration"
                           name="itunes_duration"
                           value="{{ old('itunes_duration', $episode->itunes_duration) }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_duration') border-red-400 @enderror">
                    @error('itunes_duration')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-400 list-disc list-outside pl-5">
                        <li>Use HH:MM:SS format — e.g. 01:15:32 or 00:42:07</li>
                        <li>Check the duration in your audio editor or media player if unsure.</li>
                    </ul>
                </div>

                <button type="submit"
                        class="rounded bg-yellow-600 px-6 py-2 text-sm font-semibold text-white hover:bg-yellow-700 transition-colors">
                    Confirm These Values &amp; Re-run Validation
                </button>

            </form>

        </div>

    @endif

    {{-- ================================================================ --}}
    {{-- WARNINGS                                                          --}}
    {{-- ================================================================ --}}
    @if ($result->hasWarnings())

        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Warnings</div>
        <div class="border border-yellow-300 rounded-lg px-6 py-4 mb-8">

            <ul class="space-y-3">
                @foreach ($result->warnings() as $warning)
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 text-yellow-500 flex-shrink-0">⚠</span>
                        <div>
                            <span class="text-sm font-mono text-gray-700">{{ $warning['field'] }}</span>
                            <span class="mx-2 text-gray-300">—</span>
                            <span class="text-sm text-gray-600">{{ $warning['message'] }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>

        </div>

    @endif

    {{-- ================================================================ --}}
    {{-- ACTIONS                                                           --}}
    {{-- ================================================================ --}}
    <div class="flex items-center gap-4 mt-2">

        {{-- Re-run validation after making fixes --}}
        @if ($result->failures())
            <a href="{{ route('post_production.generate_rss_feed.step2', $episode) }}"
               class="inline-block rounded bg-purple-700 px-6 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                Re-run Validation
            </a>
        @endif

        {{-- Proceed despite warnings only --}}
        @if ($result->ok() && ! $result->r2DownloadFailed() && $result->hasWarnings())
            <a href="{{ route('post_production.generate_rss_feed.step3', $episode) }}"
               class="inline-block rounded bg-purple-700 px-6 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                Proceed to Generation &rarr;
            </a>
        @endif

        {{-- Soft link to edit form — always visible --}}
        <a href="{{ route('podcast_episodes.edit', $episode) }}"
           target="_blank"
           class="text-sm text-purple-700 hover:underline">
            Edit Episode &nearr;
        </a>

    </div>

</x-layouts.app>