
<footer class="border-t border-gray-200 mt-10 p-6 text-center text-sm text-gray-200 bg-gray-700">
    &copy; {{ date('Y') }} {{ config('app.name') }} 
    <br>All rights reserved.

    @if (config('app.env') != 'production')
        <br><br><span class="text-yellow-700 rounded">{{ strtoupper(config('app.env')) }}</span> environment
    @endif
</footer>