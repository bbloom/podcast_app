<x-layouts.app title="Add Output Destination — Step 2">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add an Output Destination</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 2</span>
            <span class="mx-2">—</span>
            <span>Destination type</span>
        </div>
    </div>

    <form method="POST" action="{{ route('output_destinations.create.step2.submit') }}">
        @csrf

        <p class="text-sm text-gray-600 mb-6">Where should your digests be sent?</p>

        @error('type')
            <p class="mb-4 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <label class="flex items-start gap-4 border border-gray-200 rounded-xl p-5 mb-4 cursor-pointer hover:border-purple-400 hover:bg-purple-50 transition has-[:checked]:border-purple-600 has-[:checked]:bg-purple-50">
            <input type="radio" name="type" value="sftp" class="mt-1 accent-purple-700"
                {{ old('type', session('od_wizard.type')) === 'sftp' ? 'checked' : '' }}>
            <div>
                <p class="text-sm font-semibold text-gray-800">Web Page via SFTP</p>
                <p class="text-xs text-gray-500 mt-0.5">Upload a rendered HTML page to your web server via SFTP.</p>
            </div>
        </label>

        <label class="flex items-start gap-4 border border-gray-200 rounded-xl p-5 mb-6 cursor-pointer hover:border-purple-400 hover:bg-purple-50 transition has-[:checked]:border-purple-600 has-[:checked]:bg-purple-50">
            <input type="radio" name="type" value="wordpress" class="mt-1 accent-purple-700"
                {{ old('type', session('od_wizard.type')) === 'wordpress' ? 'checked' : '' }}>
            <div>
                <p class="text-sm font-semibold text-gray-800">WordPress</p>
                <p class="text-xs text-gray-500 mt-0.5">Publish digests as posts via the WordPress REST API.</p>
            </div>
        </label>

        <div class="flex justify-between items-center">
            <a href="{{ route('output_destinations.create.step1') }}" class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Next Step →
            </button>
        </div>

    </form>

</x-layouts.app>