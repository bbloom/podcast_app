{{--
    ============================================================================
    FILE: views/lists/output_destinations/wizard-wp1.blade.php
    WordPress wizard — Step 1: Site URL and credentials
    ============================================================================
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto py-10">
    <h1 class="text-2xl font-bold text-purple-700 mb-1">Add WordPress Destination</h1>
    <p class="text-gray-500 text-sm mb-8">Step 1 of 3 — Site URL &amp; Credentials</p>

    <form method="POST" action="{{ route('output_destinations.create.wp1.submit') }}">
        @csrf

        {{-- WordPress Site URL --}}
        <div class="mb-6">
            <label for="wordpress_url" class="block text-sm font-medium text-gray-700 mb-1">
                WordPress Site URL
            </label>
            <input
                type="url"
                id="wordpress_url"
                name="wordpress_url"
                value="{{ old('wordpress_url') }}"
                placeholder="https://mysite.com"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('wordpress_url') border-red-400 @enderror"
                required
            >
            <p class="text-xs text-gray-400 mt-1">The root URL of your WordPress site — not a page URL, just the homepage.</p>
            @error('wordpress_url')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- WordPress Username --}}
        <div class="mb-6">
            <label for="wordpress_username" class="block text-sm font-medium text-gray-700 mb-1">
                WordPress Username
            </label>
            <input
                type="text"
                id="wordpress_username"
                name="wordpress_username"
                value="{{ old('wordpress_username') }}"
                placeholder="admin"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('wordpress_username') border-red-400 @enderror"
                required
            >
            <p class="text-xs text-gray-400 mt-1">Your WordPress login username (not your email address).</p>
            @error('wordpress_username')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Application Password --}}
        <div class="mb-6">
            <label for="wordpress_app_password" class="block text-sm font-medium text-gray-700 mb-1">
                Application Password
            </label>
            <input
                type="password"
                id="wordpress_app_password"
                name="wordpress_app_password"
                placeholder="xxxx xxxx xxxx xxxx xxxx xxxx"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500 @error('wordpress_app_password') border-red-400 @enderror"
                required
                autocomplete="new-password"
            >
            <p class="text-xs text-gray-400 mt-1">
                Generate one in WordPress Admin → Users → Edit User → Application Passwords → Add New.
                This is NOT your login password.
            </p>
            @error('wordpress_app_password')
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
