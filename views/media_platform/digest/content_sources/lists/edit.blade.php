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
              outputType: '{{ old('output_type', $list->output_type->value ?? $list->output_type) }}',
              setDay(val) { this.day = val; }
          }">
        @csrf
        @method('PUT')

        {{-- Single hidden input for schedule_day --}}
        <input type="hidden" name="schedule_day" :value="frequency === 'daily' ? '' : day">

        {{-- Name --}}
        <div class="mb-6">
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Name</label>
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
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea
                id="description"
                name="description"
                rows="3"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('description') border-red-400 @enderror"
            >{{ old('description', $list->description) }}</textarea>
            @error('description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Timezone --}}
        <div class="mb-6">
            <label for="timezone" class="block text-sm font-semibold text-gray-700 mb-2">Timezone</label>
            <select
                id="timezone"
                name="timezone"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
            >
                @foreach (timezone_identifiers_list() as $tz)
                    <option value="{{ $tz }}" @selected(old('timezone', $list->timezone ?? auth()->user()->timezone) === $tz)>
                        {{ $tz }}
                    </option>
                @endforeach
            </select>
            @error('timezone') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Enabled --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Enabled</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="1"
                        {{ old('enabled', $list->enabled ? '1' : '0') === '1' ? 'checked' : '' }}
                        class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        Yes
                    </div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="0"
                        {{ old('enabled', $list->enabled ? '1' : '0') === '0' ? 'checked' : '' }}
                        class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">
                        No
                    </div>
                </label>
            </div>
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
                            x-on:change="frequency = '{{ $value }}'"
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

        {{-- Day of week (weekly) --}}
        <div class="mb-6" x-show="frequency === 'weekly'" x-cloak>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Day of week</label>
            <select x-on:change="setDay($event.target.value)"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                @foreach (['1'=>'Monday','2'=>'Tuesday','3'=>'Wednesday','4'=>'Thursday','5'=>'Friday','6'=>'Saturday','7'=>'Sunday'] as $val => $name)
                    <option value="{{ $val }}" @selected(old('schedule_day', $list->schedule_day) == $val)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Day of month (monthly) --}}
        <div class="mb-6" x-show="frequency === 'monthly'" x-cloak>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Day of month</label>
            <select x-on:change="setDay($event.target.value)"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                @for ($d = 1; $d <= 31; $d++)
                    <option value="{{ $d }}" @selected(old('schedule_day', $list->schedule_day) == $d)>
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
                @foreach (['webpage' => 'Web page', 'email' => 'Email', 'static_site' => 'Static Site'] as $value => $label)
                    <label class="flex-1 cursor-pointer">
                        <input
                            type="radio"
                            name="output_type"
                            value="{{ $value }}"
                            x-model="outputType"
                            {{ old('output_type', $list->output_type->value ?? $list->output_type) === $value ? 'checked' : '' }}
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
        <div class="mb-6" x-show="outputType === 'webpage'" x-cloak>
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

        {{-- Email notification (webpage or static_site) --}}
        <div class="mb-6" x-show="outputType === 'webpage' || outputType === 'static_site'" x-cloak>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Email notification when digest is published</label>
            <template x-if="outputType === 'static_site'">
                <p class="text-xs text-gray-500 mb-2">
                    This email confirms the digest was built and the deploy hook was fired. It does not contain the digest content.
                </p>
            </template>
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

        {{-- Retention count (all output types) --}}
        <div class="mb-6">
            <label for="retention_count" class="block text-sm font-semibold text-gray-700 mb-2">Digest retention</label>
            <input
                type="number"
                id="retention_count"
                name="retention_count"
                value="{{ old('retention_count', $list->retention_count ?? 10) }}"
                min="1"
                max="100"
                class="w-32 border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('retention_count') border-red-400 @enderror"
            >
            <div class="mt-2 text-xs text-gray-500" x-show="outputType === 'static_site'" x-cloak>
                How many published digests to keep. Your static site will display this many digests. Older records are pruned automatically after each run.
            </div>
            <div class="mt-2 text-xs text-gray-500" x-show="outputType === 'email' || outputType === 'webpage'" x-cloak>
                How many digest runs to keep in the database after delivery. Older delivered summaries are pruned automatically after each run. The delivered emails or uploaded files are not affected.
            </div>
            @error('retention_count') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        
        {{-- Deploy hooks info (static_site only) --}}
        <div class="mb-6" x-show="outputType === 'static_site'" x-cloak>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Deploy Hooks</label>
            @if ($list->output_type === \MediaPlatform\Digest\Enums\OutputType::StaticSite && $list->deployHooks->count() > 0)
                <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 mb-2">
                    @foreach ($list->deployHooks as $hook)
                        <div class="flex items-center justify-between px-4 py-2.5">
                            <div>
                                <p class="text-sm text-gray-800 font-medium">{{ $hook->label }}</p>
                                <p class="text-xs text-gray-500">{{ $hook->provider->label() }}</p>
                            </div>
                            @if ($hook->enabled)
                                <span class="text-xs bg-green-100 text-green-700 font-semibold px-2 py-0.5 rounded-full">Enabled</span>
                            @else
                                <span class="text-xs bg-gray-100 text-gray-500 font-semibold px-2 py-0.5 rounded-full">Disabled</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 mb-2">No deploy hooks attached to this list yet.</p>
            @endif
            <a href="{{ route('deploy_hooks.create', ['triggerable_type' => 'digest_list', 'triggerable_id' => $list->id]) }}"
               class="text-sm text-purple-700 hover:underline font-semibold">
                Manage deploy hooks →
            </a>
        </div>

        {{-- Submit --}}
        <div class="flex items-center gap-4 mt-8">
            <button
                type="submit"
                class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
            >
                Save Changes
            </button>
            <a href="{{ route('lists.show', $list) }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold transition">
                Cancel
            </a>
        </div>

    </form>

</x-layouts.app>