<x-layouts.app title="Create a List">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Create a List</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 4</span>
            <span>of 6</span>
            <span class="mx-2">—</span>
            <span>Email notifications</span>
        </div>
        @include('media_platform.digest.content_sources.lists._step_dots', ['current' => 4])
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800 font-medium">
            {{ session('success') }}
        </div>
    @endif

    {{-- Context --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-blue-800 font-medium mb-1">Static Site delivery</p>
        <p class="text-sm text-blue-700">
            Your digest data will be stored and served via the API. After each digest run, a deploy hook will fire automatically to trigger your static site rebuild. The static site generator fetches the data and renders the pages.
        </p>
    </div>

    <form method="POST" action="{{ route('lists.create.step4_static_site.submit') }}">
        @csrf

        @error('notify_by_email')
            <p class="mb-4 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <p class="text-sm font-semibold text-gray-700 mb-3">
            Would you like to receive a confirmation email each time a digest is published?
        </p>
        <p class="text-xs text-gray-500 mb-4">
            This email confirms the digest was built and the deploy hook was fired. It does not contain the actual digest content — your static site handles the presentation.
        </p>

        <div class="flex gap-3 mb-8">
            <label class="flex-1 cursor-pointer">
                <input type="radio" name="notify_by_email" value="1"
                    {{ old('notify_by_email', '1') === '1' ? 'checked' : '' }}
                    class="sr-only peer">
                <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                    Yes — notify me
                </div>
            </label>
            <label class="flex-1 cursor-pointer">
                <input type="radio" name="notify_by_email" value="0"
                    {{ old('notify_by_email') === '0' ? 'checked' : '' }}
                    class="sr-only peer">
                <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                    No notifications
                </div>
            </label>
        </div>

        <div class="flex justify-between">
            <a href="{{ route('lists.create.step3') }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold py-3 transition">
                ← Back
            </a>
            <button
                type="submit"
                class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
            >
                Continue →
            </button>
        </div>
    </form>

</x-layouts.app>