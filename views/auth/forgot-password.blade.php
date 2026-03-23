<x-layouts.app title="Forgot Password">

    <h1 class="text-2xl font-bold mb-4">Forgot Password</h1>

    <p class="text-sm text-gray-600 mb-6">
        Enter your email address and we'll send you a link to reset your password.
    </p>

    @if (session('status'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-300 text-green-800 rounded text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 text-red-600 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full border rounded px-3 py-2">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
            Send Reset Link
        </button>

        <div class="text-center text-sm">
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Back to Login</a>
        </div>

    </form>

</x-layouts.app>
