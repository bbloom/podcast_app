<x-layouts.app title="Submit to Auphonic — {{ $episode->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Submit to Auphonic</h1>
        <a href="{{ route('post_production.auphonic_processing.index') }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to Submit to Auphonic
        </a>
    </div>

    @session('error')
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $value }}
        </div>
    @endsession

    {{-- Episode details --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Episode</div>
    <div class="border border-purple-500 rounded-lg pl-6 pr-4 py-4 mb-8">
        <div class="flex items-start gap-5">

            {{-- Show artwork --}}
            @if ($episode->show->itunes_image)
                <img src="{{ $episode->show->itunes_image }}"
                     alt="{{ $episode->show->title }}"
                     class="w-[75px] h-[75px] rounded-lg object-cover flex-shrink-0 mt-1">
            @endif

            {{-- Episode meta --}}
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
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Recording File</td>
                    <td class="py-1 text-gray-800 font-mono text-sm">{{ $episode->raw_input_audio_filename ?? '—' }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- S3 Recording Check --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Recording File Check</div>
    <div class="border border-purple-500 rounded-lg px-6 py-6 mb-8">

        @if ($s3Status === 'match')

            {{-- ✓ File confirmed in S3 --}}
            <div class="flex items-center gap-3 text-green-700">
                <svg class="h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-sm font-semibold">The expected recording file is confirmed in S3.</span>
            </div>
            <p class="mt-2 text-xs text-gray-400 font-mono">{{ $filesInS3[0] }}</p>

        @elseif ($s3Status === 'empty')

            {{-- ✗ No files found --}}
            <div class="flex items-center gap-3 text-red-700 mb-3">
                <svg class="h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span class="text-sm font-semibold">No recording file was found in S3.</span>
            </div>
            <p class="text-sm text-gray-600 mb-1">Expected: <span class="font-mono">{{ $episode->raw_input_audio_filename }}</span></p>
            <p class="text-sm text-gray-500 mb-4">
                The upload may not have completed, or the file may have been deleted.
                Check the S3 bucket directly, then re-upload the recording.
            </p>
            <div class="flex items-center gap-4">
                <form method="POST" action="{{ route('post_production.auphonic_processing.replace_recording', $episode) }}">
                    @csrf
                    <button type="submit"
                            class="rounded bg-purple-700 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                        Re-upload Recording
                    </button>
                </form>
                <a href="{{ $consoleUrl }}" target="_blank"
                   class="text-sm text-purple-700 hover:underline">
                    Open S3 folder in AWS Console &#8599;
                </a>
            </div>

        @elseif ($s3Status === 'multiple')

            {{-- ✗ Multiple files found —ambiguous --}}
            <div class="flex items-center gap-3 text-red-700 mb-3">
                <svg class="h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span class="text-sm font-semibold">Multiple files were found in S3 — cannot confirm the correct recording.</span>
            </div>
            <p class="text-sm text-gray-600 mb-1">Expected: <span class="font-mono">{{ $episode->raw_input_audio_filename }}</span></p>
            <p class="text-sm text-gray-600 mb-1">Found:</p>
            <ul class="mb-4 ml-4 text-xs font-mono text-gray-500 list-disc list-outside pl-2">
                @foreach ($filesInS3 as $file)
                    <li>{{ $file }}</li>
                @endforeach
            </ul>
            <p class="text-sm text-gray-500 mb-4">
                Please delete the extra file(s) from S3, then return to this page.
            </p>
            <a href="{{ $consoleUrl }}" target="_blank"
               class="inline-block rounded border border-purple-700 px-5 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-50 transition-colors">
                Open S3 folder in AWS Console &#8599;
            </a>

        @elseif ($s3Status === 'mismatch')

            {{-- ✗ Wrong file found --}}
            <div class="flex items-center gap-3 text-red-700 mb-3">
                <svg class="h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span class="text-sm font-semibold">The file in S3 does not match the expected recording.</span>
            </div>
            <table class="text-sm text-gray-600 border-collapse w-full mb-4">
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-32">Expected</td>
                    <td class="py-1 font-mono text-gray-800">{{ $episode->raw_input_audio_filename }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap">Found</td>
                    <td class="py-1 font-mono text-red-600">{{ $filesInS3[0] }}</td>
                </tr>
            </table>
            <p class="text-sm text-gray-500 mb-4">
                Please delete the incorrect file from S3, then re-upload the correct recording.
            </p>
            <div class="flex items-center gap-4">
                <form method="POST" action="{{ route('post_production.auphonic_processing.replace_recording', $episode) }}">
                    @csrf
                    <button type="submit"
                            class="rounded bg-purple-700 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                        Re-upload Recording
                    </button>
                </form>
                <a href="{{ $consoleUrl }}" target="_blank"
                   class="text-sm text-purple-700 hover:underline">
                    Open S3 folder in AWS Console &#8599;
                </a>
            </div>

        @endif

    </div>

    {{-- Submit section — only shown when S3 check passes --}}
    @if ($s3Status === 'match')

        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Auphonic Processing</div>
        <div class="border border-purple-500 rounded-lg px-6 py-6 mb-8">

            <p class="text-sm text-gray-600 mb-6">
                Clicking <span class="font-semibold">Submit to Auphonic</span> will send this episode's
                recording to Auphonic for audio processing using the preset configured for
                <span class="font-semibold">{{ $episode->show->title }}</span>.
                The page will update automatically when Auphonic finishes.
            </p>

            <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-400 list-disc list-outside pl-5 mb-6">
                <li>The raw WAV recording will be fetched directly from S3 by Auphonic.</li>
                <li>Processing typically takes a few minutes depending on file length.</li>
                <li>Do not close this page while the submission is in progress.</li>
            </ul>

            <form method="POST" action="{{ route('post_production.auphonic_processing.submit', $episode) }}">
                @csrf
                <button type="submit"
                        class="rounded bg-purple-700 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                    Submit to Auphonic
                </button>
            </form>

        </div>

    @endif

</x-layouts.app>