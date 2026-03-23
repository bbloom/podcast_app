<x-layouts.app title="Add Youtube Channel Wizard">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add a Youtube Channel</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 2</span>
            <span>of 5</span>
            <span class="mx-2">—</span>

            @if (count($results) == 1)
                <span>Is this the Youtube channel you want to add?</span>
            @else
                <span>Please select the Youtube channel that you want to add</span>
            @endif
        </div>

        {{-- Step dots --}}
        <div class="flex items-center gap-2 mt-3">
            <div class="w-3 h-3 rounded-full bg-purple-300"></div>
            <div class="w-16 h-px bg-purple-300"></div>
            <div class="w-3 h-3 rounded-full bg-purple-700"></div>
            <div class="w-16 h-px bg-gray-300"></div>
            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
            <div class="w-16 h-px bg-gray-300"></div>
            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
            <div class="w-16 h-px bg-gray-300"></div>
            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
        </div>
    </div>

    <form method="POST" action="{{ route('youtube.channels.create.step2.submit') }}">
        @csrf

        @error('channel_id')
            <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6">
                <p class="text-red-600 text-sm font-semibold">{{ $message }}</p>
            </div>
        @enderror

        <div class="space-y-4">
            @foreach ($results as $channel)
                @php $alreadyAdded = $channel['already_added'] ?? false; @endphp

                <label class="flex items-center gap-4 {{ $alreadyAdded ? 'cursor-not-allowed opacity-60' : 'cursor-pointer' }}">

                    {{-- Radio button --}}
                    @if (count($results) > 1)
                        <div class="flex-shrink-0">
                            <input
                                type="radio"
                                name="channel_id"
                                value="{{ $channel['channel_id'] }}"
                                {{ $alreadyAdded ? 'disabled' : '' }}
                                class="w-5 h-5 accent-purple-700 {{ $alreadyAdded ? 'cursor-not-allowed' : 'cursor-pointer' }}"
                            >
                        </div>
                    @else
                        <input
                            type="radio"
                            name="channel_id"
                            value="{{ $channel['channel_id'] }}"
                            {{ $alreadyAdded ? 'disabled' : 'checked' }}
                            class="sr-only"
                        >
                    @endif

                    {{-- Card --}}
                    <div class="flex-1 border {{ $alreadyAdded ? 'border-gray-200 bg-gray-50' : 'border-purple-500 hover:border-purple-700' }} rounded-lg p-4 transition">
                        <div class="flex gap-4">

                            {{-- Thumbnail --}}
                            <div class="flex-shrink-0">
                                <img
                                    src="{{ $channel['thumbnail'] }}"
                                    alt="{{ $channel['title'] }}"
                                    class="w-20 h-20 rounded-full object-cover border border-gray-200 {{ $alreadyAdded ? 'grayscale' : '' }}"
                                >
                            </div>

                            {{-- Channel details --}}
                            <div class="flex-1 min-w-0">

                                <div class="flex items-center gap-2 mb-1">
                                    <p class="text-xl font-bold {{ $alreadyAdded ? 'text-gray-400' : 'text-gray-800' }}">
                                        {{ $channel['title'] }}
                                    </p>
                                    @if ($alreadyAdded)
                                        <span class="text-xs bg-gray-200 text-gray-500 px-2 py-0.5 rounded-full font-medium flex-shrink-0">
                                            Already added
                                        </span>
                                    @endif
                                </div>

                                @if ($channel['description'])
                                    <p class="mt-1 text-lg {{ $alreadyAdded ? 'text-gray-400' : 'text-gray-900' }} line-clamp-2">
                                        {{ $channel['description'] }}
                                    </p>
                                @endif

                                <table class="mt-6 text-lg text-gray-600 border-collapse">
                                    <tr>
                                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Handle</td>
                                        <td class="py-1 font-bold text-xl {{ $alreadyAdded ? 'text-gray-400' : 'text-gray-800' }}">
                                            {{ $channel['handle'] ?? '—' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Channel ID</td>
                                        <td class="py-1"><code class="font-bold">{{ $channel['channel_id'] }}</code></td>
                                    </tr>
                                    <tr>
                                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">URL</td>
                                        <td class="py-1">
                                            <a href="{{ $channel['channel_url'] }}" target="_blank" class="text-purple-700 hover:underline break-all">
                                                {{ $channel['channel_url'] }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                            </div>
                        </div>
                    </div>

                </label>

            @endforeach
        </div>

        {{-- Actions --}}
        <div class="flex justify-between items-center mt-8">
            <a href="{{ route('youtube.channels.create.step1') }}"
               class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Start over
            </a>

            @php $allAlreadyAdded = collect($results)->every(fn($c) => $c['already_added'] ?? false); @endphp

            @if ($allAlreadyAdded)
                <p class="text-sm text-gray-500">You have already added all of these channels.</p>
            @else
                <button
                    type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
                >
                    I have selected a YouTube channel.<br>Now, on to the next step...
                </button>
            @endif
        </div>

    </form>

</x-layouts.app>