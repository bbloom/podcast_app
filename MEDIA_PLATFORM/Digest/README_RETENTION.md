# Digest Retention

## Location

`MEDIA_PLATFORM/Digest/` — this document describes the retention and pruning mechanism for digest data.

## Overview

After a digest is successfully delivered, old data is pruned automatically based on each list's `retention_count` setting. Pruning is tied to the processing sequence — it runs as the final step after successful delivery, not as a separate scheduled job.

## The Three Tables

Three tables participate in the digest lifecycle. Understanding their roles is essential to understanding retention:

### `content_already_processed`

One row per `list_source`. Tracks the most recently processed content item's URL so the next processing run knows where to stop. This table is **never pruned** — it is a permanent bookmark. It exists specifically so that `summaries` can be safely deleted without losing the "where did I leave off" marker.

### `summaries`

Working table. Rows are created during content processing (one per content item per list source). After successful delivery, `DigestBuilderService::markAsIncluded()` sets `included_in_digest = true`. Rows where `included_in_digest = true` are eligible for pruning.

For email and SFTP lists, summaries are the only record of what was in a digest. Once pruned, the data is gone (the email sits in your inbox, the HTML file sits on the server — but the app has no record).

### `published_digests`

Only used for static site lists. Stores the complete digest payload as JSON. Pruned based on `retention_count` — oldest records beyond the limit are deleted.

## Retention Count

Each list has a `retention_count` field (default: 10). It controls how many digest runs to keep:

- **Static site lists:** How many `published_digests` records to retain. This directly controls how many pages the static site renders. A daily list with retention 10 keeps 10 days of digests.
- **Email and SFTP lists:** How many digest runs worth of `summaries` rows to keep after they have been marked as included. These are grouped by `included_in_digest_at` date. A daily list with retention 10 keeps 10 days of delivered summary rows.

The retention count is set during list creation (for static site lists) or via the edit form (for all output types). The default of 10 is sensible for most use cases.

## How Pruning Works

### `DigestRetentionService`

A single service class at `MEDIA_PLATFORM/Digest/Publishing/Services/DigestRetentionService.php` handles all pruning. It has one public method:

```
pruneForList(ListModel $list): void
```

This method is called by `PublishDigest` after successful delivery and after `markAsIncluded()`, for all output types.

### For Static Site Lists

Prunes `published_digests`. Keeps the newest N records (by `digest_date` descending, then `id` descending). Deletes the rest. This logic was previously inline in `StaticSiteDeliveryStrategy` — it has been extracted to `DigestRetentionService` for consistency.

### For Email and SFTP Lists

Prunes `summaries` rows where `included_in_digest = true`. Groups the included summaries by the date portion of `included_in_digest_at` to identify distinct digest runs. Keeps the newest N dates. Deletes all included summaries older than the Nth date.

Example: retention_count = 5, list runs daily. After today's delivery, the summaries table contains included rows from today, yesterday, 3 days ago, 4 days ago, 5 days ago, 6 days ago. The 6-day-old rows are deleted. The rest are kept.

### What Is NOT Pruned

- `content_already_processed` — never pruned (permanent bookmark)
- `summaries` where `included_in_digest = false` — these are pending items not yet delivered, they must not be touched
- `summaries` where `is_relevant = false` — these are search-mode items that didn't match, kept for auditing

## Pipeline Position

```
PublishDigest
    │
    ├── Build digest (DigestBuilderService)
    ├── Deliver (strategy)
    ├── Mark as included (DigestBuilderService)
    ├── Prune old data (DigestRetentionService)  ← NEW
    └── Update last_run_at
```

Pruning happens after `markAsIncluded()` and before `updateLastRunAt()`. If delivery fails, neither `markAsIncluded()` nor pruning runs — the summaries remain pending for retry.

## Failure Scenarios

### Delivery fails (detectable)

Email SMTP error, SFTP connection refused, deploy hook HTTP error. The delivery strategy returns `false`. `PublishDigest` does NOT call `markAsIncluded()` or `pruneForList()`. The summaries remain with `included_in_digest = false` and are automatically retried on the next scheduled run.

### Delivery appears to succeed but content is lost (silent failure)

Email accepted by SMTP but never arrives, SFTP file written but server disk fails. The strategy returns `true`, summaries are marked as included, and pruning runs. The app has no way to detect this. For email/SFTP lists, this is a monitoring problem outside the app's control. For static site lists, the `published_digests` record exists and can be re-served.

## UI

The `retention_count` field is visible in the list edit form for all output types. For static site lists, it's also shown during list creation (wizard step). Help text explains what the number means for each output type.