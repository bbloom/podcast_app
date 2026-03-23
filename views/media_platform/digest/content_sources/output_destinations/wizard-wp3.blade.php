{{--
    ============================================================================
    FILE: views/lists/output_destinations/wizard-wp3.blade.php
    WordPress wizard — Step 3: Test connection and confirm
    ============================================================================
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto py-10" x-data="{ tested: false, testing: false, success: false, message: '' }">
    <h1 class="text-2xl font-bold text-purple-700 mb-1">Add WordPress Destination</h1>
    <p class="text-gray-500 text-sm mb-8">Step 3 of 3 — Test &amp; Confirm</p>

    {{-- Summary of entered values --}}
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6 text-sm">
        <p><span class="text-gray-500">Name:</span> <strong>{{ $data['name'] }}</strong></p>
        <p><span class="text-gray-500">Site URL:</span> <strong>{{ $data['wordpress_url'] }}</strong></p>
        <p><span class="text-gray-500">Username:</span> <strong>{{ $data['wordpress_username'] }}</strong></p>
        <p><span class="text-gray-500">Post status:</span> <strong>{{ $data['wordpress_post_status'] ?? 'publish' }}</strong></p>
        @if(!empty($data['wordpress_category_ids']))
            <p><span class="text-gray-500">Category IDs:</span> <strong>{{ $data['wordpress_category_ids'] }}</strong></p>
        @endif
        @if(!empty($data['wordpress_tag_ids']))
            <p><span class="text-gray-500">Tag IDs:</span> <strong>{{ $data['wordpress_tag_ids'] }}</strong></p>
        @endif
    </div>

    {{-- Connection test --}}
    <div class="mb-6">
        <button
            type="button"
            @click="
                testing = true;
                tested  = false;
                fetch('{{ route('output_destinations.wizard.test_wordpress') }}', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                    body: JSON.stringify({})
                })
                .then(r => r.json())
                .then(data => {
                    testing = false;
                    tested  = true;
                    success = data.success;
                    message = data.message ?? (data.success ? 'Connection successful!' : 'Connection failed.');
                })
                .catch(() => {
                    testing = false;
                    tested  = true;
                    success = false;
                    message = 'An unexpected error occurred.';
                });
            "
            class="bg-white border border-purple-700 text-purple-700 text-sm font-medium px-4 py-2 rounded-md hover:bg-purple-50 transition"
            :disabled="testing"
        >
            <span x-show="!testing">Test WordPress Connection</span>
            <span x-show="testing">Testing…</span>
        </button>

        {{-- Result message --}}
        <div x-show="tested" class="mt-3 text-sm font-medium"
             :class="success ? 'text-green-600' : 'text-red-600'"
             x-text="message">
        </div>
    </div>

    {{-- Validation error from server-side (test not passed) --}}
    @error('test')
        <p class="text-red-500 text-sm mb-4">{{ $message }}</p>
    @enderror

    {{-- Save form — only submittable after test passes --}}
    <form method="POST" action="{{ route('output_destinations.create.wp3.submit') }}">
        @csrf
        <div class="flex justify-end">
            <button
                type="submit"
                class="bg-purple-700 text-white text-sm font-medium px-5 py-2 rounded-md hover:bg-purple-800 transition disabled:opacity-40 disabled:cursor-not-allowed"
                :disabled="!success"
            >
                Save Destination
            </button>
        </div>
    </form>
</div>
@endsection