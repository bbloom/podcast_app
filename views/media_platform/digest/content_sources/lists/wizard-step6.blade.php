<x-layouts.app title="Create a List">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Create a List</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 6</span>
            <span>of 6</span>
            <span class="mx-2">—</span>
            <span>Confirm and save</span>
        </div>
        @include('media_platform.digest.content_sources.lists._step_dots', ['current' => 6])
    </div>

    {{-- Summary --}}
    <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 mb-8">

        {{-- Name --}}
        <div class="flex items-start justify-between px-5 py-4">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">List Name</p>
                <p class="text-sm text-gray-800 font-semibold">{{ $data['name'] ?? '—' }}</p>
                @if (! empty($data['description']))
                    <p class="text-xs text-gray-500 mt-0.5">{{ $data['description'] }}</p>
                @endif
            </div>
            <a href="{{ route('lists.create.step1') }}" class="text-xs text-purple-700 hover:underline font-medium ml-4 flex-shrink-0">Edit</a>
        </div>

        {{-- Schedule --}}
        <div class="flex items-start justify-between px-5 py-4">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">Schedule</p>
                <p class="text-sm text-gray-800">
                    {{ ucfirst($data['schedule_frequency'] ?? '—') }}
                    @if (($data['schedule_frequency'] ?? '') === 'weekly' && ($data['schedule_day'] ?? null))
                        @php $dayNames = ['1'=>'Monday','2'=>'Tuesday','3'=>'Wednesday','4'=>'Thursday','5'=>'Friday','6'=>'Saturday','7'=>'Sunday']; @endphp
                        — {{ $dayNames[$data['schedule_day']] ?? '' }}
                    @elseif (($data['schedule_frequency'] ?? '') === 'monthly' && ($data['schedule_day'] ?? null))
                        — {{ $data['schedule_day'] }}{{ match(true) { $data['schedule_day']==1||$data['schedule_day']==21||$data['schedule_day']==31=>'st', $data['schedule_day']==2||$data['schedule_day']==22=>'nd', $data['schedule_day']==3||$data['schedule_day']==23=>'rd', default=>'th' } }}
                    @endif
                    at {{ $data['schedule_time'] ?? '—' }}
                </p>
            </div>
            <a href="{{ route('lists.create.step2') }}" class="text-xs text-purple-700 hover:underline font-medium ml-4 flex-shrink-0">Edit</a>
        </div>

        {{-- Output type --}}
        <div class="flex items-start justify-between px-5 py-4">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">Delivery</p>
                <p class="text-sm text-gray-800">
                    @switch($data['output_type'] ?? '')
                        @case('webpage')  Web page (via SFTP) @break
                        @case('email')    Email @break
                        @case('static_site') Static Site @break
                        @default {{ $data['output_type'] ?? '—' }}
                    @endswitch
                </p>
            </div>
            <a href="{{ route('lists.create.step3') }}" class="text-xs text-purple-700 hover:underline font-medium ml-4 flex-shrink-0">Edit</a>
        </div>

        {{-- Output destination (webpage only) --}}
        @if (($data['output_type'] ?? null) === 'webpage' && $destination)
            <div class="flex items-start justify-between px-5 py-4">
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">Output Destination</p>
                    <p class="text-sm text-gray-800 font-semibold">{{ $destination->name }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $destination->host }}{{ $destination->path ? ' · ' . $destination->path : '' }}</p>
                </div>
                <a href="{{ route('lists.create.step4') }}" class="text-xs text-purple-700 hover:underline font-medium ml-4 flex-shrink-0">Edit</a>
            </div>
        @endif

        {{-- Email notification (webpage or static_site) --}}
        @if (in_array($data['output_type'] ?? null, ['webpage', 'static_site']))
            <div class="flex items-start justify-between px-5 py-4">
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">Email Notification</p>
                    <p class="text-sm text-gray-800">
                        {{ ! empty($data['notify_by_email']) ? 'Yes — notify me when the digest is published' : 'No notifications' }}
                    </p>
                </div>
                <a href="{{ ($data['output_type'] ?? null) === 'static_site' ? route('lists.create.step4_static_site') : route('lists.create.step5') }}"
                   class="text-xs text-purple-700 hover:underline font-medium ml-4 flex-shrink-0">Edit</a>
            </div>
        @endif

        {{-- Deploy hooks note (static_site only) --}}
        @if (($data['output_type'] ?? null) === 'static_site')
            <div class="px-5 py-4">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">Deploy Hooks</p>
                <p class="text-sm text-gray-500">
                    You'll add deploy hooks after the list is created — they need the list to exist first.
                </p>
            </div>
        @endif

    </div>

    {{-- Save --}}
    <form method="POST" action="{{ route('lists.create.step6.submit') }}">
        @csrf
        <div class="flex justify-between">
            @php
                $backRoute = match ($data['output_type'] ?? null) {
                    'email'       => route('lists.create.step3'),
                    'static_site' => route('lists.create.step4_static_site'),
                    default       => route('lists.create.step5'),
                };
            @endphp
            <a href="{{ $backRoute }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold py-3 transition">
                ← Back
            </a>
            <button
                type="submit"
                class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
            >
                Create List
            </button>
        </div>
    </form>

</x-layouts.app>