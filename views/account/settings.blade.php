<x-layouts.app title="Account Settings">

    <h1 class="text-2xl font-bold mb-8">Account Settings</h1>

    {{-- Profile Information --}}
    <div class="mb-10">
        <h2 class="text-xl font-semibold mb-4">Profile Information</h2>

        @if (session('status') === 'profile-information-updated')
            <div class="mb-4 text-green-600">Profile updated successfully!</div>
        @endif

        @if ($errors->updateProfileInformation->any())
            <div class="mb-4 text-red-600">{{ $errors->updateProfileInformation->first() }}</div>
        @endif

        <form method="POST" action="/user/profile-information" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium">Name</label>
                <input type="text" name="name" value="{{ auth()->user()->name }}" required
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium">Email</label>
                <input type="email" name="email" value="{{ auth()->user()->email }}" required
                       class="w-full border rounded px-3 py-2">
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                Update Profile
            </button>
        </form>
    </div>

    <hr class="mb-10">

    {{-- Change Password --}}
    <div>
        <h2 class="text-xl font-semibold mb-4">Change Password</h2>

        @if (session('status') === 'password-updated')
            <div class="mb-4 text-green-600">Password updated successfully!</div>
        @endif

        @if ($errors->updatePassword->any())
            <div class="mb-4 text-red-600">{{ $errors->updatePassword->first() }}</div>
        @endif

        <form method="POST" action="/user/password" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium">Current Password</label>
                <div class="relative">
                    <input type="password" name="current_password" id="current_password" required
                           class="w-full border rounded px-3 py-2 pr-16">
                    <button type="button" onclick="togglePassword('current_password')"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-sm text-blue-600">Show</button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium">New Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required
                           class="w-full border rounded px-3 py-2 pr-16">
                    <button type="button" onclick="togglePassword('password')"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-sm text-blue-600">Show</button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium">Confirm New Password</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                           class="w-full border rounded px-3 py-2 pr-16">
                    <button type="button" onclick="togglePassword('password_confirmation')"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-sm text-blue-600">Show</button>
                </div>
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                Update Password
            </button>
        </form>
    </div>

    @push('scripts')
    <script>
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const btn = event.currentTarget;
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = 'Hide';
            } else {
                input.type = 'password';
                btn.textContent = 'Show';
            }
        }
    </script>
    @endpush

</x-layouts.app>