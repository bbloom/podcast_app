<x-layouts.app title="Add Youtube Channel Wizard">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Add a Youtube Channel</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 4</span>
            <span>of 5</span>
            <span class="mx-2">—</span>
            <span>Assign to a list</span>
        </div>

        {{-- Step dots --}}
        <div class="flex items-center gap-2 mt-3">
            <div class="w-3 h-3 rounded-full bg-purple-300"></div>
            <div class="w-16 h-px bg-purple-300"></div>
            <div class="w-3 h-3 rounded-full bg-purple-300"></div>
            <div class="w-16 h-px bg-purple-300"></div>
            <div class="w-3 h-3 rounded-full bg-purple-300"></div>
            <div class="w-16 h-px bg-purple-300"></div>
            <div class="w-3 h-3 rounded-full bg-purple-700"></div>
            <div class="w-16 h-px bg-gray-300"></div>
            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
        </div>
    </div>

    <form method="POST" action="{{ route('youtube.channels.create.step4.submit') }}"
          x-data="{
              listStates: {
                  @foreach ($lists as $list)
                      {{ $list->id }}: {
                          checked: {{ in_array($list->id, old('list_ids', [])) ? 'true' : 'false' }},
                          mode: '{{ old("processing_modes.{$list->id}", 'description') }}',
                          terms: '{{ old("search_terms.{$list->id}", '') }}'
                      },
                  @endforeach
              }
          }">
        @csrf

        <div class="mb-8">
            <label class="block text-sm font-semibold text-gray-700 mb-3">
                Which lists should <span class="text-purple-700">{{ $channelTitle }}</span> be added to?
            </label>

            @error('list_ids')
                <p class="mb-3 text-sm text-red-600">{{ $message }}</p>
            @enderror

            @if ($lists->isEmpty())

                <div class="bg-amber-50 border border-amber-300 rounded-lg p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <div class="text-sm text-amber-800">
                            <p class="font-semibold mb-1">You don't have any lists yet</p>
                            <p>You need at least one list to assign this channel to. Create a list now and you'll be brought straight back here to continue.</p>
                            <a
                                href="{{ route('lists.create.step1', ['redirect_to' => 'youtube.channels.create.step4']) }}"
                                class="inline-block mt-3 bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition"
                            >
                                Create a list →
                            </a>
                        </div>
                    </div>
                </div>

            @else

                <div class="flex flex-col gap-3">
                    @foreach ($lists as $list)
                        <div class="border border-gray-300 rounded-lg px-4 py-3 hover:border-gray-400 transition"
                             :class="listStates[{{ $list->id }}].checked ? 'border-purple-300 bg-purple-50/30' : ''">

                            <label class="flex items-start gap-4 cursor-pointer">
                                <div class="flex-shrink-0 mt-0.5">
                                    <input
                                        type="checkbox"
                                        name="list_ids[]"
                                        value="{{ $list->id }}"
                                        x-model="listStates[{{ $list->id }}].checked"
                                        class="w-5 h-5 accent-purple-700 cursor-pointer rounded"
                                    >
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-800">{{ $list->name }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        @if ($list->schedule_frequency === 'daily')
                                            Daily at {{ $list->schedule_time }}
                                        @elseif ($list->schedule_frequency === 'weekly')
                                            @php $dayNames = ['1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat','7'=>'Sun']; @endphp
                                            Weekly · {{ $dayNames[$list->schedule_day] ?? '' }} at {{ $list->schedule_time }}
                                        @elseif ($list->schedule_frequency === 'monthly')
                                            Monthly · {{ $list->schedule_day }}{{ match(true) { $list->schedule_day==1||$list->schedule_day==21||$list->schedule_day==31=>'st', $list->schedule_day==2||$list->schedule_day==22=>'nd', $list->schedule_day==3||$list->schedule_day==23=>'rd', default=>'th' } }} at {{ $list->schedule_time }}
                                        @endif
                                        <span class="mx-1">·</span>
                                        {{ $list->output_type === 'email' ? 'Email' : 'Web page' }}
                                    </p>
                                </div>
                            </label>

                            @include('components.processing-mode-selector', ['list' => $list])

                        </div>
                    @endforeach
                </div>

            @endif
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('youtube.channels.create.step3') }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold py-3 transition">
                ← Back
            </a>

            @if (! $lists->isEmpty())
                <button
                    type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
                >
                    Next Step...
                </button>
            @endif
        </div>

    </form>

</x-layouts.app>
