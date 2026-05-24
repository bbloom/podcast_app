# Podcast App — Context Handoff

## What This Project Is

A Laravel/PHP podcasting application for producing and publishing 5 podcast shows.
The app handles the full episode lifecycle: planning/creative, audio production,
RSS feed generation, and website publishing.

## Current State

**Phases 1, 2, 3, post-Phase-3 UI pass, Planning UI pass, FinalizeScriptWizard refactor, and Post-Production flow fix are complete and pushed.**

- Phase 1: Structural reshuffle — `PodcastStudio/` → `Podcasts/`
- Phase 2: Small standalone additions (table rename, user_id on links, show templates, enum case)
- Phase 3: Planning module — all wizards, field editors, CRUD, attach/detach, PrepareForPublishingWizard
- Post-Phase-3:
  - Podcasts Dashboard rewritten — planning grouped by show + status pipeline order, post-production with smart Continue/Monitor buttons
  - `PodcastEpisodeStatus` moved to `MEDIA_PLATFORM/Podcasts/Publishing/Enums/`
  - `PodcastEpisodeStatus::created` removed — pipeline now enters at `ready_to_upload_recording`
  - `PodcastEpisodePlanningStatus::sortOrder(): int` added — pipeline-ordered dashboard sorting
  - `PodcastEpisodeStatus::postProductionShowRoute(): string` added — maps status to episode-specific pipeline route
  - `PodcastShow::planningEpisodes()` relationship added
  - `podcast_episode_drafts` table dropped (migration)
  - `PodcastStudio/` legacy files deleted
  - RecordingView built (`MEDIA_PLATFORM/Podcasts/Planning/RecordingView/`)
  - UI pass: "Open"/"View" → "Details" across Planning views; table contrast updated
- Planning UI pass (complete):
  - All Planning views restyled — show image in headers, `text-base` body text, `flex-col` stacked buttons, breadcrumbs with spacing
  - `show.blade.php` — restructured with full episode management section
  - `edit.blade.php` — sectioned: Core, Creative Content, Website Content
  - All wizard views restyled — `text-3xl` headings, `text-base` body
  - `prepare_for_publishing_wizard/step1.blade.php` — amber "point of no return" section
- FinalizeScriptWizard refactor (complete):
  - Wizard expanded from 7 steps to **9 steps**
  - `script_scratch` column added to `podcast_episodes_planning`
  - Step 4: dual-textarea AI proofing (main script + scratch pad, both persisted)
  - Steps 5 and 7: inline intro/outro template review and creation
  - Step 9: clears `script_scratch`, sets `ready_to_record`
  - Dashboard advisory when `script_scratch` is non-null
- Post-Production flow fix (complete):
  - **Four "Done" pages** added — one per pipeline stage completion point
  - Each done page presents: green confirmation, episode identity, primary "Continue to [Next Stage] →" button, secondary "Post-Production Dashboard" link
  - The episode is carried forward automatically — no re-selection needed
  - Completing a stage now lands on the done page instead of an index or the dashboard
  - Four new `DoneController` classes, four new GET routes, four new views, four new test classes

**Test suite: 1420 passing, 3294 assertions.**

The GitHub `podcast_app` repository is connected to this project via the GitHub Connector.
All source code is accessible via project knowledge search.

---

## Folder Structure (`MEDIA_PLATFORM/Podcasts/`)

```
MEDIA_PLATFORM/
└── Podcasts/
    ├── ArchivedEpisodes/
    │   └── BobBloomShowArchive.php
    ├── Dashboard/
    │   └── Controllers/
    ├── Guests/
    │   ├── Controllers/
    │   ├── Models/
    │   ├── Requests/
    │   └── Routes/
    ├── Links/
    │   ├── Controllers/
    │   ├── Models/
    │   ├── Requests/
    │   └── Routes/
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
    │   │       ├── podcast_episodes_planning.php
    │   │       ├── podcast_episodes_planning_guests.php
    │   │       └── podcast_episodes_planning_links.php
    │   ├── CreateEpisodeWizard/
    │   │   └── Controllers/ (Step1–4)
    │   ├── EditThemeField/
    │   │   └── Controllers/
    │   ├── EditScriptField/
    │   │   └── Controllers/
    │   ├── FinalizeScriptWizard/
    │   │   └── Controllers/ (Step1–9)
    │   ├── RecordingView/
    │   │   ├── Controllers/
    │   │   │   └── RecordingViewController.php
    │   │   └── Routes/
    │   │       └── recording_view.php
    │   └── PrepareForPublishingWizard/
    │       ├── Concerns/
    │       │   └── DerivesPublishedEpisodeFields.php
    │       └── Controllers/ (Step1–3)
    ├── Publishing/
    │   ├── Controllers/
    │   ├── Enums/
    │   │   └── PodcastEpisodeStatus.php
    │   ├── Models/
    │   │   └── PodcastEpisode.php         ← table: podcast_episodes_published
    │   ├── Requests/
    │   ├── Routes/
    │   └── PostProduction/
    │       ├── AuphonicProcessing/
    │       │   └── Controllers/
    │       │       └── DoneController.php  ← NEW
    │       ├── CloudStorage/
    │       ├── Dashboard/
    │       ├── GenerateRssFeed/
    │       │   └── Controllers/
    │       │       └── DoneController.php  ← NEW
    │       ├── PublishOnWebsite/
    │       ├── RegenerateRssFeed/
    │       ├── UploadProductionAudio/
    │       │   └── Controllers/
    │       │       └── DoneController.php  ← NEW
    │       ├── UploadRecording/
    │       │   └── Controllers/
    │       │       └── DoneController.php  ← NEW
    │       └── Routes/
    └── Shows/
        ├── Controllers/
        ├── Models/
        │   └── PodcastShow.php
        ├── Requests/
        └── Routes/
```

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
  - `script_scratch` — nullable text. Ephemeral AI scratch pad for FinalizeScriptWizard Step 4.
    Cleared by Step 9 store. Non-null value triggers a dashboard advisory notice.
- `podcast_episodes_published` — live published episodes. API serves from this table.
- `podcast_shows` — has `intro_template` and `outro_template` columns (mandatory;
  FinalizeScriptWizard Steps 5 and 7 enforce creation if missing)
- `podcast_links` — has `user_id` column
- `podcast_guests`
- `podcast_guest_episode_planning` — pivot: guests ↔ planning episodes
- `podcast_guest_episode` — pivot: guests ↔ published episodes
- `podcast_link_episode_planning` — pivot: links ↔ planning episodes
- `podcast_link_episode` — pivot: links ↔ published episodes

---

## Post-Production Pipeline — Current State

| Stage | Entry status | Exit status | Done page route |
|---|---|---|---|
| UploadRecording | `ready_to_upload_recording` | `ready_for_auphonic` | `post_production.upload_recording.done` |
| AuphonicProcessing | `ready_for_auphonic` | `ready_to_upload_production_file` | `post_production.auphonic_processing.done` |
| UploadProductionAudio | `ready_to_upload_production_file` | `ready_to_generate_rss_feed` | `post_production.upload_production_audio.done` |
| GenerateRssFeed | `ready_to_generate_rss_feed` | `ready_to_publish` | `post_production.generate_rss_feed.done` |
| PublishOnWebsite | `ready_to_publish` | `published` | (handled separately — trigger builds) |

Each done page carries the episode forward automatically. The primary button links directly to the next stage's show route with the episode already in the URL.

**Note:** The pipeline order above is scheduled for a structural change. See `RSS_PIPELINE_REORDER_PLAN.md` for the planned feature.

---

## Planning Module

### `PodcastEpisodePlanningStatus` enum cases
```
new_episode_created          → set by Create Episode Wizard
working_on_theme             → set manually
writing_script               → set manually
ready_to_finalize_the_script → set manually (entry point: Finalize Script Wizard)
ready_to_record              → set by Finalize Script Wizard (Step 9)
raw_audio_needs_editing      → set manually
ready_for_publishing         → set manually (entry point: PrepareForPublishing Wizard)
```

### `PodcastEpisodeStatus` enum cases
```
ready_to_upload_recording       → pipeline entry point (set by PrepareForPublishingWizard Step 3)
ready_for_auphonic
processing_at_auphonic
auphonic_complete
ready_to_upload_production_file
ready_to_generate_rss_feed
ready_to_upload_rss_feed
ready_to_publish
published
not_published                   → set manually
```

### FinalizeScriptWizard (9 steps)

| Step | Purpose |
|---|---|
| 1 | Introduction — lists all 9 steps |
| 2 | Confirm episode number |
| 3 | Confirm title — regex rejects digit-leading titles |
| 4 | AI Proofing — dual textarea (main script + scratch pad, both Alpine.js, both persisted) |
| 5 | Intro template review/create — updates `podcast_show.intro_template` |
| 6 | Prepend resolved intro to script |
| 7 | Outro template review/create — updates `podcast_show.outro_template` |
| 8 | Append resolved outro to script |
| 9 | Final confirmation — sets `ready_to_record`, clears `script_scratch` |

Session key: `wizard.finalize_script.episode_id`

Intro/outro templates are **mandatory** — Steps 5 and 7 enforce creation; no skip when absent.

### PrepareForPublishingWizard (3 steps)
Session key: `wizard.prepare_for_publishing.episode_id`
- Step 3 store: runs `DerivesPublishedEpisodeFields`, creates published record, migrates guests + links, hard-deletes planning record, sets `ready_to_upload_recording`

---

## Key Route Names

```
# Planning
podcast_episodes_planning.index / .show / .edit / .update / .delete.confirm / .destroy
podcast_episodes_planning.wizard.create.step1–4
podcast_episodes_planning.wizard.finalize.step1–9
podcast_episodes_planning.wizard.finalize.step4.save_scratch   ← PATCH, JSON
podcast_episodes_planning.wizard.finalize.step5.store / step6.store / step7.store / step8.store / step9.store
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
```

---

## Blade Component Paths

```
views/components/podcasts/planning/create_episode_wizard/_step_dots.blade.php
views/components/podcasts/planning/finalize_script_wizard/_step_dots.blade.php  ← 9 dots
views/components/podcasts/planning/prepare_for_publishing_wizard/_step_dots.blade.php
```

---

## Important Context

### Two-world model
- **Planning** (`podcast_episodes_planning`) — creative/assembly workspace, freely mutable, hard-deleted on publishing
- **Published** (`podcast_episodes_published`) — permanent, touched as little as possible, API serves from here

### Podcasts Dashboard
- `$hasPendingScratch` — amber advisory when any planning episode has non-null `script_scratch`
- Planning section: episodes grouped by show (ACTIVE_SHOWS order), sorted by `sortOrder()`
- Post-production section: Continue/Monitor buttons via `postProductionShowRoute()`
- Recently Published: last 5 published episodes

### UI Conventions
- Button labels: "Details" for show/read views — never "Open" or "View"
- Table rows: `bg-gray-50` resting, `hover:bg-white`
- External links: inline SVG arrow-up-right, `target="_blank" rel="noopener noreferrer"`
- Show image: `w-16 h-16 rounded object-cover border border-purple-200` in page headers
- Body text: `text-base` — exception: buttons (`text-sm`/`text-xs`), help text (`text-xs`)
- Stacked action buttons: `flex flex-col items-end gap-1.5`
- Date cells: `whitespace-nowrap`
- Breadcrumb: `mb-4` below breadcrumb line
- Page headings: `text-3xl font-bold`
- Wizard steps: no show image
- Post-production done pages: purple/green layout matching existing post-production view style

### Conventions
- `docker compose restart && php artisan test --stop-on-defect 2>&1 | tee test_output.txt`
- Namespaces: `MediaPlatform\` maps to `MEDIA_PLATFORM/`
- Routes: individually declared, auth middleware per route, no `Route::resource()`
- Migrations: explicitly registered in `AppServiceProvider::loadMigrationsFrom()`
- No soft deletes on planning records
- Ownership checks: redirect with error (not `abort_if`)
- Alpine.js inline save: `save()` returns JSON, `saveAndExit()` returns redirect

---

## Outstanding / Deferred Items

1. **`ready_to_upload_recording`** on `PodcastEpisodeStatus` — marked for removal once pipeline entry point changes to `ready_for_publishing`. Deferred.
2. **Post-Production pipeline entry point** — currently `ready_to_upload_recording`. Will change to `ready_for_publishing`. Deferred.
3. **RSS Pipeline Reorder** — ⬅ **NEXT FEATURE.** Full plan in `RSS_PIPELINE_REORDER_PLAN.md`.
4. **UI review** — Post-Production and Publishing views not yet reviewed for consistency with Planning UI conventions.
5. **Guest Interaction feature** — inline guest creation inside wizards. Out of scope for now.

---

## Test Commands

```bash
# Full suite
docker compose restart && php artisan test 2>&1 | tee test_output.txt

# Stop on first failure
docker compose restart && php artisan test --stop-on-defect 2>&1 | tee test_output.txt

# Failures only (no stack traces)
docker compose restart && php artisan test 2>&1 | grep -E "FAILED|Tests:" | cat | tee failures.txt

# Planning tests only
php artisan test tests/Feature/MEDIA_PLATFORM/Podcasts/Planning/

# Publishing tests only
php artisan test tests/Feature/MEDIA_PLATFORM/Podcasts/Publishing/
```