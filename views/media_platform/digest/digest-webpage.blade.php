{{--
    ============================================================================
    FILE: views/media_platform/digest/digest-webpage.blade.php
    ============================================================================

    Standalone HTML page uploaded via SFTP.

    Slightly richer than the email version — uses a real <head> with a <style>
    block for hover effects and better typography. Still very lean and fast-loading
    since there are no external dependencies. Designed to be served without a
    .html extension (configure your web server accordingly).

    VARIABLES:
      $list        — ListModel
      $digestData  — structured array from DigestBuilderService::build()
      $slug        — the filename/slug used for this digest (for canonical purposes)
--}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $list->name }} — {{ $digestData['date']->format('M j, Y') }}</title>
    <style>
        /* ── Reset & base ──────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            background: #f9fafb;
            font-family: Georgia, 'Times New Roman', serif;
            color: #1a1a1a;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Layout ────────────────────────────────────────────────────────── */
        .wrapper {
            max-width: 720px;
            margin: 0 auto;
            padding: 32px 16px 64px;
        }

        /* ── Header ────────────────────────────────────────────────────────── */
        .digest-header {
            background: #7c3aed;
            border-radius: 8px;
            padding: 28px 32px;
            margin-bottom: 28px;
            color: #fff;
        }
        .digest-header h1 {
            margin: 0 0 6px;
            font-family: Arial, sans-serif;
            font-size: 24px;
            font-weight: bold;
            line-height: 1.2;
        }
        .digest-header .meta {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #ddd6fe;
        }

        /* ── Item card hover — improves scannability on desktop ────────────── */
        table { border-collapse: collapse; }
        a:hover { text-decoration: underline !important; }

        /* ── Footer ────────────────────────────────────────────────────────── */
        .digest-footer {
            margin-top: 40px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="wrapper">

        {{-- ── Page header ──────────────────────────────────────────────────── --}}
        <div class="digest-header">
            <h1>{{ $list->name }}</h1>
            <p class="meta">
                {{ $digestData['date']->format('l, F j, Y') }}
                &nbsp;·&nbsp;
                {{ $digestData['total_items'] }} {{ $digestData['total_items'] === 1 ? 'item' : 'items' }}
                from {{ $digestData['source_count'] }} {{ $digestData['source_count'] === 1 ? 'source' : 'sources' }}
            </p>
        </div>

        {{-- ── Digest items (shared partial) ───────────────────────────────── --}}
        @include('media_platform.digest._items', ['digestData' => $digestData])

        {{-- ── Page footer ──────────────────────────────────────────────────── --}}
        <div class="digest-footer">
            Generated automatically · {{ $digestData['date']->format('Y-m-d H:i T') }}
        </div>

    </div>
</body>
</html>