<x-layouts.app title="{{ $outputDestination->name }}">

    {{-- Breadcrumb + heading --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('output_destinations.index') }}" class="hover:text-purple-700 transition">← Output Destinations</a>
            <span>›</span>
            <span class="text-gray-700">{{ $outputDestination->name }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $outputDestination->name }}</h1>
            <a href="{{ route('output_destinations.edit', $outputDestination) }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Edit
            </a>
        </div>
    </div>

    {{-- Destination details --}}
    <div class="border border-purple-500 rounded-lg p-4 mb-8">
        <div class="flex-1 min-w-0">
            <p class="text-xl font-bold text-gray-800">{{ $outputDestination->name }}</p>

            <table class="mt-4 text-sm text-gray-600 border-collapse">
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Status</td>
                    <td class="py-1">
                        @if($outputDestination->enabled)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Type</td>
                    <td class="py-1 text-gray-800">SFTP</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Host</td>
                    <td class="py-1"><code class="font-mono font-bold text-gray-800">{{ $outputDestination->username }}@{{ $outputDestination->host }}:{{ $outputDestination->port }}</code></td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Path</td>
                    <td class="py-1"><code class="font-mono text-gray-800">{{ $outputDestination->path }}</code></td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Auth</td>
                    <td class="py-1 text-gray-800">{{ $outputDestination->auth_type === 'ssh_key' ? 'SSH Key' : 'Password' }}</td>
                </tr>
                @if($outputDestination->base_url)
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Public URL</td>
                        <td class="py-1">
                            <a href="{{ $outputDestination->base_url }}" target="_blank"
                               class="text-purple-700 hover:underline break-all">
                                {{ $outputDestination->base_url }}
                            </a>
                        </td>
                    </tr>
                @endif
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Added</td>
                    <td class="py-1 text-gray-800">{{ $outputDestination->created_at->format('d M Y') }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Lists using this destination --}}
    <div class="mb-4">
        <h2 class="text-lg font-bold text-gray-800">
            Lists using this destination
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $outputDestination->lists()->count() }})</span>
        </h2>
    </div>

    @if($lists->isEmpty())
        <div class="border border-gray-200 rounded-lg px-6 py-10 text-center text-sm text-gray-400">
            No lists are using this destination yet.
        </div>
    @else
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">List</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Schedule</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Last Run</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($lists as $list)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('lists.show', $list) }}"
                                   class="font-medium text-purple-700 hover:underline">
                                    {{ $list->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                @if($list->schedule_frequency === 'daily')
                                    Daily at {{ $list->schedule_time }}
                                @elseif($list->schedule_frequency === 'weekly')
                                    @php $dayNames = ['1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat','7'=>'Sun']; @endphp
                                    Weekly · {{ $dayNames[$list->schedule_day] ?? '' }} at {{ $list->schedule_time }}
                                @elseif($list->schedule_frequency === 'monthly')
                                    Monthly · {{ $list->schedule_day }}{{ match(true) { $list->schedule_day==1||$list->schedule_day==21||$list->schedule_day==31=>'st', $list->schedule_day==2||$list->schedule_day==22=>'nd', $list->schedule_day==3||$list->schedule_day==23=>'rd', default=>'th' } }} at {{ $list->schedule_time }}
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($list->enabled)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $list->last_run_at?->diffForHumans() ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($lists->hasPages())
            <div class="mt-4">{{ $lists->links() }}</div>
        @endif
    @endif

    <div class="mt-6 text-sm">
        <a href="{{ route('output_destinations.index') }}" class="hover:text-purple-700 transition">← Output Destinations</a>
    </div>

</x-layouts.app>