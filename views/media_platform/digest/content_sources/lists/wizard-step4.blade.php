<x-layouts.app title="Create a List">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Create a List</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 4</span>
            <span>of 6</span>
            <span class="mx-2">—</span>
            <span>Choose an output destination</span>
        </div>
        @include('media_platform.digest.content_sources.lists._step_dots', ['current' => 4])
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800 font-medium">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('lists.create.step4.submit') }}">
        @csrf

        @error('output_destination_id')
            <p class="mb-4 text-sm text-red-600">{{ $message }}</p>
        @enderror

        @if ($destinations->isEmpty())

            <div class="bg-amber-50 border border-amber-300 rounded-lg p-4">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <div class="text-sm text-amber-800">
                        <p class="font-semibold mb-1">You don't have any output destinations yet</p>
                        <p>An output destination is the SFTP server where your digest web pages will be published. Set one up now and you'll be brought straight back here to continue.</p>
                        <a
                            href="{{ route('output_destinations.create.step1', ['redirect_to' => 'lists.create.step4']) }}"
                            class="inline-block mt-3 bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition"
                        >
                            Set up an output destination →
                        </a>
                    </div>
                </div>
            </div>

        @else

            <div class="flex flex-col gap-3 mb-8">
                @foreach ($destinations as $destination)
                    <label class="flex items-start gap-4 cursor-pointer group">

                        <div class="flex-shrink-0 mt-0.5">
                            <input
                                type="radio"
                                name="output_destination_id"
                                value="{{ $destination->id }}"
                                {{ old('output_destination_id') == $destination->id ? 'checked' : '' }}
                                class="w-5 h-5 accent-purple-700 cursor-pointer mt-1"
                            >
                        </div>

                        <div class="flex-1 border border-gray-300 rounded-lg px-4 py-3 group-hover:border-gray-400 transition">
                            <p class="text-sm font-semibold text-gray-800">{{ $destination->name }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $destination->host }}:{{ $destination->port }}
                                <span class="mx-1">·</span>
                                {{ $destination->username }}
                                <span class="mx-1">·</span>
                                {{ $destination->path }}
                            </p>
                        </div>

                    </label>
                @endforeach
            </div>

        @endif

        <div class="flex justify-between items-center mt-8">
            <a href="{{ route('lists.create.step3') }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold py-3 transition">
                ← Back
            </a>

            @if (! $destinations->isEmpty())
                <button
                    type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
                >
                    Next Step...
                </button>
            @endif
        </div>

    </form>

</x-layouts.app>