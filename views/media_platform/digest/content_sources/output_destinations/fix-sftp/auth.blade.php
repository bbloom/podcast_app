<x-layouts.app title="Fix Connection — Authentication">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Fix Connection</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Authentication</span>
            <span class="mx-2">—</span>
            <span>Correct your credentials, then return to the connection test</span>
        </div>
    </div>

    <div class="bg-red-50 border border-red-300 rounded-lg p-4 mb-6">
        <p class="text-sm text-red-800 font-semibold">The connection test failed — authentication was rejected.</p>
        <p class="text-sm text-red-700 mt-1">Update your credentials below, save, and the test will run again automatically.</p>
    </div>

    <form method="POST" action="{{ route('output_destinations.fix.sftp.auth.submit') }}">
        @csrf

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-3">Authentication Type</label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="auth_type" value="password" id="auth_password"
                           class="w-4 h-4 accent-purple-700"
                           {{ old('auth_type', session('od_wizard.auth_type', 'password')) === 'password' ? 'checked' : '' }}
                           onchange="toggleAuthFields()">
                    <span class="text-sm text-gray-700">Password</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="auth_type" value="ssh_key" id="auth_ssh"
                           class="w-4 h-4 accent-purple-700"
                           {{ old('auth_type', session('od_wizard.auth_type')) === 'ssh_key' ? 'checked' : '' }}
                           onchange="toggleAuthFields()">
                    <span class="text-sm text-gray-700">SSH Key</span>
                </label>
            </div>
            @error('auth_type')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div id="password_fields" class="mb-6">
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                Password
                @if (session('od_wizard.auth_type') === 'password' && session('od_wizard.password'))
                    <span class="font-normal text-gray-400 text-xs ml-1">Leave blank to keep existing</span>
                @endif
            </label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="new-password"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('password') border-red-400 @enderror"
            >
            @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- SSH Key --}}
        <div id="ssh_key_fields" class="mb-6 hidden">
            <div class="mb-4">
                <label for="private_key" class="block text-sm font-semibold text-gray-700 mb-2">
                    Private Key
                    @if (session('od_wizard.auth_type') === 'ssh_key' && session('od_wizard.private_key'))
                        <span class="font-normal text-gray-400 text-xs ml-1">Leave blank to keep existing key</span>
                    @endif
                </label>
                <textarea
                    id="private_key"
                    name="private_key"
                    rows="8"
                    placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;...&#10;-----END OPENSSH PRIVATE KEY-----"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('private_key') border-red-400 @enderror"
                >{{ old('private_key') }}</textarea>
                @error('private_key')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-gray-500">Paste your entire private key including the BEGIN and END lines.</p>
            </div>

            <div>
                <label for="passphrase" class="block text-sm font-semibold text-gray-700 mb-2">
                    Passphrase <span class="font-normal text-gray-400">(optional)</span>
                </label>
                <input
                    type="password"
                    id="passphrase"
                    name="passphrase"
                    autocomplete="new-password"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                >
            </div>
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('output_destinations.create.step7') }}" class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to test
            </a>
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Save &amp; Retry Test →
            </button>
        </div>

    </form>

    @push('scripts')
    <script>
        function toggleAuthFields() {
            const isSshKey = document.getElementById('auth_ssh').checked;
            document.getElementById('password_fields').classList.toggle('hidden', isSshKey);
            document.getElementById('ssh_key_fields').classList.toggle('hidden', !isSshKey);
        }
        document.addEventListener('DOMContentLoaded', toggleAuthFields);
    </script>
    @endpush

</x-layouts.app>