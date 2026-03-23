<x-layouts.app title="Add Output Destination — Step 7">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add an Output Destination</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 7</span>
            <span class="mx-2">—</span>
            <span>Test the connection</span>
        </div>
    </div>

    <p class="text-sm text-gray-600 mb-6">
        Click the button below to test your SFTP connection using the details you provided.
        You must pass this test before you can proceed.
    </p>

    {{-- Test result panel (hidden until test runs) --}}
    <div id="test_result" class="hidden mb-4 rounded-lg p-4 border">
        <div class="flex gap-3 items-start">
            <div id="test_icon" class="flex-shrink-0 mt-0.5"></div>
            <div class="flex-1">
                <p id="test_message" class="text-sm font-semibold"></p>
                {{-- Fix links — shown dynamically by JS based on error_step --}}
                <div id="fix_host" class="hidden mt-3">
                    <a href="{{ route('output_destinations.fix.sftp.host') }}" class="text-sm font-semibold text-purple-700 hover:underline">← Fix host &amp; port</a>
                </div>
                <div id="fix_username" class="hidden mt-3">
                    <a href="{{ route('output_destinations.fix.sftp.username') }}" class="text-sm font-semibold text-purple-700 hover:underline">← Fix username</a>
                </div>
                <div id="fix_auth" class="hidden mt-3">
                    <a href="{{ route('output_destinations.fix.sftp.auth') }}" class="text-sm font-semibold text-purple-700 hover:underline">← Fix authentication</a>
                </div>
                <div id="fix_path" class="hidden mt-3">
                    <a href="{{ route('output_destinations.fix.sftp.path') }}" class="text-sm font-semibold text-purple-700 hover:underline">← Fix remote path</a>
                </div>
                <div id="fix_all" class="hidden mt-3 flex flex-col gap-1">
                    <a href="{{ route('output_destinations.fix.sftp.host') }}" class="text-sm font-semibold text-purple-700 hover:underline">← Edit host &amp; port</a>
                    <a href="{{ route('output_destinations.fix.sftp.username') }}" class="text-sm font-semibold text-purple-700 hover:underline">← Edit username</a>
                    <a href="{{ route('output_destinations.fix.sftp.auth') }}" class="text-sm font-semibold text-purple-700 hover:underline">← Edit authentication</a>
                    <a href="{{ route('output_destinations.fix.sftp.path') }}" class="text-sm font-semibold text-purple-700 hover:underline">← Edit remote path</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Debug panel (shown on failure) --}}
    <div id="debug_panel" class="hidden mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">What the app is sending</p>
        <table class="border-collapse">
            <tbody id="debug_body"></tbody>
        </table>
        <p class="mt-3 text-xs text-gray-400">If anything looks wrong, use the fix links above to correct it.</p>
    </div>

    @error('test')
        <div class="bg-red-50 border border-red-300 rounded-lg p-4 mb-6">
            <p class="text-sm text-red-600 font-semibold">{{ $message }}</p>
        </div>
    @enderror

    <div class="mb-8">
        <button type="button" id="test_button" onclick="testConnection()"
                class="bg-white border border-purple-700 text-purple-700 hover:bg-purple-50 text-sm font-semibold px-6 py-3 rounded-lg transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Test Connection
        </button>
    </div>

    <form method="POST" action="{{ route('output_destinations.create.step7.submit') }}">
        @csrf
        <div class="flex justify-between items-center">
            <a href="{{ route('output_destinations.create.step6') }}" class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
            <button type="submit" id="next_button" disabled
                    class="bg-purple-700 text-white text-sm font-semibold px-6 py-3 rounded-lg transition opacity-40 cursor-not-allowed">
                Next Step →
            </button>
        </div>
    </form>

    @push('scripts')
    <script>
        const fixIds = ['fix_host', 'fix_username', 'fix_auth', 'fix_path', 'fix_all'];

        function hideAllFixLinks() {
            fixIds.forEach(id => document.getElementById(id).classList.add('hidden'));
        }

        async function testConnection() {
            const button    = document.getElementById('test_button');
            const next      = document.getElementById('next_button');
            const result    = document.getElementById('test_result');
            const message   = document.getElementById('test_message');
            const icon      = document.getElementById('test_icon');
            const debug     = document.getElementById('debug_panel');
            const debugBody = document.getElementById('debug_body');

            button.disabled = true;
            button.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Testing...';

            try {
                const response = await fetch('{{ route('output_destinations.wizard.test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({}),
                });

                const data = await response.json();

                result.classList.remove('hidden');
                hideAllFixLinks();
                debug.classList.add('hidden');

                if (data.success) {
                    result.className  = 'mb-4 rounded-lg p-4 border bg-green-50 border-green-300';
                    icon.innerHTML    = '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                    message.className = 'text-sm font-semibold text-green-800';
                    message.textContent = 'Connection successful! You may proceed.';
                    next.disabled = false;
                    next.classList.remove('opacity-40', 'cursor-not-allowed');
                } else {
                    result.className  = 'mb-4 rounded-lg p-4 border bg-red-50 border-red-300';
                    icon.innerHTML    = '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
                    message.className = 'text-sm font-semibold text-red-800';
                    message.textContent = data.message || 'Connection failed. Please check your details.';

                    if (data.debug) {
                        const d = data.debug;
                        debugBody.innerHTML =
                            `<tr><td class="pr-6 py-1 text-gray-500 text-xs">Host</td><td class="py-1 text-xs font-mono font-semibold text-gray-800">${d.host || '—'}</td></tr>` +
                            `<tr><td class="pr-6 py-1 text-gray-500 text-xs">Port</td><td class="py-1 text-xs font-mono font-semibold text-gray-800">${d.port || '—'}</td></tr>` +
                            `<tr><td class="pr-6 py-1 text-gray-500 text-xs">Username</td><td class="py-1 text-xs font-mono font-semibold text-gray-800">${d.username || '—'}</td></tr>` +
                            `<tr><td class="pr-6 py-1 text-gray-500 text-xs">Path</td><td class="py-1 text-xs font-mono font-semibold text-gray-800">${d.path || '—'}</td></tr>` +
                            `<tr><td class="pr-6 py-1 text-gray-500 text-xs">Auth type</td><td class="py-1 text-xs font-mono font-semibold text-gray-800">${d.auth_type || '—'}</td></tr>`;
                        debug.classList.remove('hidden');
                    }

                    // Show the specific fix link based on error_step from SftpService
                    const errorStep = data.error_step;
                    if      (errorStep === 3)      document.getElementById('fix_host').classList.remove('hidden');
                    else if (errorStep === 5)    { document.getElementById('fix_username').classList.remove('hidden');
                                                   document.getElementById('fix_auth').classList.remove('hidden'); }
                    else if (errorStep === 'path') document.getElementById('fix_path').classList.remove('hidden');
                    else                           document.getElementById('fix_all').classList.remove('hidden');

                    next.disabled = true;
                    next.classList.add('opacity-40', 'cursor-not-allowed');
                }

            } catch (e) {
                result.classList.remove('hidden');
                hideAllFixLinks();
                result.className  = 'mb-4 rounded-lg p-4 border bg-red-50 border-red-300';
                icon.innerHTML    = '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
                message.className = 'text-sm font-semibold text-red-800';
                message.textContent = 'An unexpected error occurred. Please try again.';
                document.getElementById('fix_all').classList.remove('hidden');
            }

            button.disabled = false;
            button.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Test Again';
        }
    </script>
    @endpush

</x-layouts.app>