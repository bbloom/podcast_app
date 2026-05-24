<x-layouts.app title="Finalize Script — Intro Template">
<div class="max-w-3xl mx-auto px-4 py-10">

    <x-podcasts.planning.finalize_script_wizard._step_dots :current="5" />

    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Finalize the Script Wizard</h1>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
            Step 5: {{ $hasIntro ? 'Review Intro Template' : 'Create Intro Template' }}
        </h1>
        <p class="text-base text-gray-500 mb-6">
            @if ($hasIntro)
                This is the raw intro template for <strong>{{ $episode->show->title }}</strong> — with placeholders, not yet resolved.
                <br>Most of the time you will continue without changes. Occasionally you may want to update the template permanently.
            @else
                <strong>{{ $episode->show->title }}</strong> does not have an intro template yet.
                <br>Please create one below before continuing.
            @endif
        </p>

        <div class="mt-4 flex flex-col items-center justify-center gap-3 text-3xl font-bold text-purple-700 bg-sky-100 border-2 border-sky-700 rounded-lg px-6 py-4 mb-8 shadow-sm">
            @if ($episode->show->itunes_image)
                <img src="{{ $episode->show->itunes_image }}"
                     alt="{{ $episode->show->title }}"
                     class="w-24 h-24 rounded object-cover border border-purple-200">
            @else
                {{ $episode->show->title ?? '' }}
            @endif
            episode #{{ $episode->episode_number }}
            <span class="mt-4">{{ $episode->title }}</span>
        </div>
    </div>

    @if (! $hasIntro)
        <div class="mb-6 p-4 bg-amber-50 border border-amber-300 rounded-lg text-base text-amber-800">
            <strong>No intro template found.</strong> The intro template is required for this show.
            Please write one below and save it to continue.
        </div>
    @else
        <div class="mb-6 p-4 bg-blue-50 border border-blue-300 rounded-lg text-base text-blue-800">
            Any changes you save here will be permanently stored on the show record
            and will apply to all future episodes.
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-300 text-red-700 rounded text-base">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('podcast_episodes_planning.wizard.finalize.step5.store') }}">
        @csrf

        <div class="mb-2">
            <label for="intro_template" class="block text-base font-semibold text-gray-700 mb-1">
                Intro Template <span class="font-normal text-gray-500">(raw — placeholders not yet resolved)</span>
            </label>
            <textarea id="intro_template" name="intro_template" rows="12"
                      class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-purple-500 focus:outline-none resize-y @error('intro_template') border-red-400 @enderror">{{ old('intro_template', $introTemplate) }}</textarea>
            <p class="mt-1 ml-1 text-xs text-gray-500">
                Available placeholders: <code>&#123;&#123;episode_number&#125;&#125;</code>,
                <code>&#123;&#123;title&#125;&#125;</code>,
                <code>&#123;&#123;sponsors&#125;&#125;</code>
            </p>
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('podcast_episodes_planning.wizard.finalize.step4') }}"
               class="text-sm text-gray-500 hover:underline">← Back</a>

            <div class="flex gap-3">
                @if ($hasIntro)
                    <button type="submit" name="_action" value="continue"
                            class="px-5 py-2 border border-gray-400 text-gray-700 rounded font-semibold text-sm hover:bg-gray-50">
                        Continue Without Saving →
                    </button>
                @endif
                <button type="submit" name="_action" value="save"
                        class="px-5 py-2 bg-purple-700 text-white rounded font-semibold text-sm hover:bg-purple-800">
                    {{ $hasIntro ? 'Save Changes to Show →' : 'Save Intro Template →' }}
                </button>
            </div>
        </div>

    </form>

</div>
</x-layouts.app>