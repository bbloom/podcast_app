<x-layouts.app title="{{ $sponsor->full_name }}">

    {{-- Breadcrumb + heading --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('phpserverlessproject_sponsors.index') }}" class="hover:text-purple-700 transition">← PHPServerlessProject Sponsors</a>
            <span>›</span>
            <span class="text-gray-700">{{ $sponsor->full_name }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $sponsor->full_name }}</h1>
            <a href="{{ route('phpserverlessproject_sponsors.edit', $sponsor) }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Edit
            </a>
        </div>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    {{-- Sponsor details --}}
    <div class="border border-purple-500 rounded-lg p-6 mb-8">

        {{-- Images --}}
        @if ($sponsor->image_url || $sponsor->image_thumbnail_url)
            <div class="flex items-center gap-4 mb-6">
                @if ($sponsor->image_thumbnail_url)
                    <img src="{{ $sponsor->image_thumbnail_url }}" alt="{{ $sponsor->full_name }}"
                         class="w-16 h-16 rounded-full object-cover border border-gray-200">
                @endif
                @if ($sponsor->image_url)
                    <img src="{{ $sponsor->image_url }}" alt="{{ $sponsor->full_name }}"
                         class="w-24 h-24 rounded-lg object-cover border border-gray-200">
                @endif
            </div>
        @endif

        <p class="text-xl font-bold text-gray-800 mb-1">{{ $sponsor->full_name }}</p>
        @if ($sponsor->profile_short)
            <p class="text-sm text-gray-500 mb-4">{{ $sponsor->profile_short }}</p>
        @else 
            <p class="text-sm text-gray-500 mb-4">(note: there is no short profile)</p>
        @endif

        <table class="text-sm text-gray-600 border-collapse w-full">

            {{-- Profile --}}
            <tr><td colspan="2" class="pt-4 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Profile</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Full Profile</td>
                <td class="py-1 text-gray-800">{{ $sponsor->profile_full }}</td>
            </tr>
            @if ($sponsor->email_address)
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Email</td>
                <td class="py-1 text-gray-800">{{ $sponsor->email_address }}</td>
            </tr>
            @endif
            @if ($sponsor->link_to_sponsor_website)
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Website</td>
                <td class="py-1">
                    <a href="{{ $sponsor->link_to_sponsor_website }}" target="_blank"
                       class="text-purple-700 hover:underline text-xs">
                        {{ $sponsor->link_to_sponsor_website }}
                    </a>
                </td>
            </tr>
            @endif

            {{-- Sponsorship tiers --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Sponsorship</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Umbrella</td>
                <td class="py-1">
                    @if ($sponsor->umbrella_sponsor)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Yes</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">No</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Basecamp</td>
                <td class="py-1">
                    @if ($sponsor->basecamp_sponsor)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Yes</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">No</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Restream</td>
                <td class="py-1">
                    @if ($sponsor->restream_sponsor)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Yes</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">No</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Former Sponsor</td>
                <td class="py-1">
                    @if ($sponsor->former_sponsor)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">Yes</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">No</span>
                    @endif
                </td>
            </tr>

            {{-- Status --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Status</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Enabled</td>
                <td class="py-1">
                    @if ($sponsor->enabled)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                    @endif
                </td>
            </tr>
            @if ($sponsor->internal_comment)
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Internal Comment</td>
                <td class="py-1 text-gray-800">{{ $sponsor->internal_comment }}</td>
            </tr>
            @endif

            {{-- Record --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Record</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Created</td>
                <td class="py-1 text-gray-800">{{ $sponsor->created_at->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Updated</td>
                <td class="py-1 text-gray-800">{{ $sponsor->updated_at->format('d M Y') }}</td>
            </tr>

        </table>
    </div>

    <div class="mt-2 mb-6">
        <a href="{{ route('phpserverlessproject_sponsors.delete.confirm', $sponsor) }}"
           class="text-sm text-red-500 hover:text-red-700 font-medium transition">
            Delete this sponsor
        </a>
    </div>

    <div class="mt-6 text-sm">
        <a href="{{ route('phpserverlessproject_sponsors.index') }}" class="hover:text-purple-700 transition">← PHPServerlessProject Sponsors</a>
    </div>

</x-layouts.app>