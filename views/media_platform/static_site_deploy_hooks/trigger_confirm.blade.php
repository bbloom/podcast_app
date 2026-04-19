<x-layouts.app title="Trigger Build — {{ $hook->label }}">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <a href="{{ route('deploy_hooks.index') }}" class="hover:text-purple-700 transition">Deploy Hooks</a>
        <span>›</span>
        <a href="{{ route('deploy_hooks.show', $hook) }}" class="hover:text-purple-700 transition">{{ $hook->label }}</a>
        <span>›</span>
        <span class="text-gray-700">Trigger Build</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-8">Trigger Build</h1>

    {{-- Hook details --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-3 bg-purple-50 border-b border-purple-300">
            <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider">Hook</h2>
        </div>
        <div class="p-4">
            <table class="text-sm text-gray-600 w-full">
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top w-40">Label</td>
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
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Last Triggered</td>
                    <td class="py-1.5 text-gray-800">
                        {{ $hook->last_triggered_at
                            ? $hook->last_triggered_at->format('d M Y H:i') . ' (' . $hook->last_triggered_at->diffForHumans() . ')'
                            : '—' }}
                    </td>
                </tr>
                @if ($hook->last_trigger_status === 'failed')
                    <tr>
                        <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Last Status</td>
                        <td class="py-1.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">
                                Last trigger failed
                            </span>
                        </td>
                    </tr>
                @endif
            </table>
        </div>
    </div>

    {{-- Confirm form --}}
    <form method="POST" action="{{ route('deploy_hooks.trigger.execute', $hook) }}">
        @csrf
        <div class="flex items-center gap-4">
            <button type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                Yes, Trigger Build
            </button>
            <a href="{{ route('deploy_hooks.show', $hook) }}"
               class="text-sm text-gray-500 hover:text-gray-700 transition">Cancel</a>
        </div>
    </form>

</x-layouts.app>