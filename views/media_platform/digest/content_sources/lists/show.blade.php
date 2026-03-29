<x-layouts.app title="{{ $list->name }}">

    {{-- Breadcrumb + heading --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('lists.index') }}" class="hover:text-purple-700 transition">← My Lists</a>
            <span>›</span>
            <span class="text-gray-700">{{ $list->name }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $list->name }}</h1>
            <a href="{{ route('lists.edit', $list) }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Edit
            </a>
        </div>
    </div>

    {{-- List details --}}
    <div class="border border-purple-500 rounded-lg p-4 mb-8">
        <div class="flex-1 min-w-0">
            <p class="text-xl font-bold text-gray-800">{{ $list->name }}</p>

            @if($list->description)
                <p class="mt-2 text-sm text-gray-600">{{ $list->description }}</p>
            @endif

            <table class="mt-4 text-sm text-gray-600 border-collapse">
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Status</td>
                    <td class="py-1">
                        @if($list->enabled)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Schedule</td>
                    <td class="py-1 text-gray-800">
                        @if($list->schedule_frequency === 'daily')
                            Daily at {{ $list->schedule_time }}
                        @elseif($list->schedule_frequency === 'weekly')
                            @php $dayNames = ['1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat','7'=>'Sun']; @endphp
                            Weekly · {{ $dayNames[$list->schedule_day] ?? '' }} at {{ $list->schedule_time }}
                        @elseif($list->schedule_frequency === 'monthly')
                            Monthly · {{ $list->schedule_day }}{{ match(true) { $list->schedule_day==1||$list->schedule_day==21||$list->schedule_day==31=>'st', $list->schedule_day==2||$list->schedule_day==22=>'nd', $list->schedule_day==3||$list->schedule_day==23=>'rd', default=>'th' } }} at {{ $list->schedule_time }}
                        @endif
                        @if($list->timezone)
                            <span class="text-gray-500">({{ $list->timezone }})</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Output</td>
                    <td class="py-1 text-gray-800">
                        {{ match(strtolower($list->output_type->value) ?? $list->output_type) {
                            'email'     => 'Email',
                            'wordpress' => 'WordPress',
                            default     => 'Web page',
                        } }}
                    </td>
                </tr>
                @if($list->last_run_at)
                    <tr>
                        <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Last Run</td>
                        <td class="py-1 text-gray-800">{{ $list->last_run_at->diffForHumans() }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Added</td>
                    <td class="py-1 text-gray-800">{{ $list->created_at->format('d M Y') }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Sources --}}
    <div class="mb-4">
        <h2 class="text-lg font-bold text-gray-800">
            Sources
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $list->sources()->count() }})</span>
        </h2>
    </div>

    @if($sources->isEmpty())
        <div class="border border-gray-200 rounded-lg px-6 py-10 text-center text-sm text-gray-400">
            This list has no sources yet.
        </div>
    @else
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Source</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Mode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Last Fetched</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Failures</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($sources as $listSource)
                        @php
                            $track      = $tracking->get($listSource->id);
                            $sourceable = $listSource->sourceable;
                            $showRoute  = match($listSource->sourceable_type) {
                                'youtube_channel'     => route('youtube.channels.show', $sourceable),
                                'podcast'             => route('podcasts.show', $sourceable),
                                'text_based_rss_feed' => route('text_based_rss_feeds.show', $sourceable),
                                default               => null,
                            };
                            $typeLabel = match($listSource->sourceable_type) {
                                'youtube_channel'     => 'YouTube',
                                'podcast'             => 'Podcast',
                                'text_based_rss_feed' => 'RSS Feed',
                                default               => $listSource->sourceable_type,
                            };
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                @if($showRoute && $sourceable)
                                    <a href="{{ $showRoute }}"
                                       class="font-medium text-purple-700 hover:underline">
                                        {{ $sourceable->title }}
                                    </a>
                                @else
                                    <span class="font-medium text-gray-800">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $typeLabel }}</td>
                            <td class="px-6 py-4 text-gray-700 capitalize">{{ $listSource->processing_mode }}</td>
                            <td class="px-6 py-4">
                                @if($listSource->suspended)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Suspended</span>
                                @elseif($listSource->enabled)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $track?->last_fetched_at?->diffForHumans() ?? '—' }}
                            </td>
                            <td class="px-6 py-4">
                                @if(($track?->consecutive_failures ?? 0) > 0)
                                    <span class="text-red-600 font-medium">{{ $track->consecutive_failures }}</span>
                                @else
                                    <span class="text-gray-400">0</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($sources->hasPages())
            <div class="mt-4">{{ $sources->links() }}</div>
        @endif
    @endif

    <div class="mt-6 text-sm">
        <a href="{{ route('lists.index') }}" class="hover:text-purple-700 transition">← My Lists</a>
    </div>

</x-layouts.app>