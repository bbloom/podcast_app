{{--
    Step dots for the Generate RSS Feed wizard.
    Each wizard has its own dedicated _step_dots partial — never shared.

    Steps:
      1 — Review Episode
      2 — Validate
      3 — Generate & Stage
      4 — External Validators
      5 — Promote to Live

    Usage: @include('...._step_dots', ['currentStep' => 1])
--}}

<div class="flex items-center gap-2 mb-8">
    @foreach ([1 => 'Review', 2 => 'Validate', 3 => 'Generate', 4 => 'Validate Externally', 5 => 'Promote'] as $step => $label)
        <div class="flex items-center gap-2">
            <div class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold
                {{ $currentStep === $step ? 'bg-purple-700 text-white' : ($currentStep > $step ? 'bg-purple-300 text-white' : 'bg-gray-200 text-gray-500') }}">
                {{ $step }}
            </div>
            <span class="text-xs {{ $currentStep === $step ? 'text-purple-700 font-semibold' : 'text-gray-400' }}">
                {{ $label }}
            </span>
        </div>
        @if ($step < 5)
            <div class="flex-1 h-px bg-gray-200 mx-1"></div>
        @endif
    @endforeach
</div>