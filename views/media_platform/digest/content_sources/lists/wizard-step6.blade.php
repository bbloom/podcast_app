<x-layouts.app title="Create a List">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Create a List</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 6</span>
            <span>of 6</span>
            <span class="mx-2">—</span>
            <span>Confirm and create</span>
        </div>
        @include('media_platform.digest.content_sources.lists._step_dots', ['current' => 6, 'outputType' => $data['output_type'] ?? null])
    </div>

    {{-- Summary panel --}}
    <div class="bg-gray-50 border border-gray-200 rounded-lg divide-y divide-gray-200 mb-8">

        {{-- Name --}}
        <div class="flex items-start justify-between px-5 py-4">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">List Name</p>
                <p class="text-sm text-gray-800 font-semibold">{{ $data['name'] }}</p>
            </div>
            <a href="{{ route('lists.create.step1') }}" class="text-xs text-purple-700 hover:underline font-medium ml-4 flex-shrink-0">Edit</a>
        </div>

        {{-- Description --}}
        @if (! empty($data['description']))
            <div class="flex items-start justify-between px-5 py-4">
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">Description</p>
                    <p class="text-sm text-gray-800">{{ $data['description'] }}</p>
                </div>
                <a href="{{ route('lists.create.step1') }}" class="text-xs text-purple-700 hover:underline font-medium ml-4 flex-shrink-0">Edit</a>
            </div>
        @endif

        {{-- Timezone --}}
        <div class="flex items-start justify-between px-5 py-4">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">Timezone</p>
                <p class="text-sm text-gray-800">
                    {{ ! empty($data['timezone']) ? $data['timezone'] : 'Account default (' . auth()->user()->timezone . ')' }}
                </p>
            </div>
            <a href="{{ route('lists.create.step1') }}" class="text-xs text-purple-700 hover:underline font-medium ml-4 flex-shrink-0">Edit</a>
        </div>

        {{-- Schedule --}}
        <div class="flex items-start justify-between px-5 py-4">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">Schedule</p>
                <p class="text-sm text-gray-800">
                    @if ($data['schedule_frequency'] === 'daily')
                        Every day at {{ $data['schedule_time'] }}
                    @elseif ($data['schedule_frequency'] === 'weekly')
                        @php
                            $dayNames = ['1' => 'Monday','2' => 'Tuesday','3' => 'Wednesday','4' => 'Thursday','5' => 'Friday','6' => 'Saturday','7' => 'Sunday'];
                        @endphp
                        Every {{ $dayNames[$data['schedule_day']] ?? 'week' }} at {{ $data['schedule_time'] }}
                    @elseif ($data['schedule_frequency'] === 'monthly')
                        @php
                            $day = $data['schedule_day'];
                            $suffix = match(true) {
                                $day === 1 || $day === 21 || $day === 31 => 'st',
                                $day === 2 || $day === 22 => 'nd',
                                $day === 3 || $day === 23 => 'rd',
                                default => 'th',
                            };
                        @endphp
                        The {{ $day }}{{ $suffix }} of each month at {{ $data['schedule_time'] }}
                    @endif
                </p>
            </div>
            <a href="{{ route('lists.create.step2') }}" class="text-xs text-purple-700 hover:underline font-medium ml-4 flex-shrink-0">Edit</a>
        </div>

        {{-- Output type --}}
        <div class="flex items-start justify-between px-5 py-4">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">Delivery</p>
                <p class="text-sm text-gray-800">
                    {{ $data['output_type'] === 'webpage' ? 'Web page (via SFTP)' : 'Email' }}
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

        {{-- Email notification (webpage only) --}}
        @if (($data['output_type'] ?? null) === 'webpage')
            <div class="flex items-start justify-between px-5 py-4">
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">Email Notification</p>
                    <p class="text-sm text-gray-800">
                        {{ ! empty($data['notify_by_email']) ? 'Yes — notify me when the digest is published' : 'No notifications' }}
                    </p>
                </div>
                <a href="{{ route('lists.create.step5') }}" class="text-xs text-purple-700 hover:underline font-medium ml-4 flex-shrink-0">Edit</a>
            </div>
        @endif

    </div>

    {{-- Save --}}
    <form method="POST" action="{{ route('lists.create.step6.submit') }}">
        @csrf
        <div class="flex justify-between">
            <a href="{{ ($data['output_type'] ?? null) === 'email' ? route('lists.create.step3') : route('lists.create.step5') }}"
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
