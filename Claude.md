# Claude Code Instructions

## General Rules
- Do not delete, reformat, or reorganise any existing code unless explicitly instructed
- Only make the specific changes listed in the prompt
- Do not add comments or change whitespace outside the modified lines
- Do not "improve" or "clean up" code that was not mentioned in the prompt
- Show me what you plan to do before making any changes
- After making changes, run the test suite: `docker compose restart && php artisan test`
- Always ask before generating code — do not assume approval

## Project Context
- Laravel Framework version 13, PHP 8.5 application, on Debian 13
- Local and Production environments are on Docker
- Test suite: PHPUnit — run with `docker compose restart && php artisan test`
- **Always restart Docker before running tests** — FrankenPHP uses opcache; PHP file changes are not picked up until the container restarts
- Always follow existing conventions — reference nearby files for patterns
- Never modify .env
- Never modify any .env files, whose files start with: ".env"

## Podcasts Context
- The Podcasts module lives at `MEDIA_PLATFORM/Podcasts/`
- The production pipeline: Planning → PrepareForPublishingWizard (hard handoff) → Post-Production → Publishing
- Planning records live in `podcast_episodes_planning`; hard-deleted on publishing
- Published episodes are stored in `podcast_episodes_published`; the API serves from this table
- Planning statuses use `PodcastEpisodePlanningStatus` enum at `MEDIA_PLATFORM/Podcasts/Planning/CRUD/Enums/`
- Production statuses use `PodcastEpisodeStatus` enum at `MEDIA_PLATFORM/Podcasts/Publishing/Enums/`
- Post-production pipeline (RSS Pipeline Reorder order):
  `ready_to_upload_recording` → `ready_for_auphonic` → `processing_at_auphonic` → `auphonic_complete` → `ready_to_upload_production_file` → `ready_to_publish_website` → `website_published` → `build_triggered` → `ready_to_generate_rss_feed` → `ready_to_upload_rss_feed` → `published`; also `rss_validation_failed`, `not_published`, `ready_to_publish` (legacy)
- Each post-production stage completion redirects to a `DoneController` page — not back to an index. The done page carries the episode forward to the next stage automatically.
- Controllers listing podcast shows use `private const ACTIVE_SHOWS` for consistent filtering and ordering
- Migrations live at `database/migrations/media_platform/podcasts/`
- One table per migration file

## Guest Email Context
- Guest email feature is under active development — `INBOUND_EMAIL/` and `INBOUND_EMAIL_PROVIDERS/` are new internal packages
- Provider: **Postmark** (not SES). Domain: `bobbloominterviews.com`
- Reference: `INBOUND_EMAIL/EMAIL_PLUMBING.md` for mechanics, `INBOUND_EMAIL/EMAIL_PLUMBING_IMPLEMENTATION_PLAN.md` for build plan
- PSR-4 namespaces registered: `InboundEmail\\` → `INBOUND_EMAIL/`, `InboundEmailProviders\\` → `INBOUND_EMAIL_PROVIDERS/`
- Webhook authentication uses HTTP Basic Auth embedded in the webhook URL — credentials in `.env` as `POSTMARK_WEBHOOK_USER` / `POSTMARK_WEBHOOK_PASSWORD`

## Git
- Do not run git add, git commit, or git push — that is the developer's responsibility

## Project Documentation
Before making any changes, read these files for context:
- `ARCHITECTURE.md`
- `CONVENTIONS.md`
- `MEDIA_PLATFORM/Podcasts/HANDOFF.md`
- `INBOUND_EMAIL/EMAIL_PLUMBING.md` — if working on the guest email feature
- `INBOUND_EMAIL/EMAIL_PLUMBING_IMPLEMENTATION_PLAN.md` — if working on the guest email feature

## Interaction

- One step at a time
- Ask if ready for Claude to proceed
- Be honest in all assessments, critiques, and recommendations
- Ask for what you need
- When asking questions, do not use the modal input box
- Do not be "glib". Be accurate, precise, and thorough
- When identifying friction areas or workflow issues, focus on drawing out the problems before proposing solutions
- The goal of the app is not the app itself — the goal is excelling at podcasting. Keep this perspective in all recommendations