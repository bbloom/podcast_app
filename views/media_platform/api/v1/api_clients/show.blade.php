<x-layouts.app title="{{ $client->label }}">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <a href="{{ route('api_management.dashboard') }}" class="hover:text-purple-700 transition">API Management</a>
        <span>›</span>
        <a href="{{ route('api_management.clients.index') }}" class="hover:text-purple-700 transition">Clients</a>
        <span>›</span>
        <span class="text-gray-700">{{ $client->label }}</span>
    </div>

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">{{ $client->label }}</h1>
        <a href="{{ route('api_management.clients.edit', $client) }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            Edit
        </a>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    {{-- Token flash — shown once only after create or rotate --}}
    @session('token')
        <div class="bg-amber-50 border border-amber-400 rounded-lg px-5 py-4 mb-6">
            <p class="text-sm font-semibold text-amber-800 mb-2">⚠ Copy this token now — it will never be shown again.</p>
            <p class="font-mono text-sm text-amber-900 break-all bg-amber-100 rounded px-3 py-2 select-all">{{ $value }}</p>
            <p class="mt-2 text-xs text-amber-700">Paste this into your Cloudflare EmDash environment secrets as the <code>Authorization</code> bearer token.</p>
        </div>
    @endsession

    {{-- Client details --}}
    <div class="border border-purple-500 rounded-lg p-6 mb-8">
        <table class="text-sm text-gray-600 border-collapse w-full">

            {{-- Details --}}
            <tr><td colspan="2" class="pt-2 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Details</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Label</td>
                <td class="py-1 text-gray-800">{{ $client->label }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Requesting Domain</td>
                <td class="py-1 text-gray-800 font-mono text-xs">{{ $client->domain }}</td>
            </tr>

            {{-- Status --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Status</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Active</td>
                <td class="py-1">
                    @if ($client->is_active)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Last Used</td>
                <td class="py-1 text-gray-800">
                    {{ $client->last_used_at ? $client->last_used_at->format('d M Y H:i') . ' (' . $client->last_used_at->diffForHumans() . ')' : '—' }}
                </td>
            </tr>

            {{-- Token --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Bearer Token</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Token</td>
                <td class="py-1 text-gray-400 text-xs italic">Stored as a one-way hash — not retrievable. Rotate to generate a new one.</td>
            </tr>

            {{-- Record --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Record</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Created</td>
                <td class="py-1 text-gray-800">{{ $client->created_at->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Updated</td>
                <td class="py-1 text-gray-800">{{ $client->updated_at->format('d M Y') }}</td>
            </tr>

        </table>
    </div>

    {{-- Rotate token --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Rotate Bearer Token</div>
    <div class="border border-purple-500 rounded-lg p-6 mb-8">
        <p class="text-sm text-gray-600 mb-4">
            Rotating the token immediately invalidates the current token. A new token will be shown once — copy it immediately into your environment secrets.
        </p>
        <form method="POST" action="{{ route('api_management.clients.rotate_token', $client) }}">
            @csrf
            <button type="submit"
                    class="bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Rotate Token
            </button>
        </form>
    </div>

    <div class="mt-2 mb-6">
        <a href="{{ route('api_management.clients.delete.confirm', $client) }}"
           class="text-sm text-red-500 hover:text-red-700 font-medium transition">
            Delete this client
        </a>
    </div>

    <div class="mt-6 text-sm">
        <a href="{{ route('api_management.clients.index') }}" class="hover:text-purple-700 transition">← Clients</a>
    </div>

</x-layouts.app>