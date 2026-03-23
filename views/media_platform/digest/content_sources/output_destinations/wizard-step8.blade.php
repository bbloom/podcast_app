<x-layouts.app title="Add Output Destination — Step 8">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add an Output Destination</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 8</span>
            <span class="mx-2">—</span>
            <span>Confirm and save</span>
        </div>
    </div>

    <p class="text-sm text-gray-600 mb-6">Please review your destination details before saving.</p>

    <div class="border border-purple-500 rounded-lg p-6 mb-8">
        <table class="w-full text-sm text-gray-600 border-collapse">
            <tr>
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top font-semibold w-40">Name</td>
                <td class="py-2 text-gray-800 font-bold">{{ $data['name'] }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top font-semibold">Type</td>
                <td class="py-2 text-gray-800">SFTP</td>
            </tr>
            <tr>
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top font-semibold">Host</td>
                <td class="py-2 text-gray-800"><code>{{ $data['host'] }}</code></td>
            </tr>
            <tr>
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top font-semibold">Port</td>
                <td class="py-2 text-gray-800"><code>{{ $data['port'] }}</code></td>
            </tr>
            <tr>
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top font-semibold">Username</td>
                <td class="py-2 text-gray-800"><code>{{ $data['username'] }}</code></td>
            </tr>
            <tr>
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top font-semibold">Auth Type</td>
                <td class="py-2 text-gray-800">{{ $data['auth_type'] === 'ssh_key' ? 'SSH Key' : 'Password' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top font-semibold">Credentials</td>
                <td class="py-2 text-gray-500 italic">Stored securely — encrypted at rest</td>
            </tr>
            <tr>
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top font-semibold">Remote Path</td>
                <td class="py-2 text-gray-800"><code>{{ $data['path'] }}</code></td>
            </tr>
            @if (! empty($data['base_url']))
                <tr>
                    <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top font-semibold">Public URL</td>
                    <td class="py-2">
                        <a href="{{ $data['base_url'] }}" target="_blank" class="text-purple-700 hover:underline break-all">{{ $data['base_url'] }}</a>
                    </td>
                </tr>
            @endif
            <tr>
                <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top font-semibold">Connection Test</td>
                <td class="py-2 text-green-700 font-semibold">✓ Passed</td>
            </tr>
        </table>
    </div>

    <form method="POST" action="{{ route('output_destinations.create.step8.submit') }}">
        @csrf
        <div class="flex justify-between items-center">
            <a href="{{ route('output_destinations.create.step7') }}" class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Save Destination
            </button>
        </div>
    </form>

</x-layouts.app>