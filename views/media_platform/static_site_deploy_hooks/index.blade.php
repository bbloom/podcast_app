<x-layouts.app title="Deploy Hooks">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <span class="text-gray-700">Deploy Hooks</span>
    </div>

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Deploy Hooks</h1>
        <a href="{{ route('deploy_hooks.create') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            Add Hook
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

    @if ($hooks->isEmpty())
        <div class="bg-gray-50 border border-gray-200 rounded-lg px-5 py-8 text-center">
            <p class="text-sm text-gray-500">No deploy hooks yet.</p>
            <a href="{{ route('deploy_hooks.create') }}"
               class="mt-3 inline-block text-sm text-purple-700 hover:underline font-semibold">
                Add your first deploy hook →
            </a>
        </div>
    @else
        <div class="border border-purple-300 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-purple-50 border-b border-purple-300">
                        <th class="text-left font-semibold text-purple-700 px-4 py-3">Label</th>
                        <th class="text-left font-semibold text-purple-700 px-4 py-3">Show</th>
                        <th class="text-left font-semibold text-purple-700 px-4 py-3">Provider</th>
                        <th class="text-left font-semibold text-purple-700 px-4 py-3">Status</th>
                        <th class="text-left font-semibold text-purple-700 px-4 py-3">Last Triggered</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hooks as $hook)
                        <tr class="border-t border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-800 font-medium">
                                <a href="{{ route('deploy_hooks.show', $hook) }}"
                                   class="hover:text-purple-700 transition">
                                    {{ $hook->label }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $hook->triggerable->title }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $hook->provider->label() }}</td>
                            <td class="px-4 py-3">
                                @if ($hook->enabled)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Disabled</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                {{ $hook->last_triggered_at ? $hook->last_triggered_at->diffForHumans() : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('deploy_hooks.show', $hook) }}"
                                   class="text-xs text-purple-700 hover:underline font-semibold">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</x-layouts.app>