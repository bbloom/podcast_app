<x-layouts.app title="New Language Model">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('language_models.languagemodel.index') }}" class="hover:text-purple-700 transition">← Language Models</a>
            <span>›</span>
            <span class="text-gray-700">New Model</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">New Language Model</h1>
    </div>

    <form method="POST" action="{{ route('language_models.languagemodel.store') }}" class="space-y-5 max-w-xl">
        @csrf

        <div>
            <label for="provider_id" class="block text-sm font-semibold text-gray-700 mb-1">
                Provider <span class="text-red-500">*</span>
            </label>
            <select id="provider_id" name="provider_id" required
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('provider_id') border-red-400 @enderror">
                <option value="">— Select a provider —</option>
                @foreach($providers as $provider)
                    <option value="{{ $provider->id }}" {{ old('provider_id') == $provider->id ? 'selected' : '' }}>
                        {{ $provider->name }}
                    </option>
                @endforeach
            </select>
            @error('provider_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">
                Model Name <span class="text-red-500">*</span>
            </label>
            <input type="text" id="name" name="name" value="{{ old('name') }}"
                   placeholder="e.g. Gemini 2.5 Flash" autofocus required
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('name') border-red-400 @enderror">
            @error('name')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="slug" class="block text-sm font-semibold text-gray-700 mb-1">
                Slug / Model ID <span class="text-gray-400 font-normal">(auto-generated if blank)</span>
            </label>
            <input type="text" id="slug" name="slug" value="{{ old('slug') }}"
                   placeholder="e.g. claude-3-5-sonnet-20241022" pattern="[a-z0-9.-]+"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500 @error('slug') border-red-400 @enderror">
            @error('slug')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
            <textarea id="description" name="description" rows="3"
                      placeholder="Optional notes about this model…"
                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('description') }}</textarea>
            @error('description')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Use Cases</label>
            <div class="flex flex-wrap gap-2">
                @foreach($useCases as $useCase)
                    <label class="inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 rounded-full text-sm cursor-pointer hover:border-purple-400 has-[:checked]:border-purple-500 has-[:checked]:bg-purple-50 has-[:checked]:text-purple-700 transition-colors">
                        <input type="checkbox" name="use_case_ids[]" value="{{ $useCase->id }}"
                               {{ in_array($useCase->id, old('use_case_ids', [])) ? 'checked' : '' }}
                               class="w-3.5 h-3.5 accent-purple-700">
                        {{ $useCase->name }}
                    </label>
                @endforeach
            </div>
            @error('use_case_ids')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2">
            <input type="hidden" name="enabled" value="0">
            <input type="checkbox" id="enabled" name="enabled" value="1"
                   {{ old('enabled', true) ? 'checked' : '' }}
                   class="w-4 h-4 accent-purple-700 cursor-pointer">
            <label for="enabled" class="text-sm text-gray-700 cursor-pointer">Active</label>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Create Model
            </button>
            <a href="{{ route('language_models.languagemodel.index') }}"
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
    slug.placeholder = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'e.g. claude-3-5-sonnet-20241022';
});
</script>
@endpush