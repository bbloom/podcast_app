<x-layouts.app title="Build Confirmation — {{ $episode->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Build Confirmation</h1>
        <a href="{{ route('post_production.trigger_builds.select', $episode->show) }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to Trigger Builds
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
            </table>
        </div>
    </div>

    {{-- ── Automated polling (Cloudflare Pages hook found) ─────────────────── --}}
    @if ($cloudflareHook)

        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Build Status</div>
        <div class="border border-purple-500 rounded-lg px-6 py-8 mb-8 text-center"
             x-data="buildConfirmationPoller(
                 '{{ route('deploy_hooks.build_status', $cloudflareHook) }}',
                 '{{ route('post_production.build_confirmation.confirm', $episode) }}'
             )"
             x-init="start()">

            {{-- Polling — spinner with live stage info --}}
            <div x-show="! complete && ! buildFailed && ! apiError">
                <div class="flex justify-center mb-4">
                    <svg class="animate-spin h-10 w-10 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                </div>
                <p class="text-lg font-semibold text-gray-700 mb-2">Waiting for the Cloudflare Pages build to complete…</p>
                <p class="text-sm text-gray-500 mb-3">This page checks automatically every few seconds.</p>
                <p class="text-sm text-gray-400">
                    Stage: <span class="font-mono text-purple-600" x-text="stage ?? '—'"></span>
                    &nbsp;·&nbsp;
                    Status: <span class="font-mono text-purple-600" x-text="stageStatus ?? '—'"></span>
                </p>
            </div>

            {{-- Build succeeded — show continue link --}}
            <div x-show="complete" style="display: none;">
                <div class="flex justify-center mb-4">
                    <svg class="h-10 w-10 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <p class="text-lg font-semibold text-green-700 mb-4">Build completed successfully!</p>
                <a :href="confirmUrl"
                   class="inline-block rounded bg-purple-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                    Continue to Generate RSS Feed →
                </a>
            </div>

            {{-- Build failed --}}
            <div x-show="buildFailed" style="display: none;">
                <div class="flex justify-center mb-4">
                    <svg class="h-10 w-10 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <p class="text-lg font-semibold text-red-700 mb-2">
                    Build failed at stage: <span class="font-mono" x-text="stage"></span>.
                </p>
                <p class="text-sm text-gray-500 mb-4">
                    Check your
                    <a href="https://dash.cloudflare.com" target="_blank" class="text-purple-700 hover:underline">Cloudflare dashboard</a>
                    for details, then re-trigger the build.
                </p>
                <a href="{{ route('post_production.trigger_builds.select', $episode->show) }}"
                   class="inline-block rounded border border-purple-700 px-5 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-50 transition-colors">
                    Back to Trigger Builds
                </a>
            </div>

            {{-- API / network error — polling could not reach Cloudflare --}}
            <div x-show="apiError" style="display: none;">
                <div class="flex justify-center mb-4">
                    <svg class="h-10 w-10 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <p class="text-lg font-semibold text-amber-700 mb-2">Could not reach the Cloudflare API.</p>
                <p class="text-sm text-gray-500 mb-1" x-text="apiError"></p>
                <p class="text-sm text-gray-500 mb-4">
                    Use the manual confirmation below once you have verified the build is complete.
                </p>
                <button type="button"
                        @click="start()"
                        class="text-sm text-purple-700 hover:underline">
                    Retry
                </button>
            </div>

        </div>

        {{-- Build info --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Build Details</div>
        <div class="border border-purple-300 rounded-lg px-6 py-4 mb-8">
            <table class="text-sm text-gray-600 w-full">
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap w-40">Hook</td>
                    <td class="py-1.5 text-gray-800">{{ $cloudflareHook->label }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap">Build ID</td>
                    <td class="py-1.5 text-gray-800 font-mono text-xs">{{ $cloudflareHook->last_build_id }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap">Triggered</td>
                    <td class="py-1.5 text-gray-800">
                        {{ $cloudflareHook->last_triggered_at?->format('d M Y H:i') }}
                        ({{ $cloudflareHook->last_triggered_at?->diffForHumans() }})
                    </td>
                </tr>
            </table>
        </div>

    @else

    {{-- ── No Cloudflare hook — manual confirmation only ───────────────────── --}}

        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Build Status</div>
        <div class="border border-purple-500 rounded-lg px-6 py-8 mb-8 text-center">
            <p class="text-lg font-semibold text-gray-700 mb-2">No Cloudflare Pages hook found for this show.</p>
            <p class="text-sm text-gray-500 mb-4">
                Check your hosting provider's dashboard to confirm the build is complete,
                then confirm manually below.
            </p>
        </div>

    @endif

    {{-- Manual confirmation — always available as a fallback --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">
        {{ $cloudflareHook ? 'Confirm Manually' : 'Confirm Build Complete' }}
    </div>
    <div class="border border-purple-500 rounded-lg px-6 py-6 mb-8">
        <p class="text-sm text-gray-600 mb-4">
            @if ($cloudflareHook)
                If the automated check is unavailable, you can confirm the build is complete
                after verifying it in the
                <a href="https://dash.cloudflare.com" target="_blank" class="text-purple-700 hover:underline">Cloudflare dashboard</a>.
            @else
                Once you have verified that your static site build has completed successfully,
                click below to continue to RSS feed generation.
            @endif
        </p>
        <a href="{{ route('post_production.build_confirmation.confirm', $episode) }}"
           class="inline-block rounded bg-purple-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
            Build is Complete — Continue to Generate RSS Feed →
        </a>
    </div>

@push('scripts')
<script>
function buildConfirmationPoller(statusUrl, confirmUrl) {
    return {
        complete:    false,
        buildFailed: false,
        apiError:    null,
        stage:       null,
        stageStatus: null,
        confirmUrl:  confirmUrl,
        pollHandle:  null,

        // Begin polling immediately — called via x-init="start()".
        start() {
            this.complete    = false;
            this.buildFailed = false;
            this.apiError    = null;
            this.checkOnce();
            this.pollHandle = setInterval(() => this.checkOnce(), 5000);
        },

        stop() {
            clearInterval(this.pollHandle);
        },

        async checkOnce() {
            try {
                const res = await fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' },
                });

                if (! res.ok) return;

                const data = await res.json();

                if (! data.api_call_succeeded) {
                    this.apiError = data.error_message;
                    this.stop();
                    return;
                }

                this.stage       = data.current_stage;
                this.stageStatus = data.current_stage_status;

                if (data.build_succeeded) {
                    this.complete = true;
                    this.stop();
                } else if (data.build_failed) {
                    this.buildFailed = true;
                    this.stop();
                }
                // Still pending — keep polling.

            } catch (e) {
                // Client-side network error — silently continue polling.
            }
        },
    };
}
</script>
@endpush

</x-layouts.app>