# Podcast App — Context Handoff

## What This Project Is

A Laravel/PHP podcasting application for producing and publishing 5 podcast shows.
The app handles the full episode lifecycle: planning/creative, audio production,
RSS feed generation, and website publishing.

## Current State

**Phases 1, 2, 3, post-Phase-3 UI pass, and Planning UI pass are complete and pushed.**

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
  - `show.blade.php` — restructured: Episode Details, Status (badge only), Notes, Theme, Script (collapsible), Guests, Links, Episode Management (actions + status quick-change + edit/delete), Podcasts Dashboard button. "New Episode" button removed.
  - `edit.blade.php` — sectioned: Core, Creative Content, Website Content
  - `delete_confirm.blade.php` — show image, hard-delete warning
  - `attach_guest.blade.php` — search added, Attach button
  - `attach_link.blade.php` — search added, Attach button
  - `edit_theme_field/edit.blade.php` — Alpine.js inline save, show image in header
  - `edit_script_field/edit.blade.php` — Alpine.js inline save, show image in header, larger textarea (30 rows)
  - `recording_view/show.blade.php` — show image in header
  - All wizard views restyled — `text-3xl` headings, `text-base` body, no show image in wizard steps (agreed)
  - `create_episode_wizard/step4.blade.php` — "Add guests" option added
  - `finalize_script_wizard/step3.blade.php` — amber instruction block, regex validation rejects titles starting with a digit
  - `finalize_script_wizard/step4.blade.php` — inline script editing via Alpine.js fetch (in progress — context window ended mid-rewrite; see Outstanding)
  - `prepare_for_publishing_wizard/step1.blade.php` — amber "point of no return" section added before the wizard steps list

**Test suite passing** (count TBC after latest changes — run `php artisan test` to confirm).

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

## Seeders

### `PodcastPlanningEpisodesSeeder`
- Located at `database/seeders/PodcastPlanningEpisodesSeeder.php`
- Seeds 30 planning episodes — 6 per active show, cycling through all 7 planning statuses
- Scheduled dates spread across days, weeks, and months into the future
- Seeds 6 `PodcastGuest` and 6 `PodcastLink` records via factory
- Attaches guests and links to episodes that are far enough along in the pipeline
- Resets PostgreSQL sequences for `podcast_guests` and `podcast_links` before factory calls (avoids `UniqueConstraintViolationException` when prior seeders insert with explicit IDs)
- Registered in `DatabaseSeeder` after `Podcast_linksSeeder`
- Run standalone: `php artisan db:seed --class=PodcastPlanningEpisodesSeeder`

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
  - Step 4 "what's next" page includes: Create another, Details, Work on theme, Work on script, **Add guests**, Podcasts Dashboard
- **Finalize Script Wizard** (7 steps) — session key: `wizard.finalize_script.episode_id`
  - Step 3: Confirm title — validates title does not start with a digit (`regex:/^\D/u`). Episode number prefix is added automatically on publishing. Clear amber instruction block explains the rule. Custom error message instructs the user to spell out numbers as words.
  - Step 4: AI Proofing — inline script editing via Alpine.js fetch (same endpoint as `EditScriptField`). Suggested prompts. Copy script button. In progress — see Outstanding.
  - Step 5/6 resolve `{{episode_number}}`, `{{title}}`, `{{sponsors}}` placeholders
  - `{{sponsors}}` = enabled, non-former `PhpServerlessProjectSponsor` records, one per line
  - Auto-skips Step 5/6 if the show has no intro/outro template
- **PrepareForPublishingWizard** (3 steps) — session key: `wizard.prepare_for_publishing.episode_id`
  - Step 1: amber "point of no return" section clearly states this is the transition from Planning to Post-Production, lists all assumptions (recording done, WAV ready, script final, etc.), and warns the planning record will be permanently deleted
  - Step 2: review/edit key inputs; shows derived value previews
  - Step 3: scary confirmation page; lists everything that will happen
  - Store: runs `DerivesPublishedEpisodeFields` trait, creates `podcast_episodes_published` record,
    migrates guests + links from planning pivots to published pivots, hard-deletes planning record,
    sets initial status to `ready_to_upload_recording`

### Attach Guest / Attach Link
- Both controllers accept a `search` query parameter
- Guest search: searches `full_name`
- Link search: searches `title` OR `link` URL
- Pagination uses `->withQueryString()` so the search term persists across pages
- Method signature includes `Request $request` as the first parameter

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

### Status Quick-Change (show page)
- The quick-change form on the episode show page sends hidden inputs for `title`, `episode_number`,
  and `scheduled_date` alongside `status` — required because `PodcastEpisodePlanningRequest`
  validates `title` as required. Without the hidden inputs, validation fails silently.

### CRUD
- index, show, edit, update, destroy — no create/store (wizard only)
- show page has: Episode Details, Status badge, Notes, Theme, Script (collapsible), Guests (attach/detach), Links (attach/detach), Episode Management (action buttons + status quick-change + edit/delete), Podcasts Dashboard button
- "New Episode" button removed from show page

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
- Show image (`$show->itunes_image`): displayed as `w-16 h-16 rounded object-cover border border-purple-200` in page headers alongside the `<h1>`, and in table cells (no show title text when image is present, fallback to title text if no image)
- Body text: `text-base` throughout — exception: buttons (`text-sm` or `text-xs`), help/sub text (`text-xs`)
- Stacked action buttons in table rows: `flex flex-col items-end gap-1.5`
- Date cells: `whitespace-nowrap` to prevent year wrapping
- Breadcrumb: `mb-4` below the breadcrumb line, before the `<h1>`
- Page headings: `text-3xl font-bold`
- Wizard steps: no show image (agreed — wizards are focused flows)
- Sortable column headers: `↕` (inactive, `text-purple-700`) / `↑` or `↓` (active, `text-purple-700`) with `text-base` size

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
3. **FinalizeScriptWizard Step 4 — inline script editing** — the rewrite was started but the context window ended before the artifact was complete. Step 4 needs to be rewritten with: an editable `<textarea x-model="script">`, Alpine.js `save()` fetch to `podcast_episodes_planning.script.save`, Copy Script button (copies from `x-model`), Save button with saving state, and the suggested prompts section below. The pattern is identical to `edit_script_field/edit.blade.php`.
4. **UI review** — Planning views complete. Remaining: Post-Production views, Publishing views.
5. **Guest Interaction feature** — attaching/detaching guests from planning episodes via the CRUD show page is built. The broader Guest Interaction feature (inline guest creation inside wizards) is out of scope for now.

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