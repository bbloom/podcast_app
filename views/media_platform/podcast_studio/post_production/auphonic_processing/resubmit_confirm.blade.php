<x-layouts.app title="Confirm Re-submission — {{ $episode->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Confirm Re-submission</h1>
        <a href="{{ $cancelRoute }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Cancel
        </a>
    </div>

    {{-- Warning banner --}}
    <div class="mb-8 rounded-lg border border-yellow-300 bg-yellow-50 px-6 py-4">
        <p class="text-lg font-semibold text-yellow-700">&#9888; This will delete the existing Auphonic production.</p>
        <p class="mt-1 text-sm text-yellow-600">
            A brand new production will be created and started immediately. This cannot be undone.
        </p>
    </div>

    {{-- Episode details --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Episode</div>
    <div class="border border-purple-500 rounded-lg pl-6 pr-4 py-4 mb-8">
        <div class="flex items-start gap-5">

            {{-- Show artwork --}}
            @if ($episode->show->itunes_image)
                <img src="{{ $episode->show->itunes_image }}"
                     alt="{{ $episode->show->title }}"
                     class="w-[75px] h-[75px] rounded-lg object-cover flex-shrink-0 mt-1">
            @endif

            {{-- Episode meta --}}
            <table class="text-base text-gray-600 border-collapse w-full">
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Show</td>
                    <td class="py-1 text-gray-800">{{ $episode->show->title }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Episode</td>
                    <td class="py-1 text-gray-800 font-medium">{{ $episode->title }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Scheduled Date</td>
                    <td class="py-1 text-gray-800">{{ $episode->scheduled_date?->format('M j, Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Auphonic Production</td>
                    <td class="py-1 text-gray-800 font-mono text-sm">{{ $episode->auphonic_production_uuid ?? '—' }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- What will happen --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">What will happen</div>
    <div class="border border-purple-500 rounded-lg px-6 py-6 mb-8">
        <table class="text-sm text-gray-600 border-collapse w-full">
            <tr class="border-b border-purple-100">
                <td class="pr-6 py-3 text-gray-500 whitespace-nowrap align-top w-48">Existing Production</td>
                <td class="py-3 text-gray-800">
                    The Auphonic production
                    <span class="font-mono">{{ $episode->auphonic_production_uuid ?? '—' }}</span>
                    will be deleted.
                </td>
            </tr>
            <tr class="border-b border-purple-100">
                <td class="pr-6 py-3 text-gray-500 whitespace-nowrap align-top">New Production</td>
                <td class="py-3 text-gray-800">
                    A new Auphonic production will be created and started immediately
                    using the same recording file and preset.
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-3 text-gray-500 whitespace-nowrap align-top">Episode Status</td>
                <td class="py-3 text-gray-800">
                    Will reset to
                    <span class="font-semibold">Processing at Auphonic</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- Confirmation form --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Confirm</div>
    <div class="border border-purple-500 rounded-lg px-6 py-6">

        <p class="text-sm text-gray-600 mb-6">
            Once confirmed, the existing Auphonic production will be permanently deleted
            and a new one will be submitted immediately.
        </p>

        <div class="flex items-center gap-4">
            <form method="POST" action="{{ route('post_production.auphonic_processing.resubmit', $episode) }}">
                @csrf
                <button type="submit"
                        class="rounded bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors">
                    Confirm Re-submission
                </button>
            </form>

            <a href="{{ $cancelRoute }}"
               class="text-sm text-purple-700 hover:underline">
                Cancel
            </a>
        </div>

    </div>

</x-layouts.app>