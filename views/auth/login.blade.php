<x-layouts.app title="Login">

    <div class="max-w-md mx-auto mt-16">

        <h1 class="text-2xl font-bold mb-4">Login</h1>

        @if ($errors->any())
            <div class="mb-4 text-red-600">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium">Email</label>
                <input type="email" name="email" required
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required
                        class="w-full border rounded px-3 py-2 pr-16">
                    <button type="button" onclick="togglePassword('password')"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-sm text-purple-700">
                        Show
                    </button>
                </div>
            </div>

            <button type="submit"
                    class="w-full bg-purple-700 text-white py-2 rounded hover:bg-purple-800">
                Login
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