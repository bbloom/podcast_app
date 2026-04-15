<x-layouts.app title="Create API Client">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <a href="{{ route('api_management.dashboard') }}" class="hover:text-purple-700 transition">API Management</a>
        <span>›</span>
        <a href="{{ route('api_management.clients.index') }}" class="hover:text-purple-700 transition">Clients</a>
        <span>›</span>
        <span class="text-gray-700">Create</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-8">Create API Client</h1>

    <div class="border border-purple-500 rounded-lg p-6 mb-8">
        <form method="POST" action="{{ route('api_management.clients.store') }}">
            @csrf

            {{-- Label --}}
            <div class="mb-6">
                <label for="label" class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                <input type="text" id="label" name="label" value="{{ old('label') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                @error('label')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Domain --}}
            <div class="mb-6">
                <label for="domain" class="block text-sm font-medium text-gray-700 mb-1">Requesting Domain</label>
                <input type="text" id="domain" name="domain" value="{{ old('domain') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500">
                @error('domain')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Active --}}
            <div class="mb-8">
                <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="is_active" name="is_active"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="1" @selected(old('is_active', '1') == '1')>Active</option>
                    <option value="0" @selected(old('is_active', '1') == '0')>Inactive</option>
                </select>
                @error('is_active')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                    Create Client
                </button>
                <a href="{{ route('api_management.clients.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 transition">Cancel</a>
            </div>

        </form>
    </div>

</x-layouts.app>