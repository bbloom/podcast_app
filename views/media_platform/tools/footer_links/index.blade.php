<x-layouts.app>
<x-slot:title>Footer Links</x-slot:title>

<div class="max-w-4xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Footer Links</h1>
        <a href="{{ route('footer_links.create') }}"
           class="inline-flex items-center px-4 py-2 bg-purple-700 text-white text-sm font-medium rounded hover:bg-purple-800">
            Add Footer Link
        </a>
    </div>

    @session('success')
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded">
            {{ $value }}
        </div>
    @endsession

    @session('error')
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded">
            {{ $value }}
        </div>
    @endsession

    @if ($footerLinks->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <p>No footer links yet.</p>
            <a href="{{ route('footer_links.create') }}" class="text-purple-700 hover:underline mt-2 inline-block">
                Create your first footer link
            </a>
        </div>
    @else
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Show</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($footerLinks as $link)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $link->link_order }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <a href="{{ route('footer_links.show', $link) }}" class="text-purple-700 hover:underline">
                                    {{ $link->link_name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $link->podcastShow->title ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs">{{ $link->link_url }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="{{ route('footer_links.edit', $link) }}" class="text-purple-700 hover:underline">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $footerLinks->links() }}
        </div>
    @endif

</div>
</x-layouts.app>