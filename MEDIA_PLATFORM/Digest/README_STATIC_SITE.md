# Static Site Output Type

## Location

`MEDIA_PLATFORM/Digest/` — this document describes the "Static Site" delivery mechanism for digest lists.

## Overview

The Static Site output type is the third delivery mechanism for digest lists, alongside Email and Webpage (SFTP). Unlike those push-based mechanisms, Static Site uses a **pull model**:

1. The app builds the digest data and persists it to the `published_digests` table.
2. The app fires deploy hooks to trigger a static site rebuild (Cloudflare Pages, Netlify, Vercel).
3. The static site generator (e.g. Astro) calls the app's API endpoint during its build to fetch the digest data.
4. The static site renders pages from the structured JSON — the Laravel app does no HTML rendering for this output type.

No manual intervention is required. The nightly processing pipeline handles everything automatically.

---

## Architecture

### Delivery Strategy Pattern

All three output types share the same build step (`DigestBuilderService::build()`) but use different delivery mechanisms. Delivery is abstracted behind the `DigestDeliveryStrategy` interface:

```
DigestBuilderService::build()
        │
        ▼
PublishDigest job
        │
        ▼
DeliveryStrategyResolver::resolve(OutputType)
        │
        ├── EmailDeliveryStrategy      → sends DigestMailable
        ├── WebpageDeliveryStrategy    → renders Blade, uploads via SFTP
        └── StaticSiteDeliveryStrategy → persists JSON, fires deploy hooks
```

Each strategy implements `DigestDeliveryStrategy::deliver(ListModel $list, array $digestData, DigestBuilderService $builder): bool`.

### Key Principle

**Generating digests does not care how they are delivered.** The `$digestData` array from `DigestBuilderService` is pure structured data. HTML rendering (for email and webpage) happens inside the respective delivery strategy, not in the builder. The static site strategy stores the raw structured data as JSON — the static site generator handles all presentation.

---

## Database

### `published_digests` Table

One record per digest run per static-site list. Stores the complete digest payload as JSON so the API can serve it without reconstructing from the `summaries` table (which is ephemeral — summaries are marked as included and eventually pruned after delivery).

| Column | Type | Purpose |
|---|---|---|
| `id` | bigint PK | |
| `list_id` | FK → lists | The list this digest belongs to |
| `user_id` | FK → users | Owner, for query convenience |
| `slug` | string | URL path segment, e.g. `morning-tech-digest-2026-04-15` |
| `digest_date` | date | The date this digest pertains to |
| `total_items` | unsigned int | Item count |
| `source_count` | unsigned int | Distinct source count |
| `payload` | json | Full structured digest data (groups → items) |
| `deploy_hook_fired_at` | timestamp, nullable | When the deploy hook was fired |
| `api_fetched_at` | timestamp, nullable | When the static site fetched this record |

**Migration path:** `database/migrations/media_platform/digests/processing/`

### `lists` Table Changes

- `output_type` changed from MySQL `enum` to `string` — the PHP `OutputType` enum is the sole authority on valid values.
- `retention_count` (unsigned int, default 10) — how many `published_digests` records to keep per list. Old records are pruned after each new digest is published.

### `deploy_hooks` Table

No schema changes. The existing polymorphic design (`triggerable_type` / `triggerable_id`) supports `digest_list` as a new triggerable type alongside `podcast_show`.

---

## API Endpoint

```
GET /api/v1/digests
```

### Authentication

Same as the podcast API — bearer token + `RequestingDomain` header, validated against `api_clients`.

### Request Headers

| Header | Required | Description |
|---|---|---|
| `Authorization` | Yes | `Bearer <token>` |
| `RequestingDomain` | Yes | The front-end domain |
| `X-Digest-List` | Yes | The list name (matches `lists.name`) |

### Response

```json
{
    "list": {
        "name": "Morning Tech Digest",
        "description": "Daily tech updates from my favourite sources"
    },
    "digests": [
        {
            "slug": "morning-tech-digest-2026-04-15",
            "date": "2026-04-15",
            "total_items": 5,
            "source_count": 3,
            "groups": [
                {
                    "source_name": "Laracasts",
                    "source_type": "youtube_channel",
                    "items": [
                        {
                            "source_url": "https://youtube.com/watch?v=...",
                            "source_title": "What's New in Laravel 13",
                            "source_description": "Jeffrey explores...",
                            "source_published_at": "2026-04-14T18:30:00Z",
                            "summary_html": "<p>A walkthrough of...</p>"
                        }
                    ]
                }
            ]
        }
    ]
}
```

Each object in `digests` maps to one page on the static site. Astro iterates the array, generates a page per entry using the `slug` as the URL path. The `groups` within each digest are organized by content source.

### API On/Off Guard

When `PublishDigest` processes a static site list, it automatically enables the API if it's currently off. This ensures the API is available when the static site generator calls during its build. The API dashboard shows a warning when digests are waiting to be fetched, preventing accidental manual disable.

---

## Deploy Hooks

Static site lists use the same polymorphic deploy hook infrastructure as podcast shows.

| Aspect | Podcast Shows | Digest Lists |
|---|---|---|
| Triggerable type | `podcast_show` | `digest_list` |
| Trigger mode | Manual (confirm → execute → result) | Automatic (fired by `StaticSiteDeliveryStrategy` after persisting digest) |
| Manual trigger | From show page or deploy hook page | From list show page or deploy hook page |
| Multiple hooks | Yes (e.g. live + staging) | Yes |

Deploy hooks are managed via the existing Deploy Hooks UI at `/deploy-hooks`. The create/edit forms support both triggerable types via a dropdown.

When a list wizard creates a new static site list, the "done" page links to the deploy hook create page pre-filled with the new list's type and ID.

---

## Notification Email

`StaticSiteDigestReadyNotification` is sent when `notify_by_email` is true on the list. It confirms:

- The digest was built and persisted
- The deploy hook was fired (or failed — noted in the email)
- The list name, date, and item count excerpt

It does **not** contain the actual digest content (unlike the email output type where the email IS the digest). The user knows where their static site lives.

---

## Processing Pipeline Flow

```
Scheduler
    │
    ▼
DispatchDueLists
    │
    ▼
ProcessList (per list)
    │
    ├── ProcessYoutubeSource  ─┐
    ├── ProcessPodcastSource   ├── Batch
    └── ProcessTextBasedRss   ─┘
                                   │
                                   ▼
                            PublishDigest
                                   │
                    ┌──────────────┼──────────────┐
                    ▼              ▼               ▼
              Email Strategy  Webpage Strategy  Static Site Strategy
                    │              │               │
                    │              │               ├── Persist PublishedDigest
                    │              │               ├── Prune old records
                    │              │               ├── Fire deploy hooks
                    │              │               └── Send notification (optional)
                    │              │
                    │              ├── Render Blade → HTML
                    │              ├── Upload via SFTP
                    │              └── Send notification (optional)
                    │
                    └── Send DigestMailable
```

For static site lists, `PublishDigest` also auto-enables the API before delegating to the strategy.

---

## File Locations

### New Files

| File | Purpose |
|---|---|
| `MEDIA_PLATFORM/Digest/Publishing/Models/PublishedDigest.php` | Eloquent model for persisted digests |
| `MEDIA_PLATFORM/Digest/Publishing/Contracts/DigestDeliveryStrategy.php` | Strategy interface |
| `MEDIA_PLATFORM/Digest/Publishing/Strategies/EmailDeliveryStrategy.php` | Email delivery |
| `MEDIA_PLATFORM/Digest/Publishing/Strategies/WebpageDeliveryStrategy.php` | SFTP delivery |
| `MEDIA_PLATFORM/Digest/Publishing/Strategies/StaticSiteDeliveryStrategy.php` | Static site delivery |
| `MEDIA_PLATFORM/Digest/Publishing/Services/DeliveryStrategyResolver.php` | Resolves strategy by OutputType |
| `MEDIA_PLATFORM/Digest/Publishing/Notifications/StaticSiteDigestReadyNotification.php` | Notification |
| `MEDIA_PLATFORM/API/v1/Controllers/DigestApiController.php` | API endpoint |
| `MEDIA_PLATFORM/API/v1/Services/DigestApiService.php` | API query service |

### Key Edited Files

| File | What Changed |
|---|---|
| `MEDIA_PLATFORM/Digest/Enums/OutputType.php` | Added `StaticSite` case |
| `MEDIA_PLATFORM/Digest/Processing/Jobs/PublishDigest.php` | Refactored to use delivery strategies |
| `MEDIA_PLATFORM/StaticSiteDeployHooks/Controllers/DeployHookController.php` | Supports `digest_list` triggerable |
| `MEDIA_PLATFORM/Digest/ContentSources/Lists/Controllers/ListWizardController.php` | Static site wizard path |
| `MEDIA_PLATFORM/Digest/ContentSources/Lists/Models/ListModel.php` | Added relationships and retention |

---

## Morph Aliases

Registered in `AppServiceProvider`:

```php
'digest_list' => ListModel::class,
```

---

## Retention

Each static site list has a `retention_count` (default 10). After each new digest is published, records beyond this count are pruned (oldest first). This controls how many digests the static site displays — when Astro rebuilds, it fetches all retained records and generates a page for each.

Retention policy details (pruning frequency, cleanup scheduling) are being finalised separately.

---

## Testing

| Test File | Coverage |
|---|---|
| `StaticSiteDeliveryStrategyTest.php` | Payload persistence, pruning, deploy hooks, notifications |
| `DigestApiControllerTest.php` | Auth, 503/403/200, response structure, api_fetched_at |
| `DeployHookControllerDigestListTest.php` | CRUD for digest_list hooks |
| `EmailDeliveryStrategyTest.php` | Extracted email delivery logic |
| `WebpageDeliveryStrategyTest.php` | Extracted SFTP delivery logic |
| `PublishDigestTest.php` | Updated for strategy architecture + GROUP 8 for static site |
| `ListCrudTest.php` | Static site update/validation paths |
| `WizardFlowTest.php` | Static site wizard path |