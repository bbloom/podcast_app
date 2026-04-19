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
- `podcast_guest_episode` — pivot table joining guests to episodes (PodcastStudio)
- `podcast_link_episode` — pivot table joining links to episodes (PodcastStudio)
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

## PodcastStudio

- Active feature — `PodcastStudio/Management/` is in use with Controllers, Models, Requests, and Routes
- Manages shows, episodes, statuses, links, and guests
- Episodes relate to guests via the `podcast_guest_episode` pivot table
- Episodes relate to links via the `podcast_link_episode` pivot table
- Pre-production wizard (`PreProduction/CreateEpisode/`) is complete — `Step1Controller`, `Step2Controller`, and `Step3Controller` handle show selection, episode details, and full field population including all RSS-critical fields
- Post-production foundation is in place — `PostProduction/` contains enums, a dashboard controller, and routes
- Post-production pipeline status is tracked via the `PodcastEpisodeStatus` enum on the `podcast_episodes.status` column
- Cloud storage credentials live in `config/podcast_post_production.php`, read from `.env`
- Bucket names, providers, file types, and Auphonic preset UUIDs are defined as PHP enums under `PostProduction/Enums/`
- `UploadRecording` is complete — handles pre-signed S3 PUT upload, S3 file confirmation, and status advancement to `ready_for_auphonic`
- `AuphonicProcessing` is complete — handles S3 file verification, Auphonic submission, webhook processing, MP3 download, and clean-up; see `AuphonicProcessing/README.md` for full detail
- `UploadProductionAudio` is complete — handles the two-path MP3 upload (Auphonic download or manual upload from local machine), getID3 metadata extraction, S3 and R2 upload, and clean-up; see `UploadProductionAudio/README.md` for full detail
- `GenerateRssFeed` is complete — generates the RSS XML feed, validates it, uploads to staging for external validation, promotes to live S3 and R2, and advances the episode status to `ready_to_publish`; see `GenerateRssFeed/README.md` for full detail
- `PublishOnWebsite` is complete — sets `website_enabled = true`, advances the episode status to `published`, and when `website_publish_on <= today` redirects to the "Trigger Static Site Builds" step
- `RegenerateRssFeed` is complete — a show-level maintenance flow that rebuilds the entire RSS feed from all eligible episodes, uploads to staging for external validation, and promotes to live S3 and R2; operates independently of any episode's pipeline status

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