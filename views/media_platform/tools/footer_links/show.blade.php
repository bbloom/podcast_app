<x-layouts.app>
<x-slot:title>{{ $footer_link->link_name }}</x-slot:title>

<div class="max-w-2xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ $footer_link->link_name }}</h1>
        <a href="{{ route('footer_links.edit', $footer_link) }}"
           class="inline-flex items-center px-4 py-2 bg-purple-700 text-white text-sm font-medium rounded hover:bg-purple-800">
            Edit
        </a>
    </div>

    @session('success')
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded">
            {{ $value }}
        </div>
    @endsession

    <div class="bg-white shadow rounded-lg p-6 space-y-4">
        <div>
            <dt class="text-sm font-medium text-gray-500">Podcast Show</dt>
            <dd class="mt-1 text-sm text-gray-900">
                <a href="{{ route('podcast_shows.show', $footer_link->podcastShow) }}" class="text-purple-700 hover:underline">
                    {{ $footer_link->podcastShow->title }}
                </a>
            </dd>
        </div>

        <div>
            <dt class="text-sm font-medium text-gray-500">Link Name</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $footer_link->link_name }}</dd>
        </div>

        <div>
            <dt class="text-sm font-medium text-gray-500">Link URL</dt>
            <dd class="mt-1 text-sm text-gray-900">
                <a href="{{ $footer_link->link_url }}" target="_blank" rel="noopener" class="text-purple-700 hover:underline">
                    {{ $footer_link->link_url }}
                </a>
            </dd>
        </div>

        <div>
            <dt class="text-sm font-medium text-gray-500">Display Order</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $footer_link->link_order }}</dd>
        </div>

        <div>
            <dt class="text-sm font-medium text-gray-500">Created</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $footer_link->created_at->format('M j, Y g:i A') }}</dd>
        </div>

        <div>
            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $footer_link->updated_at->format('M j, Y g:i A') }}</dd>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-4">
        <a href="{{ route('footer_links.index') }}" class="text-sm text-gray-600 hover:underline">← Back to all footer links</a>
        <a href="{{ route('footer_links.delete.confirm', $footer_link) }}" class="text-sm text-red-600 hover:underline">Delete this link</a>
    </div>

</div>
</x-layouts.app>