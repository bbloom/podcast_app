<x-layouts.app title="Profile">

    <h1 class="text-2xl font-bold mb-6">Profile</h1>

    @if (session('status') === 'profile-updated')
        <div class="mb-5 px-4 py-3 bg-green-50 border border-green-300 text-green-800 rounded text-sm">
            Profile updated successfully.
        </div>
    @endif

    {{-- Update profile information --}}
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Profile Information</h2>

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4 max-w-xl">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-sm font-medium">Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full border rounded px-3 py-2 @error('name') border-red-400 @enderror">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="w-full border rounded px-3 py-2 @error('email') border-red-400 @enderror">
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                Save Changes
            </button>

        </form>
    </div>

    <hr class="border-gray-200 mb-8">

    {{-- Update password --}}
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Update Password</h2>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4 max-w-xl">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium">Current Password</label>
                <div class="relative">
                    <input type="password" name="current_password" id="current_password"
                           class="w-full border rounded px-3 py-2 pr-16 @error('current_password', 'updatePassword') border-red-400 @enderror">
                    <button type="button" onclick="togglePassword('current_password')"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-sm text-blue-600">Show</button>
                </div>
                @error('current_password', 'updatePassword')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium">New Password</label>
                <div class="relative">
                    <input type="password" name="password" id="new_password"
                           class="w-full border rounded px-3 py-2 pr-16 @error('password', 'updatePassword') border-red-400 @enderror">
                    <button type="button" onclick="togglePassword('new_password')"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-sm text-blue-600">Show</button>
                </div>
                @error('password', 'updatePassword')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium">Confirm New Password</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="w-full border rounded px-3 py-2 pr-16">
                    <button type="button" onclick="togglePassword('password_confirmation')"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-sm text-blue-600">Show</button>
                </div>
            </div>

            <button type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                Update Password
            </button>

        </form>
    </div>

    <hr class="border-gray-200 mb-8">

    {{-- Delete account --}}
    <div>
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Delete Account</h2>
        <p class="text-sm text-gray-600 mb-4">
            Once your account is deleted, all of its resources and data will be permanently deleted.
        </p>

        <button onclick="document.getElementById('deleteModal').classList.remove('hidden')"
                class="bg-red-100 hover:bg-red-200 text-red-700 text-sm font-medium px-4 py-2 rounded">
            Delete Account
        </button>
    </div>

    {{-- Delete confirm modal --}}
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
            <h2 class="text-lg font-bold text-gray-800 mb-2">Delete Account?</h2>
            <p class="text-sm text-gray-600 mb-4">
                Are you sure you want to delete your account? This cannot be undone.
            </p>

            <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-4">
                @csrf
                @method('DELETE')

                <div>
                    <label class="block text-sm font-medium">Confirm your password</label>
                    <input type="password" name="password" required
                           class="w-full border rounded px-3 py-2">
                    @error('password', 'userDeletion')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button"
                            onclick="document.getElementById('deleteModal').classList.add('hidden')"
                            class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-4 py-2 rounded">
                        Cancel
                    </button>
                    <button type="submit"
                            class="text-sm bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded">
                        Yes, Delete Account
                    </button>
                </div>

            </form>
        </div>
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
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });
    </script>
    @endpush

</x-layouts.app>
