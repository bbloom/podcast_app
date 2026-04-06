<x-layouts.app title="Auphonic Complete — {{ $episode->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Auphonic Complete</h1>
        <a href="{{ route('post_production.auphonic_processing.index') }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to Submit to Auphonic
        </a>
    </div>

    {{-- Success banner --}}
    <div class="mb-8 rounded-lg border border-green-300 bg-green-50 px-6 py-4">
        <p class="text-lg font-semibold text-green-700">&#10003; Auphonic has finished processing this episode.</p>
        <p class="mt-1 text-sm text-green-600">
            The processed MP3 is ready in your Auphonic account. Choose how you would like to proceed below.
        </p>
    </div>

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
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Auphonic Production</td>
                    <td class="py-1 text-gray-800 font-mono text-sm">{{ $episode->auphonic_production_uuid }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Choice: Review or Proceed --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">What would you like to do?</div>
    <div class="border border-purple-500 rounded-lg px-6 py-6 mb-8 space-y-6">

        {{-- Option 1: Review in Auphonic console --}}
        <div>
            <p class="text-sm font-semibold text-gray-700 mb-1">Review the MP3 in Auphonic</p>
            <p class="text-sm text-gray-500 mb-3">
                Listen to the processed audio in the Auphonic console before proceeding.
                Return to this page when you are ready to continue.
            </p>
            <a href="{{ $consoleUrl }}"
               target="_blank"
               class="inline-block rounded border border-purple-700 px-5 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-50 transition-colors">
                Open in Auphonic Console &#8599;
            </a>
        </div>

        <hr class="border-purple-100">

        {{-- Option 2: Proceed to Clean Up --}}
        <div>
            <p class="text-sm font-semibold text-gray-700 mb-1">Proceed to Clean Up</p>
            <p class="text-sm text-gray-500 mb-3">
                Delete the work-in-progress S3 recording and the Auphonic production,
                then advance this episode to the next pipeline step.
            </p>
            <a href="{{ route('post_production.auphonic_processing.cleanup_confirm', $episode) }}"
               class="inline-block rounded bg-purple-700 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                Proceed to Clean Up
            </a>
        </div>

    </div>

    
    {{-- Option 3: Re-submit to Auphonic --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Not happy with the result?</div>
    <div class="border border-purple-500 rounded-lg px-6 py-6">

        <p class="text-sm text-gray-600 mb-4">
            If the processed MP3 is not acceptable — wrong output, bad processing, or any other issue —
            you can delete this Auphonic production and start a brand new one.
        </p>

        <a href="{{ route('post_production.auphonic_processing.resubmit_confirm', $episode) }}"
           class="inline-block rounded border border-red-400 px-5 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 transition-colors">
            Re-submit to Auphonic
        </a>

    </div>

</x-layouts.app>