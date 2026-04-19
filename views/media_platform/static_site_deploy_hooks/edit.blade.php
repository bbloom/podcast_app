<x-layouts.app title="Edit {{ $hook->label }}">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <a href="{{ route('deploy_hooks.index') }}" class="hover:text-purple-700 transition">Deploy Hooks</a>
        <span>›</span>
        <a href="{{ route('deploy_hooks.show', $hook) }}" class="hover:text-purple-700 transition">{{ $hook->label }}</a>
        <span>›</span>
        <span class="text-gray-700">Edit</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-8">Edit Deploy Hook</h1>

    <div class="border border-purple-300 rounded-lg overflow-hidden">
        <div class="px-4 py-3 bg-purple-50 border-b border-purple-300">
            <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider">Hook Details</h2>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('deploy_hooks.update', $hook) }}"
                  x-data="{
                      triggerableType: '{{ old('triggerable_type', $hook->triggerable_type) }}'
                  }">
                @csrf
                @method('PUT')

                {{-- Triggerable Type --}}
                <div class="mb-6">
                    <label for="triggerable_type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select id="triggerable_type" name="triggerable_type"
                            x-model="triggerableType"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="podcast_show">Podcast Show</option>
                        <option value="digest_list">Digest List</option>
                    </select>
                    @error('triggerable_type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Podcast Show (visible when type is podcast_show) --}}
                <div class="mb-6" x-show="triggerableType === 'podcast_show'" x-cloak>
                    <label for="triggerable_id_show" class="block text-sm font-medium text-gray-700 mb-1">Podcast Show</label>
                    <select id="triggerable_id_show" name="triggerable_id"
                            x-bind:disabled="triggerableType !== 'podcast_show'"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        @foreach ($shows as $show)
                            <option value="{{ $show->id }}"
                                @selected(old('triggerable_id', $hook->triggerable_type === 'podcast_show' ? $hook->triggerable_id : '') == $show->id)>
                                {{ $show->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Digest List (visible when type is digest_list) --}}
                <div class="mb-6" x-show="triggerableType === 'digest_list'" x-cloak>
                    <label for="triggerable_id_list" class="block text-sm font-medium text-gray-700 mb-1">Digest List</label>
                    <select id="triggerable_id_list" name="triggerable_id"
                            x-bind:disabled="triggerableType !== 'digest_list'"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">— Select a list —</option>
                        @foreach ($lists as $list)
                            <option value="{{ $list->id }}"
                                @selected(old('triggerable_id', $hook->triggerable_type === 'digest_list' ? $hook->triggerable_id : '') == $list->id)>
                                {{ $list->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @error('triggerable_id')
                    <p class="mb-4 text-xs text-red-600">{{ $message }}</p>
                @enderror

                {{-- Label --}}
                <div class="mb-6">
                    <label for="label" class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                    <input type="text" id="label" name="label" value="{{ old('label', $hook->label) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-400 list-disc list-outside pl-5">
                        <li>A human-readable name to identify this hook at a glance.</li>
                        <li>Recommended format: <span class="font-mono">Show or List Name — Provider — Environment</span></li>
                    </ul>
                    @error('label')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Provider --}}
                <div class="mb-6">
                    <label for="provider" class="block text-sm font-medium text-gray-700 mb-1">Provider</label>
                    <select id="provider" name="provider"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        @foreach ($providers as $provider)
                            <option value="{{ $provider->value }}"
                                @selected(old('provider', $hook->provider->value) === $provider->value)>
                                {{ $provider->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('provider')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- URL --}}
                <div class="mb-6">
                    <label for="url" class="block text-sm font-medium text-gray-700 mb-1">Deploy Hook URL</label>
                    <input type="url" id="url" name="url" value="{{ old('url') }}"
                           placeholder="Leave blank to keep the existing URL unchanged"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-400 list-disc list-outside pl-5">
                        <li>Leave blank to keep the existing URL — it cannot be displayed as it is stored encrypted.</li>
                        <li>Enter a new URL only if you need to replace it.</li>
                    </ul>
                    @error('url')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Enabled --}}
                <div class="mb-8">
                    <label for="enabled" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="enabled" name="enabled"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="1" @selected(old('enabled', $hook->enabled ? '1' : '0') == '1')>Enabled</option>
                        <option value="0" @selected(old('enabled', $hook->enabled ? '1' : '0') == '0')>Disabled</option>
                    </select>
                    @error('enabled')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit"
                            class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                        Save Changes
                    </button>
                    <a href="{{ route('deploy_hooks.show', $hook) }}"
                       class="text-sm text-gray-500 hover:text-gray-700 transition">Cancel</a>
                </div>

            </form>
        </div>
    </div>

</x-layouts.app>