<x-layouts.app title="New Use Case">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('language_models.usecases.index') }}" class="hover:text-purple-700 transition">← Use Cases</a>
            <span>›</span>
            <span class="text-gray-700">New Use Case</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">New Use Case</h1>
    </div>

    <form method="POST" action="{{ route('language_models.usecases.store') }}" class="space-y-5 max-w-xl">
        @csrf

        <div>
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">
                Name <span class="text-red-500">*</span>
            </label>
            <input type="text" id="name" name="name" value="{{ old('name') }}"
                   placeholder="e.g. Content Summarisation" autofocus required
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('name') border-red-400 @enderror">
            @error('name')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="slug" class="block text-sm font-semibold text-gray-700 mb-1">
                Slug <span class="text-gray-400 font-normal">(auto-generated if blank)</span>
            </label>
            <input type="text" id="slug" name="slug" value="{{ old('slug') }}"
                   placeholder="e.g. digest-processing" pattern="[a-z0-9-]+"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500 @error('slug') border-red-400 @enderror">
            @error('slug')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
            <textarea id="description" name="description" rows="3"
                      placeholder="Optional notes about this use case…"
                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('description') }}</textarea>
            @error('description')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Create Use Case
            </button>
            <a href="{{ route('language_models.usecases.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-5 py-2 rounded">
                Cancel
            </a>
        </div>

    </form>

</x-layouts.app>

@push('scripts')
<script>
document.getElementById('name').addEventListener('input', function () {
    const slug = document.getElementById('slug');
    if (slug.value.trim() !== '') return;
    slug.placeholder = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'e.g. digest-processing';
});
</script>
@endpush