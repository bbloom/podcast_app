<x-layouts.app title="API Management">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <span class="text-gray-700">API Management</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-8">API Management</h1>

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

    {{-- ================================================================ --}}
    {{-- API STATUS                                                        --}}
    {{-- ================================================================ --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">API Status</div>
    <div class="border border-purple-500 rounded-lg p-6 mb-8">

        <div class="flex items-center justify-between">

            <div>
                @if ($control->is_enabled)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                        ● Enabled
                    </span>
                    @if ($control->enabled_at)
                        <p class="mt-2 text-xs text-gray-400">Enabled {{ $control->enabled_at->diffForHumans() }}</p>
                    @endif
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600">
                        ● Disabled
                    </span>
                    @if ($control->disabled_at)
                        <p class="mt-2 text-xs text-gray-400">Disabled {{ $control->disabled_at->diffForHumans() }}</p>
                    @endif
                @endif
            </div>

            <div class="flex gap-3">
                @if (! $control->is_enabled)
                    <form method="POST" action="{{ route('api_management.control.enable') }}">
                        @csrf
                        <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                            Enable API
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('api_management.control.disable') }}">
                        @csrf
                        <button type="submit"
                                class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                            Disable API
                        </button>
                    </form>
                @endif
            </div>

        </div>

        @if ($control->notes)
            <p class="mt-4 text-sm text-gray-500">{{ $control->notes }}</p>
        @endif

    </div>

    {{-- ================================================================ --}}
    {{-- API CLIENTS                                                       --}}
    {{-- ================================================================ --}}
    <div class="flex items-center justify-between mb-4">
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">API Clients</div>
        <a href="{{ route('api_management.clients.create') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + New Client
        </a>
    </div>

    @if ($clients->isEmpty())
        <div class="border border-gray-200 rounded-lg px-6 py-8 text-center text-sm text-gray-400">
            No API clients yet. Create one to get started.
        </div>
    @else
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Label</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Domain</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Last Used</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($clients as $client)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-purple-700">
                                <a href="{{ route('api_management.clients.show', $client) }}"
                                   class="hover:underline">{{ $client->label }}</a>
                            </td>
                            <td class="px-6 py-4 text-gray-600 font-mono text-xs">{{ $client->domain }}</td>
                            <td class="px-6 py-4">
                                @if ($client->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">
                                {{ $client->last_used_at ? $client->last_used_at->diffForHumans() : '—' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('api_management.clients.show', $client) }}"
                                   class="text-xs text-purple-700 hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</x-layouts.app>