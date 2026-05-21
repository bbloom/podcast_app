# Podcast App — Context Handoff

## What This Project Is

A Laravel/PHP podcasting application for producing and publishing 5 podcast shows.
The app handles the full episode lifecycle: planning/creative, audio production,
RSS feed generation, and website publishing.

## Current State

**Phases 1, 2, 3, and the post-Phase-3 UI pass are complete and pushed.**

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

**1379 tests passing.**

The GitHub `podcast_app` repository is connected to this project via the GitHub Connector
and is re-synced. All source code is accessible via project knowledge search.

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
    │   │   └── Controllers/ (Step1–7)
    │   ├── RecordingView/
    │   │   ├── Controllers/
    │   │   │   └── RecordingViewController.php
    │   │   └── Routes/
    │   │       └── recording_view.php
    │   └── PrepareForPublishingWizard/
    │       ├── Concerns/
    │       │   └── DerivesPublishedEpisodeFields.php  ← all population methods
    │       └── Controllers/ (Step1–3)
    ├── Publishing/
    │   ├── Controllers/
    │   ├── Enums/
    │   │   └── PodcastEpisodeStatus.php   ← moved here from Podcasts/Enums/
    │   ├── Models/
    │   │   └── PodcastEpisode.php         ← table: podcast_episodes_published
    │   ├── Requests/
    │   ├── Routes/
    │   └── PostProduction/
    │       ├── AuphonicProcessing/
    │       ├── CloudStorage/
    │       ├── Dashboard/
    │       ├── GenerateRssFeed/
    │       ├── PublishOnWebsite/
    │       ├── RegenerateRssFeed/
    │       ├── UploadProductionAudio/
    │       ├── UploadRecording/
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
- `podcast_episodes_published` — live published episodes. API serves from this table.
- `podcast_shows` — has `intro_template` and `outro_template` columns
- `podcast_links` — has `user_id` column
- `podcast_guests`
- `podcast_guest_episode_planning` — pivot: guests ↔ planning episodes
- `podcast_guest_episode` — pivot: guests ↔ published episodes
- `podcast_link_episode_planning` — pivot: links ↔ planning episodes
- `podcast_link_episode` — pivot: links ↔ published episodes

Note: `podcast_episode_drafts` has been dropped (migration run).

---

## Planning Module — What Was Built in Phase 3

### `PodcastEpisodePlanningStatus` enum cases
```
new_episode_created          → set by Create Episode Wizard
working_on_theme             → set manually
writing_script               → set manually
ready_to_finalize_the_script → set manually (entry point: Finalize Script Wizard)
ready_to_record              → set by Finalize Script Wizard
raw_audio_needs_editing      → set manually
ready_for_publishing         → set manually (entry point: PrepareForPublishing Wizard)
```

Pipeline order is codified in `sortOrder(): int` on the enum — used for dashboard sorting.

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

Note: `created` case has been removed. `postProductionShowRoute(): string` on the enum maps
each status to its episode-specific pipeline route — used by the dashboard Continue/Monitor buttons.

### Wizards
- **Create Episode Wizard** (4 steps) — session key: `wizard.create_episode_planning.podcast_show_id`
- **Finalize Script Wizard** (7 steps) — session key: `wizard.finalize_script.episode_id`
  - Step 5/6 resolve `{{episode_number}}`, `{{title}}`, `{{sponsors}}` placeholders
  - `{{sponsors}}` = enabled, non-former `PhpServerlessProjectSponsor` records, one per line
  - Auto-skips Step 5/6 if the show has no intro/outro template
- **PrepareForPublishingWizard** (3 steps) — session key: `wizard.prepare_for_publishing.episode_id`
  - Step 2: review/edit key inputs; shows derived value previews
  - Step 3: scary confirmation page; lists everything that will happen
  - Store: runs `DerivesPublishedEpisodeFields` trait, creates `podcast_episodes_published` record,
    migrates guests + links from planning pivots to published pivots, hard-deletes planning record,
    sets initial status to `ready_to_upload_recording`

### RecordingView
- Path: `MEDIA_PLATFORM/Podcasts/Planning/RecordingView/`
- Route: `podcast_episodes_planning.recording.show`
- Status gate: `ready_to_record` only
- Displays: full assembled script (Markdown rendered), guest profiles with images and website links,
  episode links (all opening in new tab with external link icon)
- Entry points: dashboard planning table, episode show page, planning index

### Field Editors
- **EditThemeField** — `save()` returns JSON (Alpine.js fetch, stays on page), `saveAndExit()` redirects
- **EditScriptField** — same pattern as EditThemeField

### CRUD
- index, show, edit, update, destroy — no create/store (wizard only)
- show page has: status quick-change, action buttons (Edit Theme, Edit Script, Finalize Script wizard entry,
  View for Recording (when ready_to_record), Prepare for Publishing wizard entry), guests with attach/detach,
  links with attach/detach

### Route Names (key ones)
```
podcast_episodes_planning.index
podcast_episodes_planning.show
podcast_episodes_planning.edit
podcast_episodes_planning.update
podcast_episodes_planning.delete.confirm
podcast_episodes_planning.destroy
podcast_episodes_planning.wizard.create.step1–4
podcast_episodes_planning.wizard.finalize.step1–7
podcast_episodes_planning.wizard.publish.step1–3
podcast_episodes_planning.theme.show / .save / .save_exit
podcast_episodes_planning.script.show / .save / .save_exit
podcast_episodes_planning.guests.attach.index / .attach / .detach
podcast_episodes_planning.links.attach.index / .attach / .detach
podcast_episodes_planning.recording.show
```

---

## Blade Component Paths

Step dot partials live in `views/components/` (Laravel component resolution):
- `views/components/podcasts/planning/create_episode_wizard/_step_dots.blade.php`
- `views/components/podcasts/planning/finalize_script_wizard/_step_dots.blade.php`
- `views/components/podcasts/planning/prepare_for_publishing_wizard/_step_dots.blade.php`

---

## Important Context

### Two-world model
- **Planning** (`podcast_episodes_planning`) — creative/assembly workspace, freely mutable, hard-deleted on publishing
- **Published** (`podcast_episodes_published`) — permanent, touched as little as possible, API serves from here
- The hard handoff is the PrepareForPublishingWizard Step 3 store method

### DerivesPublishedEpisodeFields trait
Lives at `MEDIA_PLATFORM/Podcasts/Planning/PrepareForPublishingWizard/Concerns/DerivesPublishedEpisodeFields.php`.
All population methods read from `PodcastEpisodePlanning` model. Public methods per conventions (testable directly).
Used by both Step2Controller (previews) and Step3Controller (actual creation).
`get_status()` returns `PodcastEpisodeStatus::ready_to_upload_recording` — the pipeline entry point.

### Podcasts Dashboard
- Planning section: episodes grouped by show (ACTIVE_SHOWS order), sorted within each show by `sortOrder()`
- Post-production section: excludes `published` and `not_published`; each row has a Continue button
  (or Monitor for `processing_at_auphonic`) linking directly to the episode's pipeline page via `postProductionShowRoute()`
- Recently Published: last 5 published episodes

### Digest vs Podcasts naming disambiguation
- `MEDIA_PLATFORM/Digest/` has its own podcast content source feature (RSS feed ingestion for digest processing)
- Its routes are prefixed `/digests/podcasts/` and named `digest-podcasts.*`
- Completely separate from `MEDIA_PLATFORM/Podcasts/` (episode production)
- The nav has two separate dropdowns: "Digest" and "Podcasts"

### Five active shows
```
'The Bob Bloom Show'
'The Bob Bloom Interviews'
'PHP Serverless News'
'PHP Serverless Profiles'
'PHP Serverless Project Updates'
```
Used in `private const ACTIVE_SHOWS` in wizard Step2 controllers and the dashboard controller.

### UI conventions established
- Button labels: "Details" for show/read views — never "Open" or "View"
- Table rows: `bg-gray-50` resting state, `hover:bg-white`
- External links: inline SVG arrow-up-right icon, `target="_blank" rel="noopener noreferrer"`

### Conventions
- Step by step. One thing at a time. Run `php artisan test` after every change.
- `docker compose restart` before running tests after PHP file changes (opcache)
- Namespaces: `MediaPlatform\` maps to `MEDIA_PLATFORM/`
- Factories: `Database\Factories\Media_platform\...`
- Views: `media_platform.podcasts...` dot notation
- Routes: individually declared, no `Route::resource()`, auth middleware per route
- All migrations registered explicitly in `AppServiceProvider::loadMigrationsFrom()`
- Migrations for podcasts: `database/migrations/media_platform/podcasts/`
- No soft deletes on planning records
- Enums live at `MEDIA_PLATFORM/Podcasts/Planning/CRUD/Enums/` (planning) and `MEDIA_PLATFORM/Podcasts/Publishing/Enums/` (published)
- Ownership checks: redirect with error message (not `abort_if`) — see `PodcastLinkController::authorizeOwnership()` pattern
- Alpine.js inline save: `save()` returns JSON, `saveAndExit()` returns redirect — both testable
- `docker compose restart && php artisan test --stop-on-defect 2>&1 | tee test_output.txt`

---

## Outstanding / Deferred Items

1. **`ready_to_upload_recording`** on `PodcastEpisodeStatus` — marked for removal once the Publishing wizard refactor is complete and entry point changes to `ready_for_publishing`.
2. **Post-Production pipeline entry point** — currently `ready_to_upload_recording`. Will eventually change to `ready_for_publishing` (set by PrepareForPublishingWizard). This refactor is deferred.
3. **UI review in progress** — screen-by-screen review started. Main podcast dashboard complete. Remaining screens: Planning index, episode show page, edit page, field editors, wizard steps, recording view, attach guest/link pages, Post-Production views, Publishing views.
4. **Guest Interaction feature** — attaching/detaching guests from planning episodes via the CRUD show page is built. The broader Guest Interaction feature (inline guest creation inside wizards) is out of scope for now.

---

## Test Commands

```bash
# Full suite
docker compose restart && php artisan test 2>&1 | tee test_output.txt

# Stop on first failure or error
docker compose restart && php artisan test --stop-on-defect 2>&1 | tee test_output.txt

# Planning tests only
php artisan test tests/Feature/MEDIA_PLATFORM/Podcasts/Planning/

# Publishing tests only
php artisan test tests/Feature/MEDIA_PLATFORM/Podcasts/Publishing/

# Single test class
php artisan test tests/Feature/MEDIA_PLATFORM/Podcasts/Planning/RecordingView/RecordingViewControllerTest.php
```