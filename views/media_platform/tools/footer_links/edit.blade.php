<x-layouts.app>
<x-slot:title>Edit Footer Link</x-slot:title>

<div class="max-w-2xl mx-auto">

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Footer Link</h1>

    @session('error')
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded">
            {{ $value }}
        </div>
    @endsession

    <form action="{{ route('footer_links.update', $footer_link) }}" method="POST" class="bg-white shadow rounded-lg p-6 space-y-6">
        @csrf
        @method('PUT')

        {{-- Podcast Show --}}
        <div>
            <label for="podcast_show_id" class="block text-sm font-medium text-gray-700 mb-1">Podcast Show</label>
            <select name="podcast_show_id" id="podcast_show_id"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                <option value="">— Select a show —</option>
                @foreach ($shows as $show)
                    <option value="{{ $show->id }}" @selected(old('podcast_show_id', $footer_link->podcast_show_id) == $show->id)>
                        {{ $show->title }}
                    </option>
                @endforeach
            </select>
            @error('podcast_show_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Link Name --}}
        <div>
            <label for="link_name" class="block text-sm font-medium text-gray-700 mb-1">Link Name</label>
            <input type="text" name="link_name" id="link_name" value="{{ old('link_name', $footer_link->link_name) }}"
                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
            @error('link_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Link URL --}}
        <div>
            <label for="link_url" class="block text-sm font-medium text-gray-700 mb-1">Link URL</label>
            <input type="url" name="link_url" id="link_url" value="{{ old('link_url', $footer_link->link_url) }}"
                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
            @error('link_url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Link Order --}}
        <div>
            <label for="link_order" class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
            <input type="number" name="link_order" id="link_order" value="{{ old('link_order', $footer_link->link_order) }}" min="0"
                   class="w-32 border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
            @error('link_order')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-purple-700 text-white text-sm font-medium rounded hover:bg-purple-800">
                Update Footer Link
            </button>
            <a href="{{ route('footer_links.show', $footer_link) }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
        </div>
    </form>

</div>
</x-layouts.app>