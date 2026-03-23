{{--
    ============================================================================
    FILE: views/digests/digest-wp.blade.php
    ============================================================================

    WordPress post body — a clean HTML fragment with NO outer shell.

    WordPress provides the page shell (doctype, head, body tags) via the active
    theme. This view renders only the post content that will sit inside the WP
    <article> element. Since you are building a custom theme, you have full control
    over how this content is wrapped and styled.

    What is included here:
      - A brief meta line (date + item count) for the post intro
      - The shared _items partial (all source groups and summary cards)

    What is deliberately excluded:
      - The list name / title — WordPress uses its own post title field
      - Any outer <html>, <head>, or <body> tags
      - Any CSS that would conflict with your theme's stylesheet

    All summary_html content uses semantic HTML (p, ul, li, strong, em) so your
    theme's own paragraph and list styles will apply naturally.

    VARIABLES:
      $list        — ListModel
      $digestData  — structured array from DigestBuilderService::build()
--}}

{{-- ── Post intro line ──────────────────────────────────────────────────── --}}
<p class="digest-meta" style="color:#6b7280; font-size:14px; margin-bottom:24px;">
    {{ $digestData['date']->format('l, F j, Y') }}
    &nbsp;·&nbsp;
    {{ $digestData['total_items'] }} {{ $digestData['total_items'] === 1 ? 'item' : 'items' }}
    from {{ $digestData['source_count'] }} {{ $digestData['source_count'] === 1 ? 'source' : 'sources' }}
</p>

{{-- ── Digest items (shared partial) ──────────────────────────────────────── --}}
{{-- Inline styles are included in the partial for portability, but your theme  --}}
{{-- can override them by targeting .digest-meta, the source header table, etc. --}}
@include('media_platform.digest._items', ['digestData' => $digestData])