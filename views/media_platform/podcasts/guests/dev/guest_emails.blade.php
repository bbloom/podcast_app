{{-- =============================================================================
     TEMPORARY DEV SCAFFOLDING — remove after Phase 6 proof-of-life.

     Displays all guest_emails rows for Phase 6 end-to-end verification.
     Reload this page after each test step to confirm the database state.
     ============================================================================= --}}

<x-layouts.app title="Dev — Guest Emails">

    <div class="max-w-7xl mx-auto">

        <div class="flex items-baseline justify-between mb-2">
            <h1 class="text-3xl font-bold text-gray-900">Dev — Guest Emails</h1>
            <span class="text-sm text-gray-500">{{ $emails->count() }} {{ Str::plural('row', $emails->count()) }} &mdash; newest first</span>
        </div>

        <p class="text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded px-4 py-3 mb-6">
            Temporary dev scaffolding. Remove after Phase 6 proof-of-life is complete.
            &mdash; <a href="{{ route('dev.guest-email-test.create') }}" class="underline">Go to send form</a>
        </p>

        @if ($emails->isEmpty())
            <p class="text-gray-500 text-sm">No rows yet.</p>
        @else

            <div class="space-y-6">
                @foreach ($emails as $email)

                    {{-- Row card --}}
                    <div class="border rounded-lg overflow-hidden
                        @if ($email->direction->value === 'outbound')     border-blue-200   bg-blue-50
                        @elseif ($email->direction->value === 'inbound')  border-green-200  bg-green-50
                        @elseif ($email->direction->value === 'hard_bounce') border-red-300 bg-red-50
                        @else                                             border-gray-200   bg-white
                        @endif
                    ">

                        {{-- Card header --}}
                        <div class="flex items-center gap-4 px-4 py-3 border-b
                            @if ($email->direction->value === 'outbound')     border-blue-200  bg-blue-100
                            @elseif ($email->direction->value === 'inbound')  border-green-200 bg-green-100
                            @elseif ($email->direction->value === 'hard_bounce') border-red-300 bg-red-100
                            @else                                             border-gray-200  bg-gray-50
                            @endif
                        ">
                            <span class="text-xs font-bold uppercase tracking-wide
                                @if ($email->direction->value === 'outbound')        text-blue-700
                                @elseif ($email->direction->value === 'inbound')     text-green-700
                                @elseif ($email->direction->value === 'hard_bounce') text-red-700
                                @else                                                text-gray-600
                                @endif
                            ">
                                {{ $email->direction->value }}
                            </span>

                            <span class="text-sm font-medium text-gray-900">
                                {{ $email->guest?->full_name ?? '(unknown guest)' }}
                                @if ($email->guest)
                                    <span class="font-normal text-gray-500">&lt;{{ $email->guest->email_address }}&gt;</span>
                                @endif
                            </span>

                            <span class="ml-auto text-xs text-gray-400 font-mono">
                                id={{ $email->id }}
                                &bull;
                                guest_id={{ $email->podcast_guest_id }}
                            </span>
                        </div>

                        {{-- Card body --}}
                        <div class="px-4 py-4 space-y-3 text-sm">

                            {{-- Subject --}}
                            <div>
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Subject</span>
                                <p class="mt-0.5 text-gray-900">{{ $email->subject ?: '(empty)' }}</p>
                            </div>

                            {{-- message_id --}}
                            <div>
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">message_id</span>
                                <p class="mt-0.5 font-mono text-xs text-gray-700 break-all">{{ $email->message_id ?: '(empty)' }}</p>
                            </div>

                            {{-- in_reply_to --}}
                            <div>
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">in_reply_to</span>
                                @if ($email->in_reply_to)
                                    <p class="mt-0.5 font-mono text-xs text-gray-700 break-all">{{ $email->in_reply_to }}</p>

                                    {{-- Thread correlation check --}}
                                    @php
                                        $matched = $emails->first(fn($e) => $e->message_id === $email->in_reply_to);
                                    @endphp
                                    @if ($matched)
                                        <p class="mt-1 text-xs text-green-700">
                                            ✓ Matched outbound row id={{ $matched->id }}
                                        </p>
                                    @else
                                        <p class="mt-1 text-xs text-red-600">
                                            ✗ No outbound row found with this message_id
                                        </p>
                                    @endif
                                @else
                                    <p class="mt-0.5 text-gray-400 text-xs">null</p>
                                @endif
                            </div>

                            {{-- body_stripped --}}
                            <div>
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">body_stripped</span>
                                <pre class="mt-0.5 text-xs text-gray-800 whitespace-pre-wrap break-all bg-white border border-gray-200 rounded p-3 font-mono">{{ $email->body_stripped ?: '(empty)' }}</pre>
                            </div>

                            {{-- body_full (collapsed) --}}
                            <details class="text-xs">
                                <summary class="cursor-pointer text-gray-500 hover:text-gray-700 select-none">
                                    body_full (click to expand)
                                </summary>
                                <pre class="mt-2 text-xs text-gray-700 whitespace-pre-wrap break-all bg-white border border-gray-200 rounded p-3 font-mono">{{ $email->body_full ?: '(empty)' }}</pre>
                            </details>

                            {{-- Timestamps --}}
                            <div class="flex gap-8 pt-1 border-t border-gray-200">
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">sent_at</span>
                                    <p class="mt-0.5 text-xs text-gray-700 font-mono">{{ $email->sent_at?->toDateTimeString() ?? 'null' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">received_at</span>
                                    <p class="mt-0.5 text-xs text-gray-700 font-mono">{{ $email->received_at?->toDateTimeString() ?? 'null' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">created_at</span>
                                    <p class="mt-0.5 text-xs text-gray-700 font-mono">{{ $email->created_at->toDateTimeString() }}</p>
                                </div>
                            </div>

                            {{-- Bounce flag on guest (only relevant for hard_bounce rows) --}}
                            @if ($email->direction->value === 'hard_bounce' && $email->guest)
                                <div class="pt-1 border-t border-red-200">
                                    <span class="text-xs font-semibold text-red-600 uppercase tracking-wide">Guest bounce flag</span>
                                    <p class="mt-0.5 text-xs font-mono">
                                        email_bounced = {{ $email->guest->email_bounced ? 'true' : 'false' }}
                                        &bull;
                                        email_bounced_at = {{ $email->guest->email_bounced_at?->toDateTimeString() ?? 'null' }}
                                    </p>
                                    @if (! $email->guest->email_bounced)
                                        <p class="mt-1 text-xs text-red-600">✗ Guest record was NOT flagged — check handleBounce()</p>
                                    @else
                                        <p class="mt-1 text-xs text-green-700">✓ Guest record flagged correctly</p>
                                    @endif
                                </div>
                            @endif

                        </div>{{-- /card body --}}

                    </div>{{-- /card --}}

                @endforeach
            </div>

        @endif

    </div>

</x-layouts.app>