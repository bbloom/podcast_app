<x-layouts.app title="Edit List">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('lists.index') }}" class="hover:text-purple-700 transition">My Lists</a>
            <span>›</span>
            <span class="text-gray-700">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Edit List</h1>
    </div>

    <form method="POST" action="{{ route('lists.update', $list) }}"
          x-data="{
              frequency:  '{{ old('schedule_frequency', $list->schedule_frequency) }}',
              day:        {{ old('schedule_day', $list->schedule_day ?? 'null') }},
              outputType: '{{ old('output_type', $list->output_type) }}',
              setDay(val) { this.day = val; }
          }">
        @csrf
        @method('PUT')

        {{-- Single hidden input for schedule_day --}}
        <input type="hidden" name="schedule_day" :value="frequency === 'daily' ? '' : day">

        {{-- Name --}}
        <div class="mb-6">
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">List Name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name', $list->name) }}"
                required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('name') border-red-400 @enderror"
            >
            @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Description --}}
        <div class="mb-6">
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                Description <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <textarea
                id="description"
                name="description"
                rows="3"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('description') border-red-400 @enderror"
            >{{ old('description', $list->description) }}</textarea>
            @error('description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Timezone --}}
        <div class="mb-6">
            <label for="timezone" class="block text-sm font-semibold text-gray-700 mb-2">
                Timezone <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <select
                id="timezone"
                name="timezone"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
            >
                <option value="">— Use my account timezone ({{ auth()->user()->timezone }}) —</option>
                @foreach (\DateTimeZone::listIdentifiers() as $tz)
                    <option value="{{ $tz }}" {{ old('timezone', $list->timezone) === $tz ? 'selected' : '' }}>
                        {{ $tz }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Frequency --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-3">Frequency</label>
            <div class="flex gap-3">
                @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $value => $label)
                    <label class="flex-1 cursor-pointer">
                        <input
                            type="radio"
                            name="schedule_frequency"
                            value="{{ $value }}"
                            x-model="frequency"
                            @change="day = null"
                            {{ old('schedule_frequency', $list->schedule_frequency) === $value ? 'checked' : '' }}
                            class="sr-only peer"
                        >
                        <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                            {{ $label }}
                        </div>
                    </label>
                @endforeach
            </div>
            @error('schedule_frequency') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Day of week (weekly only) --}}
        <div class="mb-6" x-show="frequency === 'weekly'" x-cloak>
            <label class="block text-sm font-semibold text-gray-700 mb-3">Which day of the week?</label>
            <div class="flex gap-2">
                @foreach (['1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat', '7' => 'Sun'] as $num => $label)
                    <button
                        type="button"
                        @click="setDay({{ $num }})"
                        :class="day == {{ $num }}
                            ? 'border-purple-700 text-purple-700 bg-purple-50'
                            : 'border-gray-300 text-gray-700 hover:border-gray-400'"
                        class="flex-1 border rounded-lg px-2 py-3 text-xs font-semibold text-center transition"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            @error('schedule_day') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Day of month (monthly only) --}}
        <div class="mb-6" x-show="frequency === 'monthly'" x-cloak>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Which day of the month?</label>
            <select
                @change="setDay($event.target.value)"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
            >
                <option value="">— Select a day —</option>
                @for ($d = 1; $d <= 31; $d++)
                    <option value="{{ $d }}" {{ old('schedule_day', $list->schedule_day) == $d ? 'selected' : '' }}>
                        {{ $d }}{{ match(true) { $d===1||$d===21||$d===31=>'st', $d===2||$d===22=>'nd', $d===3||$d===23=>'rd', default=>'th' } }}
                    </option>
                @endfor
            </select>
            <p class="mt-2 text-xs text-gray-500">If a month has fewer days than selected, the list will run on the last day of that month.</p>
        </div>

        {{-- Time --}}
        <div class="mb-6">
            <label for="schedule_time" class="block text-sm font-semibold text-gray-700 mb-2">Time of day</label>
            <input
                type="time"
                id="schedule_time"
                name="schedule_time"
                value="{{ old('schedule_time', \Illuminate\Support\Str::substr($list->schedule_time, 0, 5)) }}"
                required
                class="border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('schedule_time') border-red-400 @enderror"
            >
            @error('schedule_time') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Output type --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-3">Delivery</label>
            <div class="flex gap-3">
                @foreach (['webpage' => 'Web page', 'email' => 'Email', 'wordpress' => 'WordPress'] as $value => $label)
                    <label class="flex-1 cursor-pointer">
                        <input
                            type="radio"
                            name="output_type"
                            value="{{ $value }}"
                            x-model="outputType"
                            {{ old('output_type', $list->output_type) === $value ? 'checked' : '' }}
                            class="sr-only peer"
                        >
                        <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                            {{ $label }}
                        </div>
                    </label>
                @endforeach
            </div>
            @error('output_type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Output destination (webpage only) --}}
        <div class="mb-6" x-show="outputType === 'webpage' || outputType === 'wordpress'" x-cloak>
            <label for="output_destination_id" class="block text-sm font-semibold text-gray-700 mb-2">Output Destination</label>
            @if ($destinations->isEmpty())
                <p class="text-sm text-gray-500">
                    No destinations available.
                    <a href="{{ route('output_destinations.create.step1') }}" class="text-purple-700 font-semibold hover:underline">Add one</a>.
                </p>
            @else
                <select
                    id="output_destination_id"
                    name="output_destination_id"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('output_destination_id') border-red-400 @enderror"
                >
                    <option value="">— Select a destination —</option>
                    @foreach ($destinations as $destination)
                        <option value="{{ $destination->id }}" {{ old('output_destination_id', $list->output_destination_id) == $destination->id ? 'selected' : '' }}>
                            {{ $destination->name }} ({{ $destination->host }})
                        </option>
                    @endforeach
                </select>
                @error('output_destination_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            @endif
        </div>

        {{-- Email notification (webpage only) --}}
        <div class="mb-6" x-show="outputType === 'webpage'" x-cloak>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Email notification when digest is published</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="notify_by_email" value="1"
                        {{ old('notify_by_email', $list->notify_by_email ? '1' : '0') === '1' ? 'checked' : '' }}
                        class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        Yes
                    </div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="notify_by_email" value="0"
                        {{ old('notify_by_email', $list->notify_by_email ? '1' : '0') === '0' ? 'checked' : '' }}
                        class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        No
                    </div>
                </label>
            </div>
        </div>

        {{-- Status --}}
        <div class="mb-8">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="1"
                        {{ old('enabled', $list->enabled ? '1' : '0') === '1' ? 'checked' : '' }}
                        class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        Enabled
                    </div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="0"
                        {{ old('enabled', $list->enabled ? '1' : '0') === '0' ? 'checked' : '' }}
                        class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        Disabled
                    </div>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('lists.delete.confirm', $list) }}" class="text-sm text-red-500 hover:text-red-700 font-medium transition">
                Delete this list
            </a>
            <div class="flex gap-3">
                <a href="{{ route('lists.index') }}" class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                    Cancel
                </a>
                <button
                    type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
                >
                    Save Changes
                </button>
            </div>
        </div>

    </form>

</x-layouts.app>