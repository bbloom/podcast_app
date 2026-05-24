# RSS Pipeline Reorder — Feature Planning Document

> Status: **Planned — not started**
> Lives at: `MEDIA_PLATFORM/Podcasts/RSS_PIPELINE_REORDER_PLAN.md`

---

## Problem Statement

The current post-production pipeline generates and validates the RSS feed *before* publishing the episode to the website. With a static site (Astro on Cloudflare Pages), the episode webpage URL does not exist until after a site build completes. This means:

1. External RSS validators that check whether the episode's webpage URL resolves (`<link>` element in each `<item>`) will report a false failure during staging validation — the page does not exist yet.
2. In the past (dynamic site), `website_enabled = true` made the page immediately live. This is no longer the case.
3. The staging validation step (GenerateRssFeed Step 4) validates against a temporary S3 URL, not the live feed URL. Some validators follow the `<atom:link rel="self">` self-reference, which points to the live URL — meaning staging validation is structurally incomplete.

The correct principle: **the RSS feed should go live last**, after everything it references already resolves correctly — audio file (S3/R2) and episode webpage (static site).

---

## Guiding Principles

- No racing bots. The RSS feed must not go live until the episode webpage is confirmed live.
- One external validation step only — against the **live RSS URL**, post-promotion.
- Validation must be meaningful: audio file URL resolves, episode webpage resolves.
- The Cloudflare build confirmation is a **manual step** for now (no API polling).
- Cloudflare does not send build-completion webhooks. Auphonic does; Cloudflare does not.
- The internal pre-generation field validation (Step 2) is retained — it catches bad episode data before any upload happens.

---

## Current Pipeline Order

```
1. Upload Recording           → ready_for_auphonic
2. Auphonic Processing        → ready_to_upload_production_file
3. Upload Production Audio    → ready_to_generate_rss_feed
4. Generate RSS Feed          → ready_to_publish
   (includes staging validation against temporary S3 URL)
5. Publish on Website         → published
   (triggers static site build)
```

---

## New Pipeline Order

```
1. Upload Recording              → ready_for_auphonic            (unchanged)
2. Auphonic Processing           → ready_to_upload_production_file (unchanged)
3. Upload Production Audio       → ready_to_publish_website       (NEW status)
4. Publish on Website            → website_published              (NEW status — internal)
5. Trigger Static Site Build     → build_triggered                (NEW status — internal, or combined with step 4)
6. Confirm Build Complete        → ready_to_generate_rss_feed     (manual confirmation step)
7. Generate RSS Feed             → ready_to_publish               (unchanged name, new position)
   (no staging validation — promote directly after generation)
8. Validate Live RSS Feed        → confirmed by user              (NEW step, live URL)
9. Done → Publish on Website done page                            (existing done page adapted)
```

### Notes on status changes
- Two new intermediate statuses are needed: one between Upload Production Audio and Publish on Website, one between Publish on Website and Generate RSS Feed.
- Exact enum case names to be decided at implementation time — follow existing naming convention (snake_case, descriptive).
- `ready_to_generate_rss_feed` is retained as the entry point for GenerateRssFeed but now set by the build confirmation step rather than Upload Production Audio cleanup.
- `ready_to_publish` is retained as the exit status of GenerateRssFeed — unchanged meaning.

---

## Changes Required

### PodcastEpisodeStatus enum
- Add: `ready_to_publish_website` (or similar) — set by UploadProductionAudio cleanup done page continuation
- Add: `website_published` (or similar) — set by PublishOnWebsite store
- `postProductionShowRoute()` updated for new statuses
- Existing cases (`ready_to_generate_rss_feed`, `ready_to_publish`, `published`) unchanged

### New migration
- Add two new enum values to `podcast_episodes_published.status` column (or adjust column type if not using DB enum)

### UploadProductionAudio done page
- Primary button changes from "Continue to Generate RSS Feed" → "Continue to Publish on Website"

### GenerateRssFeed done page
- Primary button changes from "Continue to Publish on Website" → validation confirmation (or the existing publish on website done flow)

### PublishOnWebsite
- Controller: after setting `website_enabled = true`, advance status to `website_published`, redirect to trigger builds

### Trigger Builds
- Existing trigger builds flow is already separate from PublishOnWebsite
- After triggering, redirect to a new **Build Confirmation** step
- Build Confirmation: a simple page — "Your Cloudflare build has been triggered. When the build completes (check your Cloudflare dashboard), click Confirm." — sets status to `ready_to_generate_rss_feed`, routes to GenerateRssFeed Step 1

### GenerateRssFeed wizard
- **Remove** Step 4 (external staging validation) entirely
- Step 3 (generate + stage) stays, but "stage" step may be simplified or collapsed — the staging bucket upload exists to allow the XML to be read by Step 5 (promote). It is still needed.
- After Step 5 (promote to live), redirect to new **Live Validation** step instead of done page

### Live Validation step (new)
- New controller and view in GenerateRssFeed or as a standalone post-production step
- Presents the **live RSS feed URL** (not staging) for copy/paste into external validators
- Same three validator links: Cast Feed Validator, Podbase, Podcastpage
- Two actions: "Validation Passed → Done" and "Something failed → episode show page"
- This replaces the staging validation step entirely

### RegenerateRssFeed wizard
- Remove staging validation links from the StageController view
- After PromoteController succeeds, redirect to a live validation step (same pattern as above)

### Dashboard Continue buttons
- `postProductionShowRoute()` must map new statuses to correct routes
- Dashboard post-production section must show correct Continue targets for new statuses

---

## What Is Not Changing

- UploadRecording — unchanged
- AuphonicProcessing — unchanged
- UploadProductionAudio internals — only the done page continuation target changes
- GenerateRssFeed internal field validation (Step 2) — retained as-is
- GenerateRssFeed XML generation and S3/R2 promotion logic — unchanged
- All existing done pages — UploadRecording, AuphonicProcessing, UploadProductionAudio done pages remain; GenerateRssFeed done page adapts
- PublishOnWebsite core logic — `website_enabled = true` logic unchanged; status advancement and redirect target change

---

## Open Questions (to resolve at implementation time)

1. **Exact new status enum case names** — propose at start of implementation, confirm before writing migrations.
2. **Build Confirmation step location** — does it live inside PublishOnWebsite feature folder, or as a new standalone `BuildConfirmation/` feature under PostProduction?
3. **Live Validation step location** — inside GenerateRssFeed, or a new standalone step?
4. **RegenerateRssFeed live validation** — should it share the same controller/view as GenerateRssFeed live validation, or be a separate (but identical) implementation?
5. **Cloudflare API polling** — manual confirmation is the starting point. If the Cloudflare REST API (build status endpoint) is worth implementing later, it can replace the manual step without changing the rest of the pipeline.

---

## Implementation Order (proposed)

1. New migration — add two status enum cases
2. `PodcastEpisodeStatus` enum — add cases, update `postProductionShowRoute()`
3. UploadProductionAudio done page — update continuation target
4. PublishOnWebsite controller — update status advancement and redirect
5. Build Confirmation step — new controller, route, view, test
6. GenerateRssFeed — remove Step 4, add Live Validation step after Step 5
7. RegenerateRssFeed — remove staging validator links, add live validation after promote
8. Dashboard — verify Continue buttons for all new statuses
9. Full test suite pass

---

## Cloudflare Build Completion — For Reference

Cloudflare Pages does **not** send outbound build-completion webhooks. The app cannot be notified automatically. Options if manual confirmation becomes friction:

- **Cloudflare API polling** — after triggering a build, the app stores `last_build_id`. The Cloudflare REST API (`GET /client/v4/accounts/{account_id}/pages/projects/{project_name}/deployments/{deployment_id}`) returns build status. A poll loop or scheduled job could check this and auto-advance the status. Requires storing a Cloudflare API token per show.
- **Manual confirmation** (current plan) — simple, honest, reliable. Zero API complexity. The build takes 1–3 minutes; the user checks the Cloudflare dashboard and clicks Confirm.

Manual confirmation is the correct starting point.