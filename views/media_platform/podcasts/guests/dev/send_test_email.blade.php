{{-- =============================================================================
     TEMPORARY DEV SCAFFOLDING — remove after Phase 6 proof-of-life.
     ============================================================================= --}}

<x-layouts.app title="Dev — Send Test Email">

    <div class="max-w-2xl mx-auto">

        <h1 class="text-3xl font-bold text-gray-900 mb-2">Dev — Send Test Email</h1>

        <p class="text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded px-4 py-3 mb-6">
            Temporary dev scaffolding. Remove after Phase 6 proof-of-life is complete.
        </p>

        @session('success')
            <div class="bg-green-50 border border-green-200 text-green-800 rounded px-4 py-3 mb-6 text-sm font-mono break-all">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('dev.guest-email-test.store') }}" class="space-y-6">
            @csrf

            {{-- Guest recipient --}}
            <div>
                <label for="podcast_guest_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Guest
                </label>
                <select
                    id="podcast_guest_id"
                    name="podcast_guest_id"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-base @error('podcast_guest_id') border-red-500 @enderror"
                >
                    <option value="">— Select a guest —</option>
                    @foreach ($guests as $guest)
                        <option value="{{ $guest->id }}" @selected(old('podcast_guest_id') == $guest->id)>
                            {{ $guest->full_name }} &lt;{{ $guest->email_address }}&gt;
                        </option>
                    @endforeach
                </select>
                @error('podcast_guest_id')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Subject --}}
            <div>
                <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">
                    Subject
                </label>
                <input
                    type="text"
                    id="subject"
                    name="subject"
                    value="{{ old('subject') }}"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-base @error('subject') border-red-500 @enderror"
                >
                @error('subject')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Body --}}
            <div>
                <label for="body" class="block text-sm font-medium text-gray-700 mb-1">
                    Body
                </label>
                <textarea
                    id="body"
                    name="body"
                    rows="8"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-base @error('body') border-red-500 @enderror"
                >{{ old('body') }}</textarea>
                @error('body')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="bg-purple-700 text-white text-sm font-medium px-5 py-2 rounded hover:bg-purple-800">
                Send Email
            </button>

        </form>
    </div>

</x-layouts.app>