<x-layouts.app title="Edit Output Destination">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Edit Output Destination</h1>
        <p class="text-sm text-gray-500 mt-1">Editing: <span class="font-semibold text-gray-700">{{ $outputDestination->name }}</span></p>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6">
            <p class="text-sm text-green-700 font-semibold">{{ session('success') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('output_destinations.update', $outputDestination) }}">
        @csrf
        @method('PUT')

        {{-- Name --}}
        <div class="mb-6">
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name', $outputDestination->name) }}"
                required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('name') border-red-400 @enderror"
            >
            @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Host --}}
        <div class="mb-6">
            <label for="host" class="block text-sm font-semibold text-gray-700 mb-2">Host</label>
            <input
                type="text"
                id="host"
                name="host"
                value="{{ old('host', $outputDestination->host) }}"
                required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('host') border-red-400 @enderror"
            >
            @error('host') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Port --}}
        {{-- Username --}}
        {{-- Auth type --}}
        {{-- Password fields --}}
        {{-- SSH key fields --}}
        {{-- Remote path --}}
        {{-- ... all existing SFTP fields unchanged ... --}}

        {{-- Public URL --}}
        <div class="mb-6">
            <label for="base_url" class="block text-sm font-semibold text-gray-700 mb-2">
                Public URL <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input
                type="url"
                id="base_url"
                name="base_url"
                value="{{ old('base_url', $outputDestination->base_url) }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('base_url') border-red-400 @enderror"
            >
            @error('base_url') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Enabled --}}
        <div class="mb-8">
            <label class="flex items-center gap-3 cursor-pointer">
                <input
                    type="checkbox"
                    name="enabled"
                    value="1"
                    class="w-4 h-4 accent-purple-700"
                    {{ old('enabled', $outputDestination->enabled) ? 'checked' : '' }}
                >
                <span class="text-sm font-semibold text-gray-700">Enabled</span>
            </label>
            <p class="mt-1 text-xs text-gray-500 pl-7">Disabled destinations cannot be used by lists.</p>
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('output_destinations.index') }}"
               class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Cancel
            </a>
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Save Changes
            </button>
        </div>

    </form>

    @push('scripts')
    <script>
        function toggleAuthFields() {
            const isSshKey = document.getElementById('auth_ssh').checked;
            document.getElementById('password_fields').classList.toggle('hidden', isSshKey);
            document.getElementById('ssh_key_fields').classList.toggle('hidden', ! isSshKey);
        }

        function toggleSshInstructions() {
            const panel   = document.getElementById('ssh_instructions');
            const chevron = document.getElementById('ssh_instructions_chevron');
            panel.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        }

        document.addEventListener('DOMContentLoaded', toggleAuthFields);
    </script>
    @endpush

</x-layouts.app>