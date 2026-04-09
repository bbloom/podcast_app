# Public Podcast API — v1

## Overview

A stateless, read-only JSON API that serves podcast episode data to Astro-based static site front-ends during their build process. The API is designed to be switched on manually before a build and off again afterwards — it is not intended to be always-on.

---

## Endpoint

```
GET /api/v1/podcastepisodes
```

Returns all published episodes, all enabled guests, and all enabled sponsors in a single JSON response. Astro consumes this once at build time and generates all static pages from it.

---

## Authentication

Every request must include two headers:

| Header | Description |
|---|---|
| `Authorization` | `Bearer <token>` — the client's bearer token |
| `RequestingDomain` | The front-end domain, e.g. `bobbloomshow.com` |

Both must match an active record in the `api_clients` table. If either is missing, incorrect, or the client is inactive, the request is rejected with `403 Forbidden`.

---

## Response Structure

```json
{
    "episodes": [
        {
            "title": "...",
            "slug": "...",
            "website_publish_on": "...",
            "website_content": "...",
            "website_excerpt": "...",
            "website_meta_description": "...",
            "website_episode_notes": "...",
            "website_attribution": "...",
            "website_featured_image": "...",
            "itunes_enclosure_url": "...",
            "itunes_image": "...",
            "itunes_pubdate": "...",
            "itunes_duration": "...",
            "itunes_episode": 42,
            "itunes_season": 1,
            "itunes_episode_type": "full",
            "itunes_summary": "...",
            "guests": ["john-smith", "jane-doe"],
            "links": [
                {
                    "title": "...",
                    "link": "...",
                    "description": "..."
                }
            ]
        }
    ],
    "guests": [
        {
            "full_name": "...",
            "slug": "...",
            "image_url": "...",
            "image_thumbnail_url": "...",
            "profile_full": "...",
            "profile_short": "...",
            "link_to_guest_website": "..."
        }
    ],
    "sponsors": [
        {
            "full_name": "...",
            "image_url": "...",
            "image_thumbnail_url": "...",
            "profile_full": "...",
            "profile_short": "...",
            "link_to_sponsor_website": "...",
            "sponsor_type": "Umbrella sponsor",
            "former_sponsor": false
        }
    ]
}
```

### Key design decisions

- **Episodes** are ordered by `website_publish_on` descending (newest first). Only episodes where `website_enabled = true` and `website_publish_on` is in the past are included.
- **Episode guests** are an array of slugs only — Astro cross-references these against the top-level `guests` array to build episode and guest pages.
- **Top-level guests** contain the full profile including `profile_full`, enabling Astro to generate dedicated guest pages (e.g. `guests/john-smith/`).
- **Sponsors** appear on every page of the front-end. The three boolean tier flags (`umbrella_sponsor`, `basecamp_sponsor`, `restream_sponsor`) are collapsed into a single `sponsor_type` string.
- **Sensitive fields are never returned** — `email_address` and `internal_comment` are excluded from all resources.

---

## API On/Off Switch

The API is disabled by default. It must be enabled manually via the Admin UI before an Astro build, and disabled again afterwards.

**Admin UI location:** Dashboard → API Management

When the API is disabled, all requests return `503 Service Unavailable` regardless of authentication.

---

## API Clients

Each front-end domain is registered as an API client in the `api_clients` table. Clients are managed via the Admin UI.

**Current clients:**

| Label | Domain |
|---|---|
| The Bob Bloom Show | bobbloomshow.com |
| The Bob Bloom Interviews | bobbloominterviews.com |
| PHP Serverless News | phpserverlessnews.com |
| PHP Serverless Profiles | phpserverlessprofiles.com |
| PHP Serverless Project Updates | phpserverlessprojectupdates.com |

Note: LaSalle Software News (`lasallesoftwarenews.com`) does not have an Astro front-end and is therefore not registered as an API client.

### Token management

- Bearer tokens are stored as **bcrypt hashes** — they cannot be retrieved after creation.
- When creating a new client or rotating a token, the plain-text token is shown **once** in the Admin UI. Copy it immediately into the front-end's environment secrets.
- If a token is lost, rotate it via the Admin UI. The old token is immediately invalidated.
- New clients are seeded as **inactive** with a placeholder token. Activate and rotate the token via the Admin UI when the front-end is ready to connect.

---

## Folder Structure

```
MEDIA_PLATFORM/API/v1/
├── Controllers/
│   ├── ApiClientController.php     — CRUD + token rotation for API clients
│   ├── ApiControlController.php    — Enable / disable the API
│   └── PodcastEpisodesController.php — The single public API endpoint
├── Dashboard/
│   └── DashboardController.php     — API Management admin dashboard
├── Middleware/
│   ├── AuthenticateApiClient.php   — Bearer token + domain header validation
│   └── CheckApiEnabled.php         — 503 gate when API is switched off
├── Models/
│   ├── ApiClient.php               — API client record + token management
│   └── ApiControl.php              — Single-row on/off switch
├── Requests/
│   └── ApiClientRequest.php        — Validation for client create / update
├── Resources/
│   ├── PodcastEpisodeResource.php  — Episode JSON transformation
│   ├── PodcastGuestResource.php    — Guest JSON transformation
│   └── PodcastSponsorResource.php  — Sponsor JSON transformation
├── Routes/
│   ├── api.php                     — Public API route (loaded via routes/api.php)
│   └── web.php                     — Admin UI routes (loaded via routes/web.php)
├── Services/
│   └── PodcastEpisodeApiService.php — Queries episodes, guests, sponsors
└── README.md                        — This file
```

---

## Database Tables

| Table | Purpose |
|---|---|
| `api_controls` | Single-row on/off switch for the public API |
| `api_clients` | Authorised front-end domains and their hashed bearer tokens |

Migration path: `database/migrations/media_platform/api/`

---

## Tests

Test classes live in `tests/Feature/MEDIA_PLATFORM/API/v1/`:

| Test class | Coverage |
|---|---|
| `PodcastEpisodesControllerTest` | Endpoint — 503, 403, 200, response structure, field exposure |
| `DashboardControllerTest` | Admin dashboard — access control, content |
| `ApiControlControllerTest` | Enable / disable — access control, timestamps |
| `ApiClientControllerTest` | Full CRUD + token rotation — access control, validation, token hashing |