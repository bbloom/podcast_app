<x-layouts.app>
<x-slot:title>Delete Footer Link</x-slot:title>

<div class="max-w-2xl mx-auto">

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Delete Footer Link</h1>

    <div class="bg-white shadow rounded-lg p-6">
        <p class="text-gray-700 mb-4">
            Are you sure you want to delete the footer link
            <strong>{{ $footer_link->link_name }}</strong>
            from <strong>{{ $footer_link->podcastShow->title }}</strong>?
        </p>
        <p class="text-sm text-gray-500 mb-6">This action cannot be undone.</p>

        <div class="flex items-center gap-4">
            <form action="{{ route('footer_links.destroy', $footer_link) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded hover:bg-red-700">
                    Yes, Delete
                </button>
            </form>
            <a href="{{ route('footer_links.show', $footer_link) }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
        </div>
    </div>

</div>
</x-layouts.app>