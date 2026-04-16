<x-layouts.app title="Delete Deploy Hook">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <a href="{{ route('post_production.dashboard') }}" class="hover:text-purple-700 transition">Post-Production</a>
        <span>›</span>
        <a href="{{ route('deploy_hooks.index') }}" class="hover:text-purple-700 transition">Deploy Hooks</a>
        <span>›</span>
        <a href="{{ route('deploy_hooks.show', $hook) }}" class="hover:text-purple-700 transition">{{ $hook->label }}</a>
        <span>›</span>
        <span class="text-gray-700">Delete</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-8">Delete Deploy Hook</h1>

    <div class="border border-red-300 rounded-lg overflow-hidden mb-8">
        <div class="px-4 py-3 bg-red-50 border-b border-red-300">
            <h2 class="text-sm font-semibold text-red-700 uppercase tracking-wider">Confirm Deletion</h2>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-700 mb-2">
                You are about to permanently delete the deploy hook
                <strong class="text-gray-900">{{ $hook->label }}</strong>
                for <strong class="text-gray-900">{{ $hook->triggerable->title }}</strong>.
            </p>
            <p class="text-sm text-gray-700">
                This action cannot be undone. The hook URL will be deleted and builds will no longer be triggered for this hook.
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('deploy_hooks.destroy', $hook) }}">
        @csrf
        @method('DELETE')
        <div class="flex items-center gap-4">
            <button type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                Yes, Delete Hook
            </button>
            <a href="{{ route('deploy_hooks.show', $hook) }}"
               class="text-sm text-gray-500 hover:text-gray-700 transition">Cancel</a>
        </div>
    </form>

</x-layouts.app>