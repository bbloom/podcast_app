# Architecture

## Overview

A Laravel application that aggregates content from YouTube channels, podcasts, and text-based RSS feeds. Content is fetched nightly, summarised using Gemini AI, and delivered to the user via configurable output destinations.

## Core Features

- **Content Sources:** YouTube channels, podcasts, text-based RSS feeds
- **Nightly Pipeline:** Fetches new content, generates AI summaries, publishes digests
- **Output Destinations:** Configurable delivery — email, SFTP webpage upload, or static site (via deploy hooks + API)
- **Lists:** Users group content sources into lists for organised digest delivery

## Database Tables

- `youtube_channels` — stores channel ID, name, last_fetched_at
- `podcasts` — stores podcast RSS URL, name, last_fetched_at
- `text_based_rss_feeds` — stores feed URL, name, last_fetched_at
- `lists` — user-defined groupings of content sources
- `list_sources` — polymorphic pivot joining lists to any source type
- `summaries` — AI-generated summaries, polymorphic to source type
- `output_destinations` — where digests are delivered (e.g. SFTP)
- `published_digests` — persisted digest payloads for static site output type; one record per digest run per list; served via the API to static site generators
- `language_models` — available AI models for summarisation
- `podcast_shows` — the five podcast shows; each maps to an RSS `<channel>` element; includes `intro_template` and `outro_template` columns (used by the Phase 3 Finalize Script Wizard)
- `podcast_episodes_published` — published podcast episodes; each maps to an RSS `<item>` element; the API serves from this table
- `podcast_links` — reusable links (show notes URLs, references) attached to episodes; scoped by `user_id`
- `podcast_guests` — guest profiles for interview show episodes
- `podcast_guest_episode` — pivot table joining guests to published episodes
- `podcast_link_episode` — pivot table joining links to published episodes
- `api_controls` — single-row on/off switch for the public API
- `api_clients` — authorised front-end domains and their hashed bearer tokens
- `deploy_hooks` — polymorphic table of static site deploy hook URLs; belongs to any triggerable model (PodcastShow, ListModel)

## Polymorphic Relationships

Content sources (`YoutubeChannel`, `Podcast`, `TextBasedRssFeed`) are related to lists and summaries via polymorphic relationships using morph aliases.

Deploy hooks (`DeployHook`) are polymorphic via `triggerable_type` / `triggerable_id` — supporting `podcast_show` and `digest_list`.

## AI Provider Strategy

- Currently using Gemini (`gemini-2.5-flash`) for its generous free tier during prototyping
- The app must **not** be hard-coded to Gemini — AI providers are swappable
- A `language_models` table stores available models and providers
- Summarisation is abstracted behind a service/interface so the provider can be changed or configured per user without touching pipeline logic
- Future candidates: OpenAI, GitHub Copilot, Anthropic, or others

## Digest Delivery Strategies

- Delivery logic is decoupled from digest building via the strategy pattern
- `DigestDeliveryStrategy` interface with three implementations:
  - `EmailDeliveryStrategy` — sends `DigestMailable` as the email body
  - `WebpageDeliveryStrategy` — renders Blade to HTML, uploads via SFTP
  - `StaticSiteDeliveryStrategy` — persists JSON payload to `published_digests`, fires deploy hooks
- `DeliveryStrategyResolver` maps `OutputType` enum cases to strategy classes
- `PublishDigest` job delegates to the resolved strategy — no delivery logic in the job itself
- Strategies live at `MEDIA_PLATFORM/Digest/Publishing/Strategies/`

## Digest Retention

- Each list has a retention_count field (default 10) controlling how many digest runs to keep
- DigestRetentionService::pruneForList() enforces retention after every successful delivery
- For static site lists: prunes old published_digests records (oldest beyond retention_count)
- For email and SFTP lists: prunes old summaries rows where included_in_digest = true, grouped by the date of included_in_digest_at
- Never prunes: content_already_processed (permanent bookmark), pending summaries (included_in_digest = false), or irrelevant summaries (is_relevant = false)
- Pruning is tied to the processing sequence — no separate scheduled job
- Called by PublishDigest after markAsIncluded() and before updateLastRunAt()
- See MEDIA_PLATFORM/Digest/README_RETENTION.md for full detail

## Static Site Output Type

- Third output type alongside Email and Webpage (SFTP)
- Uses a pull model: app persists data, fires deploy hooks, static site fetches via API
- `published_digests` table stores the full structured digest payload as JSON
- Each list has a `retention_count` controlling how many digest records to keep
- API endpoint: `GET /api/v1/digests` — authenticated, list identified by `X-Digest-List` header
- Deploy hooks fire automatically after digest persistence — no manual confirmation
- `PublishDigest` auto-enables the API when processing a static site list
- Notification: `StaticSiteDigestReadyNotification` — optional, confirms pipeline ran (does not contain digest content)
- See `MEDIA_PLATFORM/Digest/README_STATIC_SITE.md` for full detail

## Public API

- A stateless, read-only JSON API that serves data to Astro-based static site front-ends during their build process
- Two endpoints:
  - `GET /api/v1/podcastepisodes` — returns all published episodes, enabled guests, and enabled sponsors
  - `GET /api/v1/digests` — returns published digest data for static site lists, identified by `X-Digest-List` header
- The API is off by default — enabled manually via the Admin UI before an Astro build, or auto-enabled by `PublishDigest` for static site lists
- Authentication uses a bearer token plus a `RequestingDomain` header, both validated against the `api_clients` table
- Bearer tokens are stored as bcrypt hashes and shown only once at generation time
- The API on/off state is persisted in the `api_controls` database table for durability across server restarts
- `api_fetched_at` tracked on published digest records for observability
- API dashboard shows pending fetch warnings to prevent accidental disable
- Five front-end domains are registered as API clients: `bobbloomshow.com`, `bobbloominterviews.com`, `phpserverlessnews.com`, `phpserverlessprofiles.com`, `phpserverlessprojectupdates.com`
- Managed via the Admin UI at Dashboard → API Management
- See `MEDIA_PLATFORM/API/v1/README.md` for full detail

## Podcasts

- Lives at `MEDIA_PLATFORM/Podcasts/` — the central hub for podcast production across five shows
- The Podcasts module has its own dedicated dashboard — the main app dashboard links to it as a single entry point
- The production pipeline: Recording → Post-Production → Publishing, tracked via `PodcastEpisodeStatus` enum on `podcast_episodes_published.status`
- Published episodes are served via the API to Astro static site front-ends

### Module Structure (`Podcasts/`)

- `Dashboard/` — podcast dashboard controller and routes
- `Enums/` — `PodcastEpisodeStatus` enum
- `Shows/` — CRUD for podcast shows (Controllers, Models, Requests, Routes); shows have `intro_template` and `outro_template` columns
- `Guests/` — CRUD for podcast guests, plus attach/detach to episodes
- `Links/` — CRUD for podcast links, plus attach/detach to episodes; scoped by `user_id`
- `Publishing/` — Published episode CRUD (Controllers, Models, Requests, Routes) and full Post-Production pipeline
- `ArchivedEpisodes/` — `BobBloomShowArchive` for legacy archive data

### Post-Production (`Publishing/PostProduction/`)

- `UploadRecording` — pre-signed S3 PUT upload, S3 file confirmation, status → `ready_for_auphonic`
- `AuphonicProcessing` — S3 file verification, Auphonic submission, webhook processing, MP3 download, clean-up; see `AuphonicProcessing/README.md`
- `UploadProductionAudio` — two-path MP3 upload (Auphonic download or manual upload), getID3 metadata extraction, S3 + R2 upload; see `UploadProductionAudio/README.md`
- `GenerateRssFeed` — generates RSS XML, validates it, uploads to staging for external validation, promotes to live S3 + R2, advances status to `ready_to_publish`; see `GenerateRssFeed/README.md`
- `PublishOnWebsite` — sets `website_enabled = true`, advances status to `published`, redirects to "Trigger Static Site Builds" when `website_publish_on <= today`; future-dated episodes go straight to the index
- `RegenerateRssFeed` — show-level maintenance flow, rebuilds entire RSS feed from all eligible episodes; operates independently of any episode's pipeline status
- `CloudStorage/` — S3 and R2 bucket/endpoint resolution classes
- `Dashboard/` — Post-Production pipeline dashboard

### Five Active Shows

Controllers that list shows use a `private const ACTIVE_SHOWS` array to filter and order:
1. The Bob Bloom Show
2. The Bob Bloom Interviews
3. PHP Serverless News
4. PHP Serverless Profiles
5. PHP Serverless Project Updates

### Status Enums

- `PodcastEpisodeStatus` (`MEDIA_PLATFORM/Podcasts/Enums/`): tracks the production pipeline —
  `created` → `ready_to_upload_recording` → `ready_for_auphonic` → `processing_at_auphonic` → `auphonic_complete` → `ready_to_upload_production_file` → `ready_to_generate_rss_feed` → `ready_to_upload_rss_feed` → `ready_to_publish` → `published`; also `not_published` (episode recorded but intentionally not published, set manually)
- `ready_to_upload_recording` retained for backward compatibility — marked for removal once the Phase 3 Publishing wizard refactor is complete

### Phase 3 — Planning (upcoming)

Phase 3 will add a Planning module at `MEDIA_PLATFORM/Podcasts/Planning/` with:
- New `podcast_episodes_planning` table — creative and assembly workspace; records are hard-deleted on publishing (no soft deletes)
- New `PodcastEpisodePlanningStatus` enum (separate from `PodcastEpisodeStatus`)
- Wizards: Create Episode, Finalize Script (uses `intro_template`/`outro_template` from `podcast_shows`), Prepare for Publishing
- Focused field editors for `theme` and `script`
- Guest interaction feature
- Publishing handoff: populates `podcast_episodes_published`, hard-deletes the planning record

## Static Site Deploy Hooks

- Lives at `MEDIA_PLATFORM/StaticSiteDeployHooks/`
- Polymorphic — a deploy hook can belong to any triggerable model (`PodcastShow`, `ListModel`)
- Morph aliases: `podcast_show` and `digest_list` — both registered in `AppServiceProvider`
- Providers supported: Cloudflare Pages, Netlify, Vercel (backed by `DeployHookProvider` enum)
- Hook URL stored encrypted — anyone holding the URL can trigger a build
- Tracks `last_triggered_at`, `last_build_id`, `last_trigger_status` per hook
- `DeployHookTriggerService` handles the HTTP POST, parses provider responses, and records outcomes
- `DeployHookTriggerResult` is an immutable value object carrying success/failure, build ID, HTTP status, error message
- `DeployHook` model provides `triggerable_display_name`, `triggerable_type_label`, and `triggerable_show_route` accessors for polymorphic view rendering
- Three trigger entry points:
  1. **Single hook** — from the deploy hook's show page (confirm → execute → result)
  2. **Multi-hook** — from the podcast show's show page or after publishing an episode (checkbox selection → results)
  3. **Automatic** — `StaticSiteDeliveryStrategy` fires all enabled hooks after persisting a published digest
- Post-publish trigger: `PublishController` redirects to "Trigger Static Site Builds" when `website_publish_on <= today`; future-dated episodes skip the trigger and go straight to the index

## Eloquent Scopes — PodcastEpisode

The following named scopes are defined on `PodcastEpisode` to avoid duplicating query logic across controllers and services:

- `scopeForUser(int $userId)` — filters by `user_id`
- `scopeWithStatus(PodcastEpisodeStatus $status)` — filters by pipeline status
- `scopeOrderByScheduledDate()` — orders by `scheduled_date` ascending
- `scopeEligibleForRssFeed(PodcastShow $show)` — `rss_feed_enabled = true` AND `itunes_pubdate < now()`, ordered by `itunes_pubdate` descending
- `scopeEligibleForPublishOnWebsite(PodcastShow $show)` — `website_enabled = true` AND `website_publish_on < now()`, ordered by `website_publish_on` descending

## Phase 2 — Additional Content Sources

- The content source architecture is designed to be extensible
- New source types can be added by creating a new feature folder, model, and implementing the shared source interface/contract
- The polymorphic `list_sources` and `summaries` tables accommodate new types without schema changes

## Nightly Pipeline (Automation Phase)

1. `FetchNewContent` job runs on scheduler
2. For each source, check RSS feed for new items since `last_fetched_at`
3. For YouTube: fetch transcript via `get_transcript.py` Python script
4. Send transcript/content to Gemini for summarisation
5. Store result in `summaries` table
6. Deliver digest via the resolved delivery strategy
7. Mark summaries as included (DigestBuilderService::markAsIncluded())
8. Prune old data based on retention policy (DigestRetentionService::pruneForList())
9. Update lists.last_run_at

## YouTube RSS Feed

- URL format: `https://www.youtube.com/feeds/videos.xml?channel_id=UCxxxxxx`
- Returns up to 15 most recent videos
- Each entry contains `<yt:videoId>` directly — no URL resolving needed
- Published date is ISO 8601, parsed natively by Carbon

## Services

- `RssFeedService` — fetches and parses RSS feeds
- `SftpService` — handles SFTP connection testing and file delivery
- `DeployHookTriggerService` — fires deploy hook URLs and records outcomes
- `DigestApiService` — queries published digests for the API endpoint
- `DeliveryStrategyResolver` — resolves delivery strategy by OutputType
- `DigestRetentionService` — prunes old digest data based on per-list retention_count

## RAG / Vector Search (Proof of Concept)

- pgvector extension on PostgreSQL
- Embeddings generated via external API
- Stored in `article_chunks` table with a `vector` column
- Cosine similarity search for semantic retrieval