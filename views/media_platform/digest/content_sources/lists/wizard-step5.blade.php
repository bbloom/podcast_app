<x-layouts.app title="Create a List">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Create a List</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 5</span>
            <span>of 6</span>
            <span class="mx-2">—</span>
            <span>Email notifications</span>
        </div>
        @include('media_platform.digest.content_sources.lists._step_dots', ['current' => 5, 'outputType' => 'webpage'])
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('lists.create.step5.submit') }}">
        @csrf

        <div class="mb-8">
            <label class="block text-sm font-semibold text-gray-700 mb-3">
                When your digest is published, would you like to receive a notification email?
            </label>

            @error('notify_by_email')
                <p class="mb-3 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="flex flex-col gap-3">

                {{-- Yes --}}
                <label class="cursor-pointer">
                    <input
                        type="radio"
                        name="notify_by_email"
                        value="1"
                        {{ old('notify_by_email') === '1' ? 'checked' : '' }}
                        class="sr-only peer"
                    >
                    <div class="border border-gray-300 rounded-lg p-4 peer-checked:border-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5">
                                <svg class="w-5 h-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Yes, email me when my digest is ready</p>
                                <p class="text-xs text-gray-500 mt-0.5">We'll send a notification to {{ auth()->user()->email }} with a link to your published digest.</p>
                            </div>
                        </div>
                    </div>
                </label>

                {{-- No --}}
                <label class="cursor-pointer">
                    <input
                        type="radio"
                        name="notify_by_email"
                        value="0"
                        {{ old('notify_by_email') === '0' ? 'checked' : '' }}
                        class="sr-only peer"
                    >
                    <div class="border border-gray-300 rounded-lg p-4 peer-checked:border-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">No, don't send me a notification</p>
                                <p class="text-xs text-gray-500 mt-0.5">I'll check the published page directly when I want to read it.</p>
                            </div>
                        </div>
                    </div>
                </label>

            </div>
        </div>

        <div class="flex justify-between">
            <a href="{{ route('lists.create.step4') }}" class="text-sm text-gray-500 hover:text-gray-700 font-semibold py-3 transition">
                ← Back
            </a>
            <button
                type="submit"
                class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
            >
                Next Step...
            </button>
        </div>

    </form>

</x-layouts.app>
