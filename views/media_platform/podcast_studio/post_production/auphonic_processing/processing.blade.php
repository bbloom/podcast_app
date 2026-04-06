<x-layouts.app title="Processing at Auphonic — {{ $episode->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Processing at Auphonic</h1>
        <a href="{{ route('post_production.auphonic_processing.index') }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to Submit to Auphonic
        </a>
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

    {{-- Processing status — Alpine.js polls the webhook-status endpoint every 5 seconds.
         When the episode status advances to `auphonic_complete`, the spinner is hidden
         and the "Ready!" button is shown. --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Status</div>
    <div class="border border-purple-500 rounded-lg px-6 py-8 mb-8 text-center"
         x-data="auphonicPoller('{{ route('post_production.auphonic_processing.webhook_status', $episode) }}', '{{ route('post_production.auphonic_processing.complete', $episode) }}')"
         x-init="startPolling()">

        {{-- Spinner — shown while processing --}}
        <div x-show="! complete">
            <div class="flex justify-center mb-4">
                <svg class="animate-spin h-10 w-10 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
            </div>
            <p class="text-lg font-semibold text-gray-700 mb-2">Auphonic is processing your episode…</p>
            <p class="text-sm text-gray-500">
                This page checks automatically every few seconds. You can also monitor progress in the
                <a href="https://auphonic.com/engine/status/{{ $episode->auphonic_production_uuid }}/"
                   target="_blank"
                   class="text-purple-700 hover:underline">Auphonic console</a>.
            </p>
        </div>

        {{-- Ready banner — shown when auphonic_complete --}}
        <div x-show="complete" style="display: none;">
            <div class="flex justify-center mb-4">
                <svg class="h-10 w-10 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p class="text-lg font-semibold text-green-700 mb-4">Auphonic has finished processing!</p>
            <a :href="completeUrl"
               class="inline-block rounded bg-purple-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                Ready! Click here to continue…
            </a>
        </div>

    </div>

    {{-- Re-submit option --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Something wrong?</div>
    <div class="border border-purple-500 rounded-lg px-6 py-6">

        <p class="text-sm text-gray-600 mb-4">
            If Auphonic reported an error, or if you need to start over with a fresh production,
            you can re-submit below. This will delete the current Auphonic production and create a new one.
        </p>

        <a href="{{ route('post_production.auphonic_processing.resubmit_confirm', $episode) }}"
           class="inline-block rounded border border-purple-700 px-5 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-50 transition-colors">
            Re-submit to Auphonic
        </a>

    </div>

@push('scripts')
<script>
function auphonicPoller(statusUrl, completeUrl) {
    return {
        complete:    false,
        completeUrl: completeUrl,
        pollHandle:  null,

        // Begin polling the webhook-status endpoint every 5 seconds.
        startPolling() {
            this.pollHandle = setInterval(() => this.checkStatus(), 5000);
        },

        // Fetch the current episode status from the server.
        async checkStatus() {
            try {
                const response = await fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' },
                });

                if (! response.ok) return;

                const data = await response.json();

                if (data.complete) {
                    // Stop polling and show the "Ready!" button.
                    clearInterval(this.pollHandle);
                    this.complete = true;
                }

            } catch (e) {
                // Network error — silently continue polling.
            }
        },
    };
}
</script>
@endpush

</x-layouts.app>