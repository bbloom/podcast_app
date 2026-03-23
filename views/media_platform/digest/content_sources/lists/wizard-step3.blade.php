<x-layouts.app title="Create a List">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Create a List</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 3</span>
            <span>of 6</span>
            <span class="mx-2">—</span>
            <span>Choose how it's delivered</span>
        </div>
        @include('media_platform.digest.content_sources.lists._step_dots', ['current' => 3])
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('lists.create.step3.submit') }}">
        @csrf

        <div class="mb-8">
            <label class="block text-sm font-semibold text-gray-700 mb-3">How should your digest be delivered?</label>

            @error('output_type')
                <p class="mb-3 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="flex flex-col gap-3">

                {{-- Webpage option --}}
                <label class="cursor-pointer">
                    <input
                        type="radio"
                        name="output_type"
                        value="webpage"
                        {{ old('output_type') === 'webpage' ? 'checked' : '' }}
                        class="sr-only peer"
                    >
                    <div class="border border-gray-300 rounded-lg p-4 peer-checked:border-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5">
                                <svg class="w-5 h-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Web page</p>
                                <p class="text-xs text-gray-500 mt-0.5">Your digest is published as an HTML file to your server via SFTP. You can share the URL with anyone.</p>
                            </div>
                        </div>
                    </div>
                </label>

                {{-- Email option --}}
                <label class="cursor-pointer">
                    <input
                        type="radio"
                        name="output_type"
                        value="email"
                        {{ old('output_type') === 'email' ? 'checked' : '' }}
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
                                <p class="text-sm font-semibold text-gray-800">Email</p>
                                <p class="text-xs text-gray-500 mt-0.5">Your digest is sent directly to your email address ({{ auth()->user()->email }}) on schedule.</p>
                            </div>
                        </div>
                    </div>
                </label>

            </div>
        </div>

        <div class="flex justify-between">
            <a href="{{ route('lists.create.step2') }}" class="text-sm text-gray-500 hover:text-gray-700 font-semibold py-3 transition">
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
