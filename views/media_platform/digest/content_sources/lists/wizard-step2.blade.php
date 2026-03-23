<x-layouts.app title="Create a List">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Create a List</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 2</span>
            <span>of 6</span>
            <span class="mx-2">—</span>
            <span>Set a schedule</span>
        </div>
        @include('media_platform.digest.content_sources.lists._step_dots', ['current' => 2])
    </div>

    <form method="POST" action="{{ route('lists.create.step2.submit') }}"
          x-data="{
              frequency: '{{ old('schedule_frequency', 'daily') }}',
              day: {{ old('schedule_day', 'null') }},
              setDay(val) { this.day = val; }
          }">
        @csrf

        {{-- Single hidden input that holds the actual submitted value --}}
        <input type="hidden" name="schedule_day" :value="frequency === 'daily' ? '' : day">

        {{-- Frequency --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-3">How often should this list run?</label>
            <div class="flex gap-3">
                @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $value => $label)
                    <label class="flex-1 cursor-pointer">
                        <input
                            type="radio"
                            name="schedule_frequency"
                            value="{{ $value }}"
                            x-model="frequency"
                            @change="day = null"
                            {{ old('schedule_frequency', 'daily') === $value ? 'checked' : '' }}
                            class="sr-only peer"
                        >
                        <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                            {{ $label }}
                        </div>
                    </label>
                @endforeach
            </div>
            @error('schedule_frequency')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
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
            @error('schedule_day')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Day of month (monthly only) --}}
        <div class="mb-6" x-show="frequency === 'monthly'" x-cloak>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Which day of the month?</label>
            <select
                @change="setDay($event.target.value)"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
            >
                <option value="" disabled selected>— Select a day —</option>
                @for ($d = 1; $d <= 31; $d++)
                    <option value="{{ $d }}" {{ old('schedule_day') == $d ? 'selected' : '' }}>
                        {{ $d }}{{ match(true) { $d===1||$d===21||$d===31=>'st', $d===2||$d===22=>'nd', $d===3||$d===23=>'rd', default=>'th' } }}
                    </option>
                @endfor
            </select>
            <p class="mt-2 text-xs text-gray-500">If a month has fewer days than selected, the list will run on the last day of that month.</p>
            @error('schedule_day')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Time --}}
        <div class="mb-8">
            <label for="schedule_time" class="block text-sm font-semibold text-gray-700 mb-2">What time of day?</label>
            <input
                type="time"
                id="schedule_time"
                name="schedule_time"
                value="{{ old('schedule_time', '06:00') }}"
                required
                class="border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('schedule_time') border-red-400 @enderror"
            >
            @error('schedule_time')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">
                Times are in your
                @if (session('list_wizard.timezone'))
                    list timezone ({{ session('list_wizard.timezone') }}).
                @else
                    account timezone ({{ auth()->user()->timezone }}).
                @endif
            </p>
        </div>

        <div class="flex justify-between">
            <a href="{{ route('lists.create.step1') }}" class="text-sm text-gray-500 hover:text-gray-700 font-semibold py-3 transition">
                ← Back
            </a>
            <button
                type="submit"
                class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
            >
                Next Step...
            </button>
        </div>

    </form>

</x-layouts.app>