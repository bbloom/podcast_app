<x-layouts.app title="Flush Failed Jobs">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <a href="{{ route('admin.health-checks.index') }}" class="hover:text-purple-700 transition">Health Checks</a>
        <span>›</span>
        <span class="text-gray-700">Flush Failed Jobs</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-8">Flush Failed Jobs</h1>

    <div class="border border-amber-400 rounded-lg p-6 mb-8">
        <p class="text-sm text-gray-700 mb-2">
            There {{ $count === 1 ? 'is' : 'are' }} currently
            <span class="font-bold text-amber-700">{{ $count }} failed {{ str('job')->plural($count) }}</span>
            in the queue.
        </p>
        <p class="text-sm text-gray-700 mb-2">
            Flushing will <strong>permanently delete</strong> all failed jobs from the queue.
            This action cannot be undone.
        </p>
        <p class="text-sm text-gray-700">
            The related health check alert will be resolved immediately.
        </p>
    </div>

    <form method="POST" action="{{ route('admin.health-checks.failed-jobs.flush') }}">
        @csrf
        <div class="flex items-center gap-4">
            <button type="submit"
                    class="bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                Yes, Flush Failed Jobs
            </button>
            <a href="{{ route('admin.health-checks.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 transition">Cancel</a>
        </div>
    </form>

</x-layouts.app>