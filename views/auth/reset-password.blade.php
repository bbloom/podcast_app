<x-layouts.app title="Reset Password">

    <h1 class="text-2xl font-bold mb-4">Reset Password</h1>

    @if ($errors->any())
        <div class="mb-4 text-red-600 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label class="block text-sm font-medium">Email</label>
            <input type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus
                   class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium">New Password</label>
            <div class="relative">
                <input type="password" name="password" id="password" required
                       class="w-full border rounded px-3 py-2 pr-16">
                <button type="button" onclick="togglePassword('password')"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-sm text-blue-600">
                    Show
                </button>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium">Confirm New Password</label>
            <div class="relative">
                <input type="password" name="password_confirmation" id="password_confirmation" required
                       class="w-full border rounded px-3 py-2 pr-16">
                <button type="button" onclick="togglePassword('password_confirmation')"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-sm text-blue-600">
                    Show
                </button>
            </div>
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
            Reset Password
        </button>

    </form>

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
