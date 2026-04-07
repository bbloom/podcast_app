# Architecture

## Overview
A Laravel application that aggregates content from YouTube channels, podcasts, and text-based RSS feeds. Content is fetched nightly, summarised using Gemini AI, and delivered to the user via configurable output destinations.

## Core Features
- **Content Sources:** YouTube channels, podcasts, text-based RSS feeds
- **Nightly Pipeline:** Fetches new content, generates AI summaries, publishes digests
- **Output Destinations:** Configurable delivery (e.g. SFTP)
- **Lists:** Users group content sources into lists for organised digest delivery

## Database Tables
- `youtube_channels` — stores channel ID, name, last_fetched_at
- `podcasts` — stores podcast RSS URL, name, last_fetched_at
- `text_based_rss_feeds` — stores feed URL, name, last_fetched_at
- `lists` — user-defined groupings of content sources
- `list_sources` — polymorphic pivot joining lists to any source type
- `summaries` — AI-generated summaries, polymorphic to source type
- `output_destinations` — where digests are delivered (e.g. SFTP)
- `language_models` — available AI models for summarisation
- `podcast_guest_episode` — pivot table joining guests to episodes (PodcastStudio)
- `podcast_link_episode` — pivot table joining links to episodes (PodcastStudio)

## Polymorphic Relationships
Content sources (`YoutubeChannel`, `Podcast`, `TextBasedRssFeed`) are related to lists and summaries via polymorphic relationships using morph aliases.

## AI Provider Strategy
- Currently using Gemini (`gemini-2.5-flash`) for its generous free tier during prototyping
- The app must **not** be hard-coded to Gemini — AI providers are swappable
- A `language_models` table stores available models and providers
- Summarisation is abstracted behind a service/interface so the provider can be changed or configured per user without touching pipeline logic
- Future candidates: OpenAI, GitHub Copilot, Anthropic, or others

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
6. Deliver digest to configured output destinations

## YouTube RSS Feed
- URL format: `https://www.youtube.com/feeds/videos.xml?channel_id=UCxxxxxx`
- Returns up to 15 most recent videos
- Each entry contains `<yt:videoId>` directly — no URL resolving needed
- Published date is ISO 8601, parsed natively by Carbon

## Services
- `RssFeedService` — fetches and parses RSS feeds
- `SftpService` — handles SFTP connection testing and file delivery

## RAG / Vector Search (Proof of Concept)
- pgvector extension on PostgreSQL
- Embeddings generated via external API
- Stored in `article_chunks` table with a `vector` column
- Cosine similarity search for semantic retrieval