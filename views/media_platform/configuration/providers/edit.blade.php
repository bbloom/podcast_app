<x-layouts.app :title="'Edit ' . $provider->name">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('language_models.providers.index') }}" class="hover:text-purple-700 transition">← Providers</a>
            <span>›</span>
            <a href="{{ route('language_models.providers.show', $provider) }}" class="hover:text-purple-700 transition">{{ $provider->name }}</a>
            <span>›</span>
            <span class="text-gray-700">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Edit Provider</h1>
    </div>

    <form method="POST" action="{{ route('language_models.providers.update', $provider) }}" class="space-y-5 max-w-xl">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">
                Name <span class="text-red-500">*</span>
            </label>
            <input type="text" id="name" name="name" value="{{ old('name', $provider->name) }}"
                   required
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('name') border-red-400 @enderror">
            @error('name')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="slug" class="block text-sm font-semibold text-gray-700 mb-1">Slug</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $provider->slug) }}"
                   pattern="[a-z0-9-]+"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500 @error('slug') border-red-400 @enderror">
            @error('slug')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
            <textarea id="description" name="description" rows="3"
                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('description', $provider->description) }}</textarea>
            @error('description')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="website_url" class="block text-sm font-semibold text-gray-700 mb-1">Website URL</label>
            <input type="url" id="website_url" name="website_url" value="{{ old('website_url', $provider->website_url) }}"
                   placeholder="https://…"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('website_url') border-red-400 @enderror">
            @error('website_url')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2">
            <input type="hidden" name="enabled" value="0">
            <input type="checkbox" id="enabled" name="enabled" value="1"
                   {{ old('enabled', $provider->enabled) ? 'checked' : '' }}
                   class="w-4 h-4 accent-purple-700 cursor-pointer">
            <label for="enabled" class="text-sm text-gray-700 cursor-pointer">Active</label>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Save Changes
            </button>
            <a href="{{ route('language_models.providers.show', $provider) }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-5 py-2 rounded">
                Cancel
            </a>
        </div>

    </form>

</x-layouts.app>