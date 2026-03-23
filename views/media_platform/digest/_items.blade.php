{{--
    views/digests/_items.blade.php
    ─────────────────────────────────────────────────────────────────────────────
    SHARED DIGEST CONTENT PARTIAL

    This is the single source of truth for digest content rendering. It is
    @include'd by all three digest wrappers:
      - digest-email.blade.php   (email shell)
      - digest-webpage.blade.php (standalone HTML page)
      - digest-wp.blade.php      (WordPress post body)

    VARIABLES EXPECTED (passed down from the parent view):
      $digestData['groups']      — Collection of source groups from DigestBuilderService
      $digestData['total_items'] — Total number of items in this digest
      $digestData['date']        — Carbon date of this digest run

    DESIGN PRINCIPLES
    ─────────────────
    - Scannable, not readable. Each item leads with the title (linked) and
      date, followed by a short summary. No walls of text.
    - Source-grouped. Items are grouped under their source name so the reader
      can jump to or skip a whole source at a glance.
    - Inline CSS only. Required for email clients. All styling is inline so
      the partial works identically in email and web contexts.
    - Source type badge. A subtle pill badge shows YouTube / Podcast / Article
      so the reader knows what kind of content they are looking at.
--}}

{{-- ── Outer container — max-width keeps lines comfortable for reading ──── --}}
<div style="max-width:680px; margin:0 auto; font-family:Georgia,'Times New Roman',serif; color:#1a1a1a;">

    {{-- ── Iterate over source groups ─────────────────────────────────────── --}}
    @foreach($digestData['groups'] as $group)

        {{-- ── Source group header ──────────────────────────────────────────── --}}
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:8px;">
            <tr>
                <td style="
                    padding: 10px 16px;
                    background-color: #f3f0ff;
                    border-left: 4px solid #7c3aed;
                    border-radius: 0 4px 4px 0;
                ">
                    {{-- Source type badge (YouTube / Podcast / Article) --}}
                    <span style="
                        display: inline-block;
                        font-size: 10px;
                        font-family: Arial, sans-serif;
                        font-weight: bold;
                        letter-spacing: 0.05em;
                        text-transform: uppercase;
                        color: #7c3aed;
                        background: #ede9fe;
                        border: 1px solid #c4b5fd;
                        border-radius: 3px;
                        padding: 2px 7px;
                        margin-right: 8px;
                        vertical-align: middle;
                    ">
                        @switch($group['source_type'])
                            @case('youtube_channel') YouTube @break
                            @case('podcast')         Podcast @break
                            @default                 Article
                        @endswitch
                    </span>

                    {{-- Source name --}}
                    <span style="
                        font-family: Arial, sans-serif;
                        font-size: 15px;
                        font-weight: bold;
                        color: #3b0764;
                        vertical-align: middle;
                    ">{{ $group['source_name'] }}</span>

                    {{-- Item count --}}
                    <span style="
                        font-family: Arial, sans-serif;
                        font-size: 12px;
                        color: #6b7280;
                        margin-left: 8px;
                        vertical-align: middle;
                    ">
                        {{ $group['items']->count() }}
                        {{ $group['items']->count() === 1 ? 'item' : 'items' }}
                    </span>
                </td>
            </tr>
        </table>

        {{-- ── Items within this source group ──────────────────────────────── --}}
        @foreach($group['items'] as $item)
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="
                margin-bottom: 16px;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                background: #ffffff;
                overflow: hidden;
            ">
                <tr>
                    <td style="padding: 14px 16px 10px 16px;">

                        {{-- ── Item title (linked) ─────────────────────────── --}}
                        <p style="margin: 0 0 4px 0;">
                            <a href="{{ $item->source_url }}" style="
                                font-family: Arial, sans-serif;
                                font-size: 16px;
                                font-weight: bold;
                                color: #1d4ed8;
                                text-decoration: none;
                                line-height: 1.3;
                            ">{{ $item->source_title ?? 'Untitled' }}</a>
                        </p>

                        {{-- ── Published date ─────────────────────────────── --}}
                        @if($item->source_published_at)
                            <p style="
                                margin: 0 0 10px 0;
                                font-family: Arial, sans-serif;
                                font-size: 12px;
                                color: #9ca3af;
                            ">
                                {{ \Illuminate\Support\Carbon::parse($item->source_published_at)->format('M j, Y') }}
                            </p>
                        @endif

                    </td>
                </tr>

                {{-- ── Summary HTML ─────────────────────────────────────────── --}}
                @if($item->summary_html)
                    <tr>
                        <td style="
                            padding: 0 16px 14px 16px;
                            font-family: Georgia, 'Times New Roman', serif;
                            font-size: 14px;
                            line-height: 1.6;
                            color: #374151;
                        ">
                            {{-- summary_html is trusted HTML from our own LLM pipeline --}}
                            {!! $item->summary_html !!}
                        </td>
                    </tr>
                @endif

            </table>
        @endforeach

        {{-- ── Visual divider between source groups ──────────────────────── --}}
        @unless($loop->last)
            <div style="height:8px;"></div>
        @endunless

    @endforeach

</div>