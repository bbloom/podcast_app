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

</x-layouts.app>