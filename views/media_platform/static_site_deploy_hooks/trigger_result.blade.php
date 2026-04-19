<x-layouts.app title="Build Trigger Result — {{ $hook->label }}">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <a href="{{ route('deploy_hooks.index') }}" class="hover:text-purple-700 transition">Deploy Hooks</a>
        <span>›</span>
        <a href="{{ route('deploy_hooks.show', $hook) }}" class="hover:text-purple-700 transition">{{ $hook->label }}</a>
        <span>›</span>
        <span class="text-gray-700">Trigger Result</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-8">Build Trigger Result</h1>

    {{-- Summary banner --}}
    @if ($result->succeeded())
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800 font-medium">
            Build triggered successfully.
            @if ($result->alreadyExists())
                A build was already queued or initialising — no new build was created.
            @endif
        </div>
    @else
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800 font-medium">
            Build trigger failed. See details below.
        </div>
    @endif

    {{-- Result details --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-8">
        <div class="px-4 py-3 bg-purple-50 border-b border-purple-300">
            <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider">Details</h2>
        </div>
        <div class="p-4">
            <table class="text-sm text-gray-600 w-full">
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top w-40">Hook</td>
                    <td class="py-1.5 text-gray-800 font-medium">{{ $hook->label }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">{{ $hook->triggerable_type_label }}</td>
                    <td class="py-1.5 text-gray-800">{{ $hook->triggerable_display_name }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Provider</td>
                    <td class="py-1.5 text-gray-800">{{ $hook->provider->label() }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Status</td>
                    <td class="py-1.5">
                        @if ($result->succeeded())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800">Success</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">Failed</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">HTTP Status</td>
                    <td class="py-1.5 text-gray-800">
                        {{ $result->httpStatus() ?: 'No response' }}
                    </td>
                </tr>
                @if ($result->buildId())
                    <tr>
                        <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Build ID</td>
                        <td class="py-1.5 font-mono text-purple-700 text-xs">{{ $result->buildId() }}</td>
                    </tr>
                @endif
                @if ($result->errorMessage())
                    <tr>
                        <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Error</td>
                        <td class="py-1.5 text-red-600">{{ $result->errorMessage() }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Triggered At</td>
                    <td class="py-1.5 text-gray-800">
                        {{ $hook->fresh()->last_triggered_at?->format('d M Y H:i:s') ?? now()->format('d M Y H:i:s') }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Navigation --}}
    <div class="flex items-center gap-4 text-sm">
        <a href="{{ route('deploy_hooks.show', $hook) }}"
           class="bg-purple-700 hover:bg-purple-800 text-white font-semibold px-5 py-2.5 rounded-lg transition">
            Back to Hook
        </a>
        <a href="{{ route('deploy_hooks.trigger.confirm', $hook) }}"
           class="text-gray-500 hover:text-gray-700 transition">
            Trigger again
        </a>
    </div>

</x-layouts.app>