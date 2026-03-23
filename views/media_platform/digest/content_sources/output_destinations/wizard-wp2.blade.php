{{--
    ============================================================================
    FILE: views/lists/output_destinations/wizard-wp2.blade.php
    WordPress wizard — Step 2: Post settings
    ============================================================================
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto py-10">
    <h1 class="text-2xl font-bold text-purple-700 mb-1">Add WordPress Destination</h1>
    <p class="text-gray-500 text-sm mb-8">Step 2 of 3 — Post Settings</p>

    <form method="POST" action="{{ route('output_destinations.create.wp2.submit') }}">
        @csrf

        {{-- Post Status --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Post Status</label>
            <div class="space-y-2">
                @foreach(['publish' => 'Publish — post is immediately public', 'draft' => 'Draft — post is saved but not published', 'private' => 'Private — post is visible only to logged-in editors'] as $value => $label)
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="radio"
                            name="wordpress_post_status"
                            value="{{ $value }}"
                            @checked(old('wordpress_post_status', 'publish') === $value)
                            class="mt-0.5 accent-purple-700"
                        >
                        <span class="text-sm text-gray-700">
                            <strong>{{ ucfirst($value) }}</strong>
                            — {{ explode(' — ', $label)[1] }}
                        </span>
                    </label>
                @endforeach
            </div>
            @error('wordpress_post_status')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Category IDs --}}
        <div class="mb-6">
            <label for="wordpress_category_ids" class="block text-sm font-medium text-gray-700 mb-1">
                Category IDs <span class="text-gray-400 font-normal">(optional)</span>
            </label>
            <input
                type="text"
                id="wordpress_category_ids"
                name="wordpress_category_ids"
                value="{{ old('wordpress_category_ids') }}"
                placeholder="3, 7"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500"
            >
            <p class="text-xs text-gray-400 mt-1">
                Comma-separated WordPress category term IDs (the numeric IDs, not slugs).
                Find them in WordPress Admin → Posts → Categories → hover a category for its ID in the URL.
                Leave blank to use WordPress defaults.
            </p>
            @error('wordpress_category_ids')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Tag IDs --}}
        <div class="mb-6">
            <label for="wordpress_tag_ids" class="block text-sm font-medium text-gray-700 mb-1">
                Tag IDs <span class="text-gray-400 font-normal">(optional)</span>
            </label>
            <input
                type="text"
                id="wordpress_tag_ids"
                name="wordpress_tag_ids"
                value="{{ old('wordpress_tag_ids') }}"
                placeholder="12, 45"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500"
            >
            <p class="text-xs text-gray-400 mt-1">
                Comma-separated WordPress tag term IDs. Find them in WordPress Admin → Posts → Tags.
                Leave blank for no tag assignment.
            </p>
            @error('wordpress_tag_ids')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-purple-700 text-white text-sm font-medium px-5 py-2 rounded-md hover:bg-purple-800 transition">
                Continue →
            </button>
        </div>
    </form>
</div>
@endsection
