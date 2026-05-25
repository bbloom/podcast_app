<x-layouts.app title="{{ $hook->label }}">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <a href="{{ route('deploy_hooks.index') }}" class="hover:text-purple-700 transition">Deploy Hooks</a>
        <span>›</span>
        <span class="text-gray-700">{{ $hook->label }}</span>
    </div>

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">{{ $hook->label }}</h1>
        <a href="{{ route('deploy_hooks.edit', $hook) }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            Edit
        </a>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    @session('error')
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800">
            {{ $value }}
        </div>
    @endsession

    {{-- Details --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-8">
        <div class="px-4 py-3 bg-purple-50 border-b border-purple-300">
            <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider">Details</h2>
        </div>
        <div class="p-4">
            <table class="text-sm text-gray-600 w-full">

                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top w-40">Type</td>
                    <td class="py-1.5 text-gray-800">{{ $hook->triggerable_type_label }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">{{ $hook->triggerable_type_label }}</td>
                    <td class="py-1.5 text-gray-800">
                        <a href="{{ route($hook->triggerable_show_route, $hook->triggerable) }}"
                           class="hover:text-purple-700 transition">{{ $hook->triggerable_display_name }}</a>
                    </td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Label</td>
                    <td class="py-1.5 text-gray-800">{{ $hook->label }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Provider</td>
                    <td class="py-1.5 text-gray-800">{{ $hook->provider->label() }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Hook URL</td>
                    <td class="py-1.5 text-gray-400 text-xs italic">Stored encrypted — not displayable. Edit to replace.</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Status</td>
                    <td class="py-1.5">
                        @if ($hook->enabled)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Disabled</span>
                        @endif
                    </td>
                </tr>

                {{-- Trigger tracking --}}
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Last Triggered</td>
                    <td class="py-1.5 text-gray-800">
                        {{ $hook->last_triggered_at
                            ? $hook->last_triggered_at->format('d M Y H:i') . ' (' . $hook->last_triggered_at->diffForHumans() . ')'
                            : '—' }}
                    </td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Last Build ID</td>
                    <td class="py-1.5 text-gray-800 font-mono text-xs">
                        {{ $hook->last_build_id ?? '—' }}
                    </td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Last Trigger Status</td>
                    <td class="py-1.5">
                        @if ($hook->last_trigger_status === 'success')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Success</span>
                        @elseif ($hook->last_trigger_status === 'failed')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Failed</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Created</td>
                    <td class="py-1.5 text-gray-800">{{ $hook->created_at->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Updated</td>
                    <td class="py-1.5 text-gray-800">{{ $hook->updated_at->format('d M Y') }}</td>
                </tr>

            </table>
        </div>
    </div>

    {{-- Trigger Build --}}
    @if ($hook->enabled)
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Trigger Build</div>
        <div class="border border-purple-300 rounded-lg p-6 mb-8">
            <p class="text-sm text-gray-600 mb-4">
                Trigger a fresh static site build for
                <strong>{{ $hook->triggerable_display_name }}</strong>
                via {{ $hook->provider->label() }}.
            </p>
            <a href="{{ route('deploy_hooks.trigger.confirm', $hook) }}"
               class="inline-block bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Trigger Build
            </a>
        </div>
    @else
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Trigger Build</div>
        <div class="border border-gray-200 rounded-lg p-6 mb-8">
            <p class="text-sm text-gray-400">
                This hook is disabled and cannot be triggered. Enable it to trigger builds.
            </p>
        </div>
    @endif

    {{-- Build Status — Cloudflare Pages only, only when a build has been triggered --}}
    @if ($hook->provider->value === 'cloudflare_pages' && $hook->last_build_id)
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Build Status</div>
        <div class="border border-purple-300 rounded-lg p-6 mb-8"
             x-data="buildStatusChecker('{{ route('deploy_hooks.build_status', $hook) }}')">

            {{-- Initial state — not yet checking --}}
            <div x-show="! checking && ! succeeded && ! failed && ! apiError">
                <p class="text-sm text-gray-600 mb-1">
                    Check the current status of the last triggered Cloudflare Pages build.
                </p>
                <p class="text-xs text-gray-400 font-mono mb-4">{{ $hook->last_build_id }}</p>
                <button type="button"
                        @click="start()"
                        class="inline-block bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                    Check Build Status
                </button>
            </div>

            {{-- Checking — spinner with live stage info --}}
            <div x-show="checking" style="display: none;">
                <div class="flex items-center gap-3 mb-3">
                    <svg class="animate-spin h-5 w-5 text-purple-600 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    <span class="text-sm font-semibold text-gray-700">Checking build status…</span>
                </div>
                <p class="text-sm text-gray-500">
                    Stage: <span class="font-mono text-purple-700" x-text="stage ?? '—'"></span>
                    &nbsp;·&nbsp;
                    Status: <span class="font-mono text-purple-700" x-text="stageStatus ?? '—'"></span>
                </p>
            </div>

            {{-- Build succeeded --}}
            <div x-show="succeeded" style="display: none;">
                <div class="flex items-center gap-3 mb-3">
                    <svg class="h-5 w-5 text-green-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-sm font-semibold text-green-700">Build completed successfully.</span>
                </div>
                <button type="button"
                        @click="start()"
                        class="text-sm text-purple-700 hover:underline">
                    Check again
                </button>
            </div>

            {{-- Build failed --}}
            <div x-show="failed" style="display: none;">
                <div class="flex items-center gap-3 mb-3">
                    <svg class="h-5 w-5 text-red-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span class="text-sm font-semibold text-red-700">
                        Build failed at stage: <span class="font-mono" x-text="stage"></span>.
                    </span>
                </div>
                <button type="button"
                        @click="start()"
                        class="text-sm text-purple-700 hover:underline">
                    Check again
                </button>
            </div>

            {{-- API / network error --}}
            <div x-show="apiError" style="display: none;">
                <div class="flex items-start gap-3 mb-3">
                    <svg class="h-5 w-5 text-amber-500 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <span class="text-sm text-amber-700" x-text="apiError"></span>
                </div>
                <button type="button"
                        @click="start()"
                        class="text-sm text-purple-700 hover:underline">
                    Retry
                </button>
            </div>

        </div>
    @endif

    {{-- API Status --}}
    @if ($hook->enabled)
        <div class="border rounded-lg p-4 mb-8 {{ $apiStatus ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50' }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    @if ($apiStatus)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">● API Enabled</span>
                        <span class="text-sm text-green-700">Ready for static site builds.</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">● API Disabled</span>
                        <span class="text-sm text-red-700">Builds will fail — the API must be enabled first.</span>
                    @endif
                </div>
                @if (! $apiStatus)
                    <a href="{{ route('api_management.dashboard') }}"
                    class="text-sm font-semibold text-red-700 hover:text-red-900 hover:underline transition">
                        Enable API →
                    </a>
                @endif
            </div>
        </div>
    @endif

    {{-- Delete --}}
    <div class="mt-2 mb-6">
        <a href="{{ route('deploy_hooks.delete.confirm', $hook) }}"
           class="text-sm text-red-500 hover:text-red-700 font-medium transition">
            Delete this hook
        </a>
    </div>

    <div class="mt-6 text-sm">
        <a href="{{ route('deploy_hooks.index') }}" class="hover:text-purple-700 transition">← Deploy Hooks</a>
    </div>

@push('scripts')
<script>
function buildStatusChecker(statusUrl) {
    return {
        checking:     false,
        succeeded:    false,
        failed:       false,
        apiError:     null,
        stage:        null,
        stageStatus:  null,
        pollHandle:   null,

        // Begin polling immediately and every 5 seconds thereafter.
        start() {
            this.checking  = true;
            this.succeeded = false;
            this.failed    = false;
            this.apiError  = null;
            this.checkOnce();
            this.pollHandle = setInterval(() => this.checkOnce(), 5000);
        },

        // Stop polling and mark as no longer actively checking.
        stop() {
            clearInterval(this.pollHandle);
            this.checking = false;
        },

        async checkOnce() {
            try {
                const res = await fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' },
                });

                if (! res.ok) return;

                const data = await res.json();

                // API-level failure (wrong provider, no build ID, network error
                // caught server-side, Cloudflare auth failure, etc.).
                if (! data.api_call_succeeded) {
                    this.apiError = data.error_message;
                    this.stop();
                    return;
                }

                this.stage       = data.current_stage;
                this.stageStatus = data.current_stage_status;

                if (data.build_succeeded) {
                    this.succeeded = true;
                    this.stop();
                } else if (data.build_failed) {
                    this.failed = true;
                    this.stop();
                }
                // Otherwise still pending — keep polling.

            } catch (e) {
                // Client-side network error — silently continue polling.
            }
        },
    };
}
</script>
@endpush

</x-layouts.app>