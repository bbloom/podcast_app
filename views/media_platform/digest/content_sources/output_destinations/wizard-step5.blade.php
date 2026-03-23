<x-layouts.app title="Add Output Destination — Step 5">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add an Output Destination</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 5</span>
            <span class="mx-2">—</span>
            <span>Authentication</span>
        </div>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-semibold mb-1">Some servers require SSH key authentication</p>
                <p>Servers managed by <span class="font-semibold">Laravel Forge</span> and many other managed hosting providers disable password authentication entirely. If you are connecting to a Forge-managed server, select <span class="font-semibold">SSH Key</span> below.</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('output_destinations.create.step5.submit') }}">
        @csrf

        {{-- Auth type selector --}}
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

        {{-- Password field --}}
        <div id="password_fields" class="mb-6">
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
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

        {{-- SSH Key fields --}}
        <div id="ssh_key_fields" class="mb-6 hidden">

            {{-- Expandable SSH key instructions --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <button type="button" onclick="toggleSshInstructions()"
                        class="flex items-center justify-between w-full text-sm font-semibold text-blue-800">
                    <span>How do I get my SSH private key?</span>
                    <svg id="ssh_instructions_chevron" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div id="ssh_instructions" class="hidden mt-4 text-sm text-blue-900 space-y-5">

                    <div>
                        <p class="font-semibold text-blue-900 mb-2">If your server is managed by Laravel Forge</p>
                        <p class="text-xs text-blue-700 mb-3">Forge generates an SSH key pair for the <code>forge</code> user when it provisions the server. Use the existing private key.</p>
                        <p class="font-semibold text-xs mb-1">Step 1 — Get the private key:</p>
                        <code class="block bg-white border border-blue-200 rounded px-3 py-2 text-xs mb-1">cat ~/.ssh/id_rsa</code>
                        <p class="text-xs text-blue-700 mb-2">If that file doesn't exist, try: <code>cat ~/.ssh/id_ed25519</code></p>
                        <p class="font-semibold text-xs mb-1">Step 2 — Make sure the public key is in authorized_keys:</p>
                        <code class="block bg-white border border-blue-200 rounded px-3 py-2 text-xs mb-1">cat ~/.ssh/authorized_keys</code>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <p class="text-xl font-bold text-amber-900 mb-2">⚠ Still failing on a Forge server?</p>
                        <p class="text-sm text-purple-700 mb-3 border border-purple-700 rounded p-4">Forge creates an <code>id_rsa</code> key pair but does <u>not automatically add it to <code>authorized_keys</code></u>. Fix it with:</p>
                        <code class="block bg-white border border-amber-200 rounded px-3 py-2 text-xs mb-2">cat ~/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys</code>
                        <p class="text-xs text-amber-800">Verify: <code>tail -1 ~/.ssh/authorized_keys</code> — the last line should show the <code>id_rsa</code> public key.</p>
                    </div>

                    <div>
                        <p class="font-semibold text-blue-900 mb-2">If your server is a generic Linux server</p>
                        <p class="font-semibold text-xs mb-1">Step 1 — Generate a key pair:</p>
                        <code class="block bg-white border border-blue-200 rounded px-3 py-2 text-xs mb-1">ssh-keygen -t ed25519 -C "your-app-name"</code>
                        <p class="font-semibold text-xs mb-1">Step 2 — Copy the public key to your server:</p>
                        <code class="block bg-white border border-blue-200 rounded px-3 py-2 text-xs mb-1">ssh-copy-id -i ~/.ssh/id_ed25519.pub username@your-server.com</code>
                        <p class="font-semibold text-xs mb-1">Step 3 — Copy the private key to paste below:</p>
                        <code class="block bg-white border border-blue-200 rounded px-3 py-2 text-xs mb-1">cat ~/.ssh/id_ed25519</code>
                    </div>

                </div>
            </div>

            <div class="mb-4">
                <label for="private_key" class="block text-sm font-semibold text-gray-700 mb-2">Private Key</label>
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
                <p class="mt-2 text-xs text-gray-500">Only required if your SSH key was created with a passphrase.</p>
            </div>

        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('output_destinations.create.step4') }}" class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Next Step →
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

        function toggleSshInstructions() {
            document.getElementById('ssh_instructions').classList.toggle('hidden');
            document.getElementById('ssh_instructions_chevron').classList.toggle('rotate-180');
        }

        document.addEventListener('DOMContentLoaded', toggleAuthFields);
    </script>
    @endpush

</x-layouts.app>