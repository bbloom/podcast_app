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

            <div x-data="{ show: false }" class="relative">
                <input :type="show ? 'text' : 'password'" 
                    name="password" 
                    id="password" 
                    required
                    class="w-full border rounded px-3 py-2 pr-16">
                    
                <button type="button" 
                        @click="show = !show"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-purple-700">
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12.001a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>

                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>

            <button type="submit"
                    class="w-full bg-purple-700 text-white py-2 rounded hover:bg-purple-800">
                Login
            </button>
        </form>

    </div>

    @push('scripts')
    <script>
        function togglePassword(fieldId, btn) {
            const input = document.getElementById(fieldId);
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