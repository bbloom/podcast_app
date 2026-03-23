<x-layouts.app title="Fix Connection — WordPress Post Settings">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Fix Connection</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">WordPress Post Settings</span>
            <span class="mx-2">—</span>
            <span>Correct your post settings, then return to the connection test</span>
        </div>
    </div>

    <form method="POST" action="{{ route('output_destinations.fix.wordpress.post_settings.submit') }}">
        @csrf

        <div class="mb-6">
            <label for="wordpress_post_status" class="block text-sm font-semibold text-gray-700 mb-2">Post Status</label>
            <select
                id="wordpress_post_status"
                name="wordpress_post_status"
                required
                class="w-48 border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('wordpress_post_status') border-red-400 @enderror"
            >
                @foreach (['publish' => 'Published', 'draft' => 'Draft', 'private' => 'Private'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('wordpress_post_status', session('od_wizard.wordpress_post_status')) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('wordpress_post_status')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="wordpress_category_ids" class="block text-sm font-semibold text-gray-700 mb-2">
                Category IDs <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input
                type="text"
                id="wordpress_category_ids"
                name="wordpress_category_ids"
                value="{{ old('wordpress_category_ids', session('od_wizard.wordpress_category_ids')) }}"
                placeholder="e.g. 3,14"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('wordpress_category_ids') border-red-400 @enderror"
            >
            @error('wordpress_category_ids')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">Comma-separated WordPress category IDs.</p>
        </div>

        <div class="mb-6">
            <label for="wordpress_tag_ids" class="block text-sm font-semibold text-gray-700 mb-2">
                Tag IDs <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input
                type="text"
                id="wordpress_tag_ids"
                name="wordpress_tag_ids"
                value="{{ old('wordpress_tag_ids', session('od_wizard.wordpress_tag_ids')) }}"
                placeholder="e.g. 7,22"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('wordpress_tag_ids') border-red-400 @enderror"
            >
            @error('wordpress_tag_ids')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">Comma-separated WordPress tag IDs.</p>
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('output_destinations.create.wp3') }}" class="text-sm text-purple-700 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to test
            </a>
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Save &amp; Retry Test →
            </button>
        </div>

    </form>

</x-layouts.app>