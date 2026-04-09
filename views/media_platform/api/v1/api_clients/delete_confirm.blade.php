<x-layouts.app title="Delete {{ $client->label }}">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <a href="{{ route('api_management.dashboard') }}" class="hover:text-purple-700 transition">API Management</a>
        <span>›</span>
        <a href="{{ route('api_management.clients.index') }}" class="hover:text-purple-700 transition">Clients</a>
        <span>›</span>
        <a href="{{ route('api_management.clients.show', $client) }}" class="hover:text-purple-700 transition">{{ $client->label }}</a>
        <span>›</span>
        <span class="text-gray-700">Delete</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-8">Delete Client</h1>

    <div class="border border-red-300 rounded-lg p-6 mb-8 bg-red-50">
        <p class="text-sm text-red-800 font-semibold mb-2">
            Are you sure you want to delete <strong>{{ $client->label }}</strong>?
        </p>
        <p class="text-sm text-red-700">
            This will permanently delete the client and invalidate its bearer token.
            Any site this token will immediately start receiving 403 responses.
            This action cannot be undone.
        </p>
    </div>

    <div class="flex items-center gap-4">
        <form method="POST" action="{{ route('api_management.clients.destroy', $client) }}">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                Yes, Delete Client
            </button>
        </form>
        <a href="{{ route('api_management.clients.show', $client) }}"
           class="text-sm text-gray-500 hover:text-gray-700 transition">Cancel</a>
    </div>

</x-layouts.app>