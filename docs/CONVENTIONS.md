# Podcast App — Conventions & Current State

## What This Project Is

A Laravel/PHP podcasting application:

- for producing and publishing 5 podcast shows. The app handles the full episode lifecycle: planning/creative, audio production, RSS feed generation, and website publishing.

- that aggregates content from YouTube channels, podcasts, and text-based RSS feeds. Content is fetched nightly, summarised using Gemini AI, and delivered to the user via configurable output destinations.

## Current State

**Phases 1, 2, 3, post-Phase-3 UI pass, Planning UI pass, FinalizeScriptWizard refactor, Post-Production flow fix, and RSS Pipeline Reorder are complete and pushed.**

- Phase 1: Structural reshuffle — `PodcastStudio/` → `Podcasts/`
- Phase 2: Small standalone additions (table rename, user_id on links, show templates, enum case)
- Phase 3: Planning module — all wizards, field editors, CRUD, attach/detach, PrepareForPublishingWizard
- Post-Phase-3: Dashboard rewritten, status enums moved, RecordingView built, UI pass
- Planning UI pass: All Planning views restyled — show image in headers, `text-base` body, stacked buttons, breadcrumbs
- FinalizeScriptWizard refactor: Expanded from 7 to 9 steps — dual-textarea AI proofing, inline intro/outro template create/edit, `script_scratch` column added, dashboard advisory
- Post-Production flow fix: Four "Done" pages added — completing a pipeline stage now lands on a "Stage Complete — what next?" page with a direct "Continue to [Next Stage] →" button. No re-selection of episode needed.
- RSS Pipeline Reorder: Website published and static site build confirmed before RSS generation. Four new statuses. Cloudflare build status polling via `CloudflareBuildStatusService`. GenerateRssFeed split into S3-only promote + Live Validation + R2 promote. RegenerateRssFeed updated to match. Dashboard surfaces in-progress and needs-attention episodes.

**Test suite: 1536 passing, 3503 assertions.**

---

## Folder Structure (`MEDIA_PLATFORM/Podcasts/`)

```
MEDIA_PLATFORM/
└── Podcasts/
    ├── ArchivedEpisodes/
    ├── Dashboard/
    │   └── Controllers/
    ├── Guests/
    │   ├── Controllers/ / Models/ / Requests/ / Routes/
    ├── Links/
    │   ├── Controllers/ / Models/ / Requests/ / Routes/
    ├── Planning/
    │   ├── CRUD/
    │   │   ├── Controllers/
    │   │   │   ├── PodcastEpisodePlanningController.php
    │   │   │   ├── PodcastEpisodePlanningGuestController.php
    │   │   │   └── PodcastEpisodePlanningLinkController.php
    │   │   ├── Enums/
    │   │   │   └── PodcastEpisodePlanningStatus.php
    │   │   ├── Models/
    │   │   │   └── PodcastEpisodePlanning.php
    │   │   ├── Requests/
    │   │   │   └── PodcastEpisodePlanningRequest.php
    │   │   └── Routes/
    │   ├── CreateEpisodeWizard/      Controllers/ (Step1–4)
    │   ├── EditThemeField/           Controllers/
    │   ├── EditScriptField/          Controllers/
    │   ├── FinalizeScriptWizard/     Controllers/ (Step1–9)
    │   ├── RecordingView/            Controllers/ + Routes/
    │   └── PrepareForPublishingWizard/
    │       ├── Concerns/
    │       │   └── DerivesPublishedEpisodeFields.php
    │       └── Controllers/ (Step1–3)
    ├── Publishing/
    │   ├── Controllers/ / Enums/ / Models/ / Requests/ / Routes/
    │   │   └── PodcastEpisodeStatus.php   ← in Enums/
    │   │   └── PodcastEpisode.php         ← in Models/, table: podcast_episodes_published
    │   └── PostProduction/
    │       ├── AuphonicProcessing/    Controllers/ (incl. DoneController)
    │       ├── BuildConfirmation/     Controllers/ (ShowController, ConfirmController)
    │       ├── CloudStorage/
    │       ├── Dashboard/
    │       ├── GenerateRssFeed/       Controllers/ (Step1–3, Step4†, Step5,
    │       │                           LiveValidationController, RestartController,
    │       │                           DoneController)
    │       ├── PublishOnWebsite/      Controllers/ (IndexController, ShowController,
    │       │                           PublishController, PrepareTriggerBuildsController,
    │       │                           TriggerBuildsController, TriggerBuildsResultController)
    │       ├── RegenerateRssFeed/     Controllers/ (incl. LiveValidationController)
    │       ├── UploadProductionAudio/ Controllers/ (incl. DoneController)
    │       ├── UploadRecording/       Controllers/ (incl. DoneController)
    │       └── Routes/
    └── Shows/
        ├── Controllers/ / Models/ / Requests/ / Routes/
        └── PodcastShow.php   ← in Models/
```

† `Step4Controller` is intentionally empty and deprecated — retained to explain the gap in step numbering. See its file header.

---

## Key Models and Namespaces

| Model | Namespace | Table |
|---|---|---|
| `PodcastEpisodePlanning` | `MediaPlatform\Podcasts\Planning\CRUD\Models` | `podcast_episodes_planning` |
| `PodcastEpisodePlanningStatus` (enum) | `MediaPlatform\Podcasts\Planning\CRUD\Enums` | — |
| `PodcastEpisode` | `MediaPlatform\Podcasts\Publishing\Models` | `podcast_episodes_published` |
| `PodcastEpisodeStatus` (enum) | `MediaPlatform\Podcasts\Publishing\Enums` | — |
| `PodcastShow` | `MediaPlatform\Podcasts\Shows\Models` | `podcast_shows` |
| `PodcastGuest` | `MediaPlatform\Podcasts\Guests\Models` | `podcast_guests` |
| `PodcastLink` | `MediaPlatform\Podcasts\Links\Models` | `podcast_links` |

---

## Database Tables (current)

- `podcast_episodes_planning` — planning/creative workspace. Hard-deleted on publishing.
  - `script_scratch` — nullable text. Ephemeral AI scratch pad (FinalizeScriptWizard Step 4). Cleared by Step 9. Non-null triggers dashboard advisory.
- `podcast_episodes_published` — live published episodes. API serves from this table.
- `podcast_shows` — has `intro_template` and `outro_template` (mandatory; wizard enforces creation)
- `podcast_links` — has `user_id`
- `podcast_guests`
- `podcast_guest_episode_planning` — pivot: guests ↔ planning episodes
- `podcast_guest_episode` — pivot: guests ↔ published episodes
- `podcast_link_episode_planning` — pivot: links ↔ planning episodes
- `podcast_link_episode` — pivot: links ↔ published episodes

---

## Status Enums

### `PodcastEpisodePlanningStatus`
```
new_episode_created          → set by Create Episode Wizard
working_on_theme             → set manually
writing_script               → set manually
ready_to_finalize_the_script → set manually — entry point: FinalizeScriptWizard
ready_to_record              → set by FinalizeScriptWizard Step 9
raw_audio_needs_editing      → set manually
ready_for_publishing         → set manually — entry point: PrepareForPublishingWizard
```
`sortOrder(): int` — pipeline-ordered dashboard sorting.

### `PodcastEpisodeStatus`
```
ready_to_upload_recording       → pipeline entry point (set by PrepareForPublishingWizard Step 3)
ready_for_auphonic
processing_at_auphonic
auphonic_complete
ready_to_upload_production_file
ready_to_publish_website        → set by UploadToStorageController (RSS Pipeline Reorder)
website_published               → set by PublishController (RSS Pipeline Reorder)
build_triggered                 → set by TriggerBuildsController in pipeline context
ready_to_generate_rss_feed      → set by BuildConfirmation / RestartController
ready_to_upload_rss_feed        → set by Step5Controller: RSS on live S3, awaiting R2 upload
rss_validation_failed           → set by LiveValidationController::fail(); needs attention
ready_to_publish                → legacy; retained for backwards compatibility
published
not_published                   → set manually
```
`postProductionShowRoute(): string` — maps each status to its pipeline route for dashboard Continue buttons.

---

## Post-Production Pipeline — Current State (RSS Pipeline Reorder complete)

| Stage | Entry status | Exit status | Done route |
|---|---|---|---|
| UploadRecording | `ready_to_upload_recording` | `ready_for_auphonic` | `post_production.upload_recording.done` |
| AuphonicProcessing | `ready_for_auphonic` | `ready_to_upload_production_file` | `post_production.auphonic_processing.done` |
| UploadProductionAudio | `ready_to_upload_production_file` | `ready_to_publish_website` | `post_production.upload_production_audio.done` |
| PublishOnWebsite | `ready_to_publish_website` | `website_published` | → TriggerBuilds (via session) |
| TriggerBuilds | `website_published` | `build_triggered` | → BuildConfirmation |
| BuildConfirmation | `build_triggered` | `ready_to_generate_rss_feed` | → GenerateRssFeed Step 1 |
| GenerateRssFeed | `ready_to_generate_rss_feed` | `published` (via `ready_to_upload_rss_feed`) | `post_production.generate_rss_feed.done` |

**GenerateRssFeed internal flow:** Steps 1–3 (review/validate/generate+stage) → Step 5 (upload to live S3, status → `ready_to_upload_rss_feed`) → Live Validation (validate against live S3 URL) → Promote to R2 (status → `published`).

**`rss_validation_failed`:** Set by `LiveValidationController::fail()`. Surfaced on the Post-Production Dashboard and the GenerateRssFeed index. `RestartController` resets the episode to `ready_to_generate_rss_feed` and redirects to Step 1.

---

## Wizards

### FinalizeScriptWizard (9 steps) — `wizard.finalize_script.episode_id`

| Step | Purpose |
|---|---|
| 1 | Introduction |
| 2 | Confirm episode number |
| 3 | Confirm title — regex rejects digit-leading titles |
| 4 | AI Proofing — dual textarea: `script` + `script_scratch`, both Alpine.js PATCH |
| 5 | Intro template review/create — updates `podcast_show.intro_template` |
| 6 | Prepend resolved intro to script |
| 7 | Outro template review/create — updates `podcast_show.outro_template` |
| 8 | Append resolved outro to script |
| 9 | Confirm — sets `ready_to_record`, clears `script_scratch` |

### PrepareForPublishingWizard (3 steps) — `wizard.prepare_for_publishing.episode_id`
- Step 3 store: runs `DerivesPublishedEpisodeFields`, creates published record, migrates guests + links, hard-deletes planning record, sets `ready_to_upload_recording`

### CreateEpisodeWizard (4 steps) — `wizard.create_episode_planning.podcast_show_id`
- Step 4: "what's next" — Create another / Details / Work on theme / Work on script / Add guests / Dashboard

---

## Key Route Names

```
# Planning
podcast_episodes_planning.index / .show / .edit / .update / .delete.confirm / .destroy
podcast_episodes_planning.wizard.create.step1–4
podcast_episodes_planning.wizard.finalize.step1–9
podcast_episodes_planning.wizard.finalize.step4.save_scratch   ← PATCH, JSON
podcast_episodes_planning.wizard.finalize.step5.store – step9.store
podcast_episodes_planning.wizard.publish.step1–3
podcast_episodes_planning.theme.show / .save / .save_exit
podcast_episodes_planning.script.show / .save / .save_exit
podcast_episodes_planning.guests.attach.index / .attach / .detach
podcast_episodes_planning.links.attach.index / .attach / .detach
podcast_episodes_planning.recording.show

# Post-Production Done Pages
post_production.upload_recording.done
post_production.auphonic_processing.done
post_production.upload_production_audio.done
post_production.generate_rss_feed.done

# Post-Production Pipeline (RSS Pipeline Reorder additions)
post_production.prepare_trigger_builds          ← bridge: episode → TriggerBuilds select
post_production.trigger_builds.select
post_production.trigger_builds.trigger
post_production.trigger_builds.results
post_production.build_confirmation.show
post_production.build_confirmation.confirm
post_production.generate_rss_feed.live_validation
post_production.generate_rss_feed.live_validation.promote
post_production.generate_rss_feed.live_validation.fail
post_production.generate_rss_feed.restart
post_production.regenerate_rss_feed.live_validation
post_production.regenerate_rss_feed.live_validation.promote

# Deploy Hooks
deploy_hooks.index / .create / .store / .show / .edit / .update
deploy_hooks.delete.confirm / .destroy
deploy_hooks.trigger.confirm / .execute / .result
deploy_hooks.build_status                        ← JSON endpoint; polled by Alpine.js
```

---

## UI Conventions

- Button labels: "Details" for show/read — never "Open" or "View"
- Table rows: `bg-gray-50` resting, `hover:bg-white`
- External links: inline SVG arrow-up-right, `target="_blank" rel="noopener noreferrer"`
- Show image: `w-16 h-16 rounded object-cover border border-purple-200` in page headers; in table cells replace title text when image present
- Body text: `text-base` — exception: buttons (`text-sm`/`text-xs`), help text (`text-xs`)
- Stacked action buttons in table rows: `flex flex-col items-end gap-1.5`
- Date cells: `whitespace-nowrap`
- Breadcrumb: `mb-4` below breadcrumb line, before `<h1>`
- Page headings: `text-3xl font-bold`
- Wizard steps: no show image
- Post-production done pages: purple/green layout, show image, episode identity, primary Continue button, secondary dashboard link
- Sortable column headers: `↕` inactive / `↑` `↓` active — `text-purple-700`, `text-base`

---

## Coding Conventions

- `docker compose restart && php artisan test --stop-on-defect 2>&1 | tee test_output.txt`
- Failures only (no stack traces): `php artisan test 2>&1 | grep -E "FAILED|Tests:" | cat | tee failures.txt`
- Namespaces: `MediaPlatform\` maps to `MEDIA_PLATFORM/`
- Factories: `Database\Factories\Media_platform\...`
- Views: `media_platform.podcasts...` dot notation
- Routes: individually declared, auth middleware per route, no `Route::resource()`
- Migrations: explicitly registered in `AppServiceProvider::loadMigrationsFrom()`; path `database/migrations/media_platform/podcasts/`
- No soft deletes on planning records
- Ownership checks: `return redirect()->route(...)->with('error', '...')` — not `abort_if()`
- Alpine.js inline save: `save()` returns JSON, `saveAndExit()` returns redirect — both testable
- Slim controllers — logic in Service classes
- Named Eloquent scopes on models — no duplicated query logic across controllers

---

## Blade Components

```
views/components/podcasts/planning/create_episode_wizard/_step_dots.blade.php
views/components/podcasts/planning/finalize_script_wizard/_step_dots.blade.php  ← 9 dots
views/components/podcasts/planning/prepare_for_publishing_wizard/_step_dots.blade.php
```

---

## Podcasts Dashboard

- `$hasPendingScratch` — amber advisory when any planning episode has non-null `script_scratch`
- Planning section: episodes grouped by show (ACTIVE_SHOWS order), sorted by `sortOrder()`
- Post-production: Continue/Monitor buttons via `postProductionShowRoute()`; excludes `published` and `not_published`
- Recently Published: last 5

## Post-Production Dashboard

- `DashboardController` queries for episodes in intermediate pipeline statuses (`website_published`, `build_triggered`, `ready_to_upload_rss_feed`, `rss_validation_failed`) and passes them as `$inProgressEpisodes`
- Dashboard view shows an **In Progress** section at the top when any such episodes exist, with a status badge and a Continue → link using `postProductionShowRoute()`
- Pipeline steps are listed in the new order: Upload Recording → Submit to Auphonic → Upload Production Audio → Publish on Website → (Trigger Builds + Build Confirmation, automatic) → Generate RSS Feed

---

## Outstanding / Deferred Items

1. **`ready_to_upload_recording`** — marked for removal once entry point changes to `ready_for_publishing`. Deferred.
2. **Post-Production pipeline entry point** — currently `ready_to_upload_recording`. Will change to `ready_for_publishing`. Deferred.
3. **UI review** — Post-Production and Publishing views not yet reviewed for consistency with Planning UI conventions.
4. **Guest Interaction feature** — inline guest creation inside wizards. Out of scope for now.

---

## Test Commands

```bash
# Full suite
docker compose restart && php artisan test 2>&1 | tee test_output.txt

# Stop on first failure
docker compose restart && php artisan test --stop-on-defect 2>&1 | tee test_output.txt

# Failures only — no stack traces
docker compose restart && php artisan test 2>&1 | grep -E "FAILED|Tests:" | cat | tee failures.txt

# Planning tests only
php artisan test tests/Feature/MEDIA_PLATFORM/Podcasts/Planning/

# Publishing tests only
php artisan test tests/Feature/MEDIA_PLATFORM/Podcasts/Publishing/
```