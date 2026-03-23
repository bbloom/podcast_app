<x-layouts.app title="Verify Email">

    <h1 class="text-2xl font-bold mb-4">Verify Your Email</h1>

    <p class="text-sm text-gray-600 mb-6">
        Thanks for signing up! Before getting started, please verify your email address
        by clicking the link we just emailed to you. If you didn't receive the email,
        we'll gladly send you another.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-300 text-green-800 rounded text-sm">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <div class="space-y-4">

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                    class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded">
                Logout
            </button>
        </form>

    </div>

</x-layouts.app>
