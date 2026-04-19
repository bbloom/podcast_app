# Digest Pipeline

## Overview

The digest pipeline is the **final stage** of the content processing pipeline. After
`ProcessList` batches all source-processing jobs (YouTube, Podcast, TextBasedRss), the
`->then()` callback dispatches `PublishDigest`, which assembles everything written to
the `summaries` table and delivers it to the user.

```
DispatchDueLists
  └─► ProcessList
        └─► Bus::batch([ProcessYoutubeSource, ProcessPodcastSource, ProcessTextBasedRssSource, ...])
              └─► (on batch complete) PublishDigest::dispatch($listId)
```

---

## Key Classes

| Class | Path | Responsibility |
|---|---|---|
| `PublishDigest` | `app/Processing/Jobs/PublishDigest.php` | Orchestrates the whole delivery: queries summaries, renders view, routes to SFTP / email / WordPress |
| `DigestBuilderService` | `app/Processing/Services/DigestBuilderService.php` | Queries pending summaries grouped by source; marks them as included after delivery |
| `SftpService` | `app/Lists/Services/SftpService.php` | SFTP connection testing (pre-existing) **and** file upload (added in this phase) |
| `WordPressService` | `app/Lists/Services/WordPressService.php` | Publishes a post to WordPress via the REST API using Application Password auth |
| `DigestMailable` | `app/Mail/DigestMailable.php` | Sends the digest as a rich HTML email (used when `output_type = email`) |
| `DigestReadyNotification` | `app/Notifications/DigestReadyNotification.php` | "Your digest is ready" nudge email when `output_type = webpage` and `notify_by_email = true` |
| `DigestEmptyNotification` | `app/Notifications/DigestEmptyNotification.php` | Sent when a list runs but yields no new relevant summaries |

---

## Output Types

### `email`
The digest HTML is sent **directly as the email body**. No file is created. Uses
`DigestMailable` which wraps `views/digests/digest-email.blade.php`.

### `webpage` (SFTP)
The digest is rendered as a **standalone HTML page** and uploaded via SFTP to the
configured `OutputDestination`. The file is named:

```
{list-slug}-digest-{YYYY-MM-DD}
```

For example: `morning-tech-digest-2026-03-13`

No `.html` extension — the web server should serve the file without one (configure
`DirectoryIndex` or URL rewriting on your server). After upload, if the list has
`notify_by_email = true`, a `DigestReadyNotification` email is sent with a link to
`{base_url}/{filename}`.

### `wordpress`
The digest is posted to a **WordPress site** as a new post via the WP REST API
(`/wp/v2/posts`). Authentication uses WordPress Application Passwords (Basic Auth),
which is the recommended approach since WP 5.6.

Fields sent to WordPress:
- `title` — List name + date
- `slug` — Same `{list-slug}-digest-{YYYY-MM-DD}` pattern (no extension)
- `content` — Full digest HTML (rendered from `views/digests/digest-wp.blade.php`)
- `excerpt` — Plain-text summary: "{N} items from {source count} sources"
- `status` — Configurable per OutputDestination (`publish`, `draft`, `private`)
- `categories` — Optional category IDs array (from OutputDestination config)
- `tags` — Optional tag IDs array (from OutputDestination config)
- `date` — Set to the run date in ISO 8601 format

---

## Digest Views

All views live in `views/digests/`:

| View | Used for |
|---|---|
| `_items.blade.php` | Shared partial — renders the list of source groups and summary cards |
| `digest-email.blade.php` | Full HTML email shell (table-based, inline CSS, email-safe) |
| `digest-webpage.blade.php` | Standalone HTML page (richer, browser-safe) |
| `digest-wp.blade.php` | WordPress post body (clean HTML fragment, no shell — WP provides the outer page) |

The `_items` partial is the **single source of truth** for digest content rendering.
All three wrappers include it. This means layout changes only need to happen once.

---

## Empty Digest Behaviour

When `PublishDigest` runs and finds **zero new relevant summaries**, it:

1. Silently skips the publish step (no file upload, no email sent, no WP post created)
2. Sends a `DigestEmptyNotification` to the list owner informing them that the digest
   ran but had nothing new
3. Still updates `lists.last_run_at` so the scheduler does not re-run the same window

---

## Filename / Slug Convention

The slug for both SFTP filenames and WordPress post slugs is derived from the list name:

```php
strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($list->name)))
    . '-digest-'
    . now()->format('Y-m-d');
```

Examples:
- "Morning Tech Digest" → `morning-tech-digest-2026-03-13`
- "AI & Robotics Weekly" → `ai-robotics-weekly-digest-2026-03-13`

---

## Database: What Gets Updated

After successful publish:

```sql
-- Mark each summary as included
UPDATE summaries
SET included_in_digest = true,
    included_in_digest_at = NOW()
WHERE id IN (...);

-- Update the list's last run timestamp
UPDATE lists SET last_run_at = NOW() WHERE id = ?;
```

Summaries already marked `included_in_digest = true` are **never** re-included.
This is the idempotency guarantee — if `PublishDigest` is retried after a partial
failure, only genuinely new summaries will appear.

---

## Error Handling & Gates

`PublishDigest` checks `ProcessingGate::canPublish()` before doing any SFTP or
WordPress work. If a Tier 3 alert exists for `sftp` or `infrastructure`, publishing
is blocked and an error is logged — but the job does **not** fail, so summaries
are not marked as included. They will be picked up on the next successful run.

SFTP failures and WordPress API failures each raise `AdminAlert::raiseIfNew()` at
Tier 2, so you will receive an email but processing is not fully blocked.

---

## Adding a New Output Type

1. Add the value to `app/Enums/OutputType.php`
2. Write a migration to extend the `output_type` enum on `lists`
3. Add any new credential columns to `output_destinations` (with encrypted cast)
4. Write a Service class for the delivery mechanism
5. Add a `case` to `PublishDigest::publish()` routing the new type to the service
6. Add wizard steps to `OutputDestinationWizardController` (branch at step 2 on type)
7. Add the `publish` subsystem category to `ProcessingGate` if SFTP-class reliability needed
8. Write Pest tests

---

## Podcast Wiring Status

Podcasts are **fully wired**. `SourceJobResolver` dispatches `ProcessPodcastSource`
for `sourceable_type = 'podcast'`, and `ProcessingGate` has `podcast` in its
subsystem categories. No additional work is needed.