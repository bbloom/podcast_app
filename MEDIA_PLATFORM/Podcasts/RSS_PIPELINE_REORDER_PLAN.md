# RSS Pipeline Reorder — Feature Planning Document

> Status: **Complete**
> Lives at: `MEDIA_PLATFORM/Podcasts/RSS_PIPELINE_REORDER_PLAN.md`
> Test suite after completion: **1536 passing, 3503 assertions**

---

## Problem Statement

The current post-production pipeline generated and validated the RSS feed *before* publishing the episode to the website. With a static site (Astro on Cloudflare Pages), the episode webpage URL did not exist until after a site build completed. This meant:

1. External RSS validators that check whether the episode's webpage URL resolves (`<link>` element in each `<item>`) reported false failures during staging validation — the page did not exist yet.
2. In the past (dynamic site), `website_enabled = true` made the page immediately live. This was no longer the case.
3. The staging validation step (GenerateRssFeed Step 4) validated against a temporary S3 URL, not the live feed URL. Some validators follow the `<atom:link rel="self">` self-reference, which points to the live URL — meaning staging validation was structurally incomplete.

The correct principle: **the RSS feed goes live last**, after everything it references already resolves correctly — audio file (S3/R2) and episode webpage (static site).

---

## Implemented Pipeline Order

```
1. Upload Recording              → ready_for_auphonic                (unchanged)
2. Auphonic Processing           → ready_to_upload_production_file   (unchanged)
3. Upload Production Audio       → ready_to_publish_website          (new status)
4. Publish on Website            → website_published                 (new status)
5. Trigger Static Site Build     → build_triggered                   (new status)
6. Build Confirmation            → ready_to_generate_rss_feed        (automated via Cloudflare API)
7. Generate RSS Feed (Steps 1–3) — generate XML, upload to WIP staging
8. Promote to Live S3 (Step 5)   → ready_to_upload_rss_feed          (repurposed status)
9. Live Validation               — validate against live S3 URL
10. Promote to R2                → published
```

---

## New Statuses Added

| Enum case | Value | Set by | Meaning |
|---|---|---|---|
| `ready_to_publish_website` | `ready-to-publish-website` | `UploadToStorageController` | MP3 on S3+R2; entry for PublishOnWebsite |
| `website_published` | `website-published` | `PublishController` | Website live; entry for TriggerBuilds |
| `build_triggered` | `build-triggered` | `TriggerBuildsController` | Deploy hooks fired; entry for BuildConfirmation |
| `rss_validation_failed` | `rss-validation-failed` | `LiveValidationController::fail()` | Validation failed; needs attention |

`ready_to_upload_rss_feed` was **repurposed**: previously mapped to the removed Step 4 (staging validation); now means "RSS XML is on the live S3 bucket, awaiting R2 upload after user validates".

`ready_to_publish` is **retained** for backwards compatibility with episodes that entered the pipeline before this reorder was deployed.

---

## Open Questions — Resolved

1. **Exact new status enum case names** → `ready_to_publish_website`, `website_published`, `build_triggered`, `rss_validation_failed`

2. **Build Confirmation step location** → Standalone `BuildConfirmation/` feature folder under `PostProduction/` (`ShowController`, `ConfirmController`)

3. **Live Validation step location** → Inside `GenerateRssFeed/` as `LiveValidationController` (three actions: `show`, `promoteToR2`, `fail`)

4. **RegenerateRssFeed live validation** → Separate implementation in `RegenerateRssFeed/LiveValidationController` — same pattern, show-level (no episode status changes, no `fail()` action)

5. **Cloudflare API polling** → **Implemented** — not deferred. `CloudflareBuildStatusService` polls the Cloudflare Pages REST API using `last_build_id` from `deploy_hooks.last_build_id`. Alpine.js auto-polls every 5 seconds on the BuildConfirmation page. A scoped Cloudflare API token (`Account / Pages / Read`) is stored in `config/podcast_post_production.php`. Manual confirmation fallback is always available. See `CloudflareBuildStatusService` and `BuildStatusController`.

---

## Implementation — What Was Built

### New files
- `CloudflareBuildStatusResult` — `StaticSiteDeployHooks/Services/`
- `CloudflareBuildStatusService` — `StaticSiteDeployHooks/Services/`
- `BuildStatusController` — `StaticSiteDeployHooks/Controllers/` (JSON endpoint for Alpine.js polling)
- `BuildConfirmation/ShowController` — auto-polls build status; manual fallback
- `BuildConfirmation/ConfirmController` — advances status to `ready_to_generate_rss_feed`
- `PrepareTriggerBuildsController` — bridge controller: episode → TriggerBuilds session setup
- `GenerateRssFeed/LiveValidationController` — `show`, `promoteToR2`, `fail`
- `GenerateRssFeed/RestartController` — resets `rss_validation_failed` / `ready_to_upload_rss_feed` → `ready_to_generate_rss_feed`, redirects to Step 1
- `RegenerateRssFeed/LiveValidationController` — `show`, `promoteToR2`
- `build_confirmation.php` routes
- `step3.blade.php`, `live_validation.blade.php` (×2), `live_validation.blade.php` (RegenerateRssFeed)

### Modified files
- `PodcastEpisodeStatus` — four new cases; `postProductionShowRoute()` updated; `label()` updated
- `UploadToStorageController` — exit status `ready_to_generate_rss_feed` → `ready_to_publish_website`
- `CleanUpController` — status guard updated to `ready_to_publish_website`
- `done.blade.php` (UploadProductionAudio) — button → "Continue to Publish on Website"
- `PublishController` — exit status → `website_published`; stores episode ID in session for TriggerBuilds
- `TriggerBuildsController` — pipeline context detection; advances episode to `build_triggered`; redirects to BuildConfirmation
- `PublishOnWebsite/ShowController` — accepts both `ready_to_publish_website` and `ready_to_publish`
- `PublishOnWebsite/IndexController` — shows both statuses
- `GenerateRssFeed/Step4Controller` — **deprecated** (emptied, retained for step-numbering clarity)
- `GenerateRssFeed/Step5Controller` — uploads to live S3 only (R2 deferred to Live Validation)
- `GenerateRssFeed/IndexController` — also shows `rss_validation_failed` episodes
- `generate_rss_feed.php` routes — Step 4 removed; live validation + restart added
- `PromoteController` (RegenerateRssFeed) — uploads to live S3 only; redirects to Live Validation
- `stage.blade.php` (RegenerateRssFeed) — staging validator links removed; button → "Upload to Live S3"
- `regenerate_rss_feed.php` routes — live validation added
- `done.blade.php` (GenerateRssFeed) — updated: episode is fully published at this point; "Continue to Publish on Website" button removed
- `DashboardController` (Post-Production) — passes `$inProgressEpisodes` to view
- `dashboard.blade.php` (Post-Production) — In Progress section added; pipeline steps in new order
- `config/podcast_post_production.php` — `cloudflare.api_token` added
- `deploy_hooks/show.blade.php` — "Check Build Status" section added for Cloudflare hooks

### S3 / R2 split — GenerateRssFeed and RegenerateRssFeed

Previously Step 5 uploaded to both S3 and R2 in one pass. The split:
- **Step 5 / PromoteController** — uploads to live S3 only. S3 is used for validation (not public-facing for podcast directories).
- **Live Validation** — user validates the live S3 URL against external validators. All referenced URLs now resolve (audio file on R2, episode webpage live).
- **Promote to R2** — R2 is the public-facing CDN polled by Apple Podcasts, Spotify, etc. Promoted only after validation confirms the feed is correct.

---

## Implementation Order — Completed

1. ✅ New migration — string column; no DB migration needed
2. ✅ `PodcastEpisodeStatus` enum — new cases, updated routes and labels
3. ✅ UploadProductionAudio done page — button updated
4. ✅ PublishOnWebsite controller — status + session
5. ✅ Build Confirmation step — controllers, routes, view, tests; Cloudflare API polling implemented
6. ✅ GenerateRssFeed — Step 4 deprecated, Step 5 S3-only, Live Validation added
7. ✅ RegenerateRssFeed — staging validators removed, Live Validation added
8. ✅ Dashboard — In Progress section, new pipeline order
9. ✅ Full test suite pass — 1536 passing, 3503 assertions