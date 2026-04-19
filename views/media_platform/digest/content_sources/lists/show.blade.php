<x-layouts.app title="{{ $list->name }}">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
        <a href="{{ route('lists.index') }}" class="hover:text-purple-700 transition">My Lists</a>
        <span>›</span>
        <span class="text-gray-700">{{ $list->name }}</span>
    </div>

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">{{ $list->name }}</h1>
        <a href="{{ route('lists.edit', $list) }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            Edit
        </a>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    @session('error')
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800">
            {{ $value }}
        </div>
    @endsession

    {{-- List Details --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-8">
        <div class="px-4 py-3 bg-purple-50 border-b border-purple-300">
            <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider">Details</h2>
        </div>
        <div class="p-4">
            <table class="text-sm text-gray-600 w-full">
                @if ($list->description)
                    <tr>
                        <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top w-40">Description</td>
                        <td class="py-1.5 text-gray-800">{{ $list->description }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top w-40">Status</td>
                    <td class="py-1.5">
                        @if ($list->enabled)
                            <span class="text-xs bg-green-100 text-green-700 font-semibold px-2 py-0.5 rounded-full">Enabled</span>
                        @else
                            <span class="text-xs bg-gray-100 text-gray-500 font-semibold px-2 py-0.5 rounded-full">Disabled</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Schedule</td>
                    <td class="py-1.5 text-gray-800">
                        {{ ucfirst($list->schedule_frequency) }}
                        @if ($list->schedule_frequency === 'weekly')
                            @php $dayNames = ['1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat','7'=>'Sun']; @endphp
                            · {{ $dayNames[$list->schedule_day] ?? '' }}
                        @elseif ($list->schedule_frequency === 'monthly')
                            · {{ $list->schedule_day }}{{ match(true) { $list->schedule_day==1||$list->schedule_day==21||$list->schedule_day==31=>'st', $list->schedule_day==2||$list->schedule_day==22=>'nd', $list->schedule_day==3||$list->schedule_day==23=>'rd', default=>'th' } }}
                        @endif
                        at {{ \Illuminate\Support\Str::substr($list->schedule_time, 0, 5) }}
                    </td>
                </tr>
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Delivery</td>
                    <td class="py-1.5 text-gray-800">{{ $list->output_type->label() }}</td>
                </tr>
                @if ($list->output_type === \MediaPlatform\Digest\Enums\OutputType::Webpage && $list->outputDestination)
                    <tr>
                        <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Destination</td>
                        <td class="py-1.5 text-gray-800">
                            <a href="{{ route('output_destinations.show', $list->outputDestination) }}" class="hover:text-purple-700 transition">
                                {{ $list->outputDestination->name }}
                            </a>
                        </td>
                    </tr>
                @endif
                @if ($list->output_type === \MediaPlatform\Digest\Enums\OutputType::StaticSite)
                    <tr>
                        <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Retention</td>
                        <td class="py-1.5 text-gray-800">{{ $list->retention_count }} digests</td>
                    </tr>
                @endif
                @if ($list->output_type !== \MediaPlatform\Digest\Enums\OutputType::Email)
                    <tr>
                        <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Email Notification</td>
                        <td class="py-1.5 text-gray-800">{{ $list->notify_by_email ? 'Yes' : 'No' }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="pr-6 py-1.5 text-gray-500 whitespace-nowrap align-top">Last Run</td>
                    <td class="py-1.5 text-gray-800">
                        {{ $list->last_run_at ? $list->last_run_at->format('d M Y H:i') . ' (' . $list->last_run_at->diffForHumans() . ')' : '—' }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Deploy Hooks (static site only) --}}
    @if ($list->output_type === \MediaPlatform\Digest\Enums\OutputType::StaticSite)
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Deploy Hooks</div>
        <div class="border border-purple-300 rounded-lg overflow-hidden mb-8">
            @if ($deployHooks && $deployHooks->isNotEmpty())
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-purple-50 border-b border-purple-300">
                            <th class="text-left font-semibold text-purple-700 px-4 py-3">Label</th>
                            <th class="text-left font-semibold text-purple-700 px-4 py-3">Provider</th>
                            <th class="text-left font-semibold text-purple-700 px-4 py-3">Status</th>
                            <th class="text-left font-semibold text-purple-700 px-4 py-3">Last Triggered</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deployHooks as $hook)
                            <tr class="border-t border-gray-100 hover:bg-gray-50 transition">
                                <td class="px-4 py-3 text-gray-800 font-medium">
                                    <a href="{{ route('deploy_hooks.show', $hook) }}" class="hover:text-purple-700 transition">{{ $hook->label }}</a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $hook->provider->label() }}</td>
                                <td class="px-4 py-3">
                                    @if ($hook->enabled)
                                        <span class="text-xs bg-green-100 text-green-700 font-semibold px-2 py-0.5 rounded-full">Enabled</span>
                                    @else
                                        <span class="text-xs bg-gray-100 text-gray-500 font-semibold px-2 py-0.5 rounded-full">Disabled</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    {{ $hook->last_triggered_at ? $hook->last_triggered_at->diffForHumans() : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($hook->enabled)
                                        <a href="{{ route('deploy_hooks.trigger.confirm', $hook) }}"
                                           class="text-xs text-purple-700 hover:underline font-semibold">Trigger</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-5 py-6 text-center">
                    <p class="text-sm text-gray-500 mb-3">No deploy hooks attached to this list yet.</p>
                </div>
            @endif
            <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                <a href="{{ route('deploy_hooks.create', ['triggerable_type' => 'digest_list', 'triggerable_id' => $list->id, 'redirect_to' => 'lists.show']) }}"
                   class="text-sm text-purple-700 hover:underline font-semibold">
                    Add deploy hook →
                </a>
            </div>
        </div>

        {{-- Published Digests --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Published Digests</div>
        <div class="border border-purple-300 rounded-lg overflow-hidden mb-8">
            @if ($publishedDigests && $publishedDigests->isNotEmpty())
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-purple-50 border-b border-purple-300">
                            <th class="text-left font-semibold text-purple-700 px-4 py-3">Date</th>
                            <th class="text-left font-semibold text-purple-700 px-4 py-3">Slug</th>
                            <th class="text-left font-semibold text-purple-700 px-4 py-3">Items</th>
                            <th class="text-left font-semibold text-purple-700 px-4 py-3">Deploy Hook</th>
                            <th class="text-left font-semibold text-purple-700 px-4 py-3">API Fetched</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($publishedDigests as $digest)
                            <tr class="border-t border-gray-100 hover:bg-gray-50 transition">
                                <td class="px-4 py-3 text-gray-800 font-medium">{{ $digest->digest_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $digest->slug }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $digest->total_items }} from {{ $digest->source_count }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    @if ($digest->deploy_hook_fired_at)
                                        <span class="text-green-700">{{ $digest->deploy_hook_fired_at->diffForHumans() }}</span>
                                    @else
                                        <span class="text-amber-600">Not fired</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    @if ($digest->api_fetched_at)
                                        <span class="text-green-700">{{ $digest->api_fetched_at->diffForHumans() }}</span>
                                    @else
                                        <span class="text-amber-600">Not yet</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-5 py-6 text-center">
                    <p class="text-sm text-gray-500">No digests have been published yet. They will appear here after the list runs.</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Sources --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">
        Sources
        <span class="ml-1 text-sm font-normal text-gray-400">({{ $sources->total() }})</span>
    </div>
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-8">
        @if ($sources->isEmpty())
            <div class="px-5 py-6 text-center">
                <p class="text-sm text-gray-500">No sources attached to this list yet.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-purple-50 border-b border-purple-300">
                        <th class="text-left font-semibold text-purple-700 px-4 py-3">Source</th>
                        <th class="text-left font-semibold text-purple-700 px-4 py-3">Type</th>
                        <th class="text-left font-semibold text-purple-700 px-4 py-3">Mode</th>
                        <th class="text-left font-semibold text-purple-700 px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sources as $source)
                        <tr class="border-t border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-800 font-medium">
                                {{ $source->sourceable->title ?? $source->sourceable->name ?? "Source #{$source->sourceable_id}" }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                @switch($source->sourceable_type)
                                    @case('youtube_channel') YouTube @break
                                    @case('podcast')         Podcast @break
                                    @default                 RSS Feed
                                @endswitch
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ ucfirst($source->processing_mode) }}</td>
                            <td class="px-4 py-3">
                                @if ($source->suspended)
                                    <span class="text-xs bg-red-100 text-red-700 font-semibold px-2 py-0.5 rounded-full">Suspended</span>
                                @elseif ($source->enabled)
                                    <span class="text-xs bg-green-100 text-green-700 font-semibold px-2 py-0.5 rounded-full">Active</span>
                                @else
                                    <span class="text-xs bg-gray-100 text-gray-500 font-semibold px-2 py-0.5 rounded-full">Disabled</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($sources->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">
                    {{ $sources->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Delete --}}
    <div class="mt-2 mb-6">
        <a href="{{ route('lists.delete.confirm', $list) }}"
           class="text-sm text-red-500 hover:text-red-700 font-medium transition">
            Delete this list
        </a>
    </div>

</x-layouts.app>