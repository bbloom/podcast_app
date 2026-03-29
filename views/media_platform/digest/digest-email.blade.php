{{--
    ============================================================================
    FILE: views/media_platform/digest/digest-email.blade.php
    ============================================================================

    Full HTML email shell for the digest.

    Used by DigestMailable (output_type = email). Designed to render correctly
    in Gmail, Apple Mail, Outlook 2016+, and webmail clients. Uses table-based
    layout and inline CSS only — no external stylesheets, no @media queries.

    VARIABLES:
      $list        — ListModel
      $digestData  — structured array from DigestBuilderService::build()
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $list->name }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f9fafb;">

    {{-- ── Outer wrapper table — forces full-width on all clients ──────────── --}}
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f9fafb;">
        <tr>
            <td align="center" style="padding: 24px 16px;">

                {{-- ── Content card ───────────────────────────────────────────── --}}
                <table width="680" cellpadding="0" cellspacing="0" border="0" style="max-width:680px; width:100%; background:#ffffff; border-radius:8px; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">

                    {{-- ── Header ─────────────────────────────────────────────── --}}
                    <tr>
                        <td style="
                            padding: 28px 32px 20px 32px;
                            background: #7c3aed;
                            border-radius: 8px 8px 0 0;
                        ">
                            {{-- List name --}}
                            <p style="
                                margin: 0 0 4px 0;
                                font-family: Arial, sans-serif;
                                font-size: 22px;
                                font-weight: bold;
                                color: #ffffff;
                                line-height: 1.2;
                            ">{{ $list->name }}</p>

                            {{-- Date + item count summary --}}
                            <p style="
                                margin: 0;
                                font-family: Arial, sans-serif;
                                font-size: 13px;
                                color: #ddd6fe;
                            ">
                                {{ $digestData['date']->format('l, F j, Y') }}
                                &nbsp;·&nbsp;
                                {{ $digestData['total_items'] }} {{ $digestData['total_items'] === 1 ? 'item' : 'items' }}
                                from {{ $digestData['source_count'] }} {{ $digestData['source_count'] === 1 ? 'source' : 'sources' }}
                            </p>
                        </td>
                    </tr>

                    {{-- ── Body — digest items partial ────────────────────────── --}}
                    <tr>
                        <td style="padding: 24px 32px 8px 32px;">
                            @include('media_platform.digest._items', ['digestData' => $digestData])
                        </td>
                    </tr>

                    {{-- ── Footer ─────────────────────────────────────────────── --}}
                    <tr>
                        <td style="
                            padding: 16px 32px 24px 32px;
                            border-top: 1px solid #f3f4f6;
                        ">
                            <p style="
                                margin: 0;
                                font-family: Arial, sans-serif;
                                font-size: 11px;
                                color: #9ca3af;
                                text-align: center;
                            ">This digest was generated automatically by your content aggregator.</p>
                        </td>
                    </tr>

                </table>
                {{-- /content card --}}

            </td>
        </tr>
    </table>

</body>
</html>
