# Podcast Version 2 — Planning Document

> Lives at: `MEDIA_PLATFORM/Podcasts/PODCAST_V2_PLAN.md`

---

## Guiding Principles

- The Podcast Episode is the thing being produced.
- There are two distinct worlds: **Planning**, and **Published**.
- Planning encompasses the creative process, planning, and assembly. And, is entirely internal.
- Publishing refers to the public facing podcast episodes, including the audio file and the RSS XML file.
- One database table per world.
- There is a "hard handoff" from the Planning to the Publishing database tables.
- There are no soft deletes on planning records — physically deleted upon publishing.
- This application is to help me produce high quality podcast episodes, and to keep to a regular production schedule.

---

## Folder Structure

### Version 1 — Current (`MEDIA_PLATFORM/PodcastStudio/`)

```
MEDIA_PLATFORM/
└── PodcastStudio/
    ├── Dashboard/
    ├── PodcastEpisodeDrafts/
    │   ├── Controllers/
    │   ├── CreateDraft/
    │   │   └── Controllers/
    │   ├── PreProduction/
    │   │   ├── Controllers/
    │   │   └── Routes/
    │   ├── Enums/
    │   ├── Models/
    │   ├── Requests/
    │   └── Routes/
    ├── CreateProductionEpisode/    ← planned, never built
    ├── Management/
    ├── PreProduction/              ← legacy
    └── PostProduction/
        ├── AuphonicProcessing/
        ├── CloudStorage/
        └── PublishOnWebsite/
```

### Version 2 — New (`MEDIA_PLATFORM/Podcasts/`)

```
MEDIA_PLATFORM/
└── Podcasts/
    ├── Planning/
    │   ├── CreateEpisodeWizard/
    │   ├── FinalizeScriptWizard/
    │   ├── PrepareForPublishingWizard/
    │   ├── EditThemeField/
    │   ├── EditScriptField/
    │   ├── CRUD/
    │   │   └── Controllers/
    │   └── Common/
    │       ├── Models/
    │       ├── Enums/
    │       ├── Requests/
    │       └── Routes/
    ├── Publishing/
    │   └── (Post Production wizard — refactored from PodcastStudio/PostProduction/)
    ├── Shows/
    │   ├── Controllers/
    │   ├── Models/
    │   ├── Requests/
    │   └── Routes/
    ├── Guests/
    ├── Links/
    └── Dashboard/
```

---

## Database Changes

### New table: `podcast_episodes_planning`

Replaces `podcast_episode_drafts`. The home for every episode during its
creative, planning, and assembly life. Records are hard deleted (no soft
deletes) when an episode is published and the corresponding
`podcast_episodes_published` record is created.

Column changes from `podcast_episode_drafts` (all other columns unchanged):
- `draft` → renamed to `script`
- `date` → renamed to `scheduled_date`
- `theme` → new field
- `guest_notes` → removed; replaced by FK relationship to `podcast_guests` table

### Existing table: `podcast_episodes`

- Rename to `podcast_episodes_published` *(confirm timing relative to API impact — see Pending Decisions)*
- Add status: `not_published` — for episodes recorded but intentionally not published
- `ready_to_upload_recording` status: retained during refactor for backward
  compatibility with the existing Post Production wizard entry point.
  To be removed once the Publishing wizard refactor is complete and the
  entry point changes to `ready_for_publishing`.
- Otherwise untouched. The API serves from this table.

### `podcast_links` table

- Add `user_id` field
- Update CRUD controllers and tests accordingly

---

## Status Enums

### `PodcastEpisodePlanningStatus` (new)

For the `podcast_episodes_planning` table. Statuses can move backwards in
the Planning phase — the app does not enforce forward-only progression.
Data is never cleared on a backwards status move.

```php
// Set automatically by the Create Episode Wizard
case new_episode_created             = 'new-episode-created';

// Set manually
case working_on_theme                = 'working-on-theme';

// Set manually
case writing_script                  = 'writing-script';

// Set manually — deliberate entry point for the Finalize Script Wizard
// Signals: writing is done, ready for final polish, intro/outro, proofread
case ready_to_finalize_the_script    = 'ready-to-finalize-the-script';

// Set automatically by the Finalize Script Wizard
// Script is locked. Ready to record.
case ready_to_record                 = 'ready-to-record';

// Set manually — raw audio needs trimming/editing before Auphonic
case raw_audio_needs_editing         = 'raw-audio-needs-editing';

// Set manually — the handoff point; entry into the Publishing wizard
case ready_for_publishing            = 'ready-for-publishing';
```

### `PodcastEpisodeStatus` (existing — changes only)

- Add: `not_published` — episode recorded but intentionally not published
- `ready_to_upload_recording`: retained during refactor; to be removed once
  the Publishing wizard refactor is complete

---

## Wizards — Planning Phase

### Create New Episode Wizard
`MEDIA_PLATFORM/Podcasts/Planning/CreateEpisodeWizard/`

- **Step 1** — Introduction page
- **Step 2** — Select a podcast show
- **Step 3** *(Bob Bloom Interviews Show only)* — Do you have a guest or guests in mind?
- **Step 4** *(Interviews show only, if yes)* — Select existing guests from dropdown,
  and/or create new guests inline (cycle through Create Guest for each new guest)
  → guests auto-linked to planning record
- **Step 5** *(all shows)* — Title, episode number, scheduled date, theme
- **Step 6** — Confirmation screen: episode created. What do you want to do next?
  - Create another new episode
  - View this episode
  - Work on notes / theme now
  - Work on the script now

Sets status: `new_episode_created`

Notes:
- Guest branching is hardcoded to the Interviews show in this wizard
- Any show *can* have guests attached; only the Interviews show prompts for them here
- Steps 3 and 4 are skipped for all other shows

---

### Finalize Script Wizard
`MEDIA_PLATFORM/Podcasts/Planning/FinalizeScriptWizard/`

- Entry point status: `ready_to_finalize_the_script` (set manually — deliberate act)
- Sets status on completion: `ready_to_record`

**Steps:**

- **Step 1** — Introduction page. Explains the purpose of this wizard: finalize
  the script, proof it, prepend the intro, append the outro, and lock it for recording.

- **Step 2** — Confirm the episode number.

- **Step 3** — Confirm the title, displayed in the format:
  `#{{episode_number}} - {{title}}` — derived at render time.
  `episode_number` and `title` are stored separately; the formatted
  display is always derived, never stored.

- **Step 4** — AI Proofing.
  Displays the script in a read-only view. Includes:
  - **"Copy Script"** button — copies full script text to clipboard in one click
  - **Ad Hoc Prompt** link → `/adhocprompt` (internal)
  - **Gemini** link → `https://gemini.google.com` (opens in new tab)
  - **ChatGPT** link → `https://chatgpt.com` (opens in new tab)

  Suggested prompts displayed on the page for copy-paste use:

  > **Spelling & Grammar**
  > "Please check the following podcast script for spelling and grammar errors.
  > List each error with the suggested correction."

  > **Conversational Flow**
  > "Please review the following podcast script for conversational flow. This
  > script will be read aloud. Identify any sentences or passages that sound
  > unnatural when spoken, are too formal, or would be awkward to say aloud.
  > Suggest more natural alternatives."

  > **Sentence Length & Spoken Readability**
  > "Please review the following podcast script for sentences that are too long
  > or complex to read aloud comfortably. Suggest shorter, more natural
  > alternatives where needed."

  > **Full Polish**
  > "Please review the following podcast script for overall quality. This script
  > will be read aloud. Check for: spelling and grammar errors, conversational
  > flow and natural spoken language, sentence length and readability when read
  > aloud, clarity of ideas, and natural pacing. Provide specific, actionable
  > suggestions for improvement."

- **Step 5** — Prepend the intro.
  Resolves the show's `intro_template` with `{{episode_number}}`, `{{title}}`,
  and `{{sponsors}}` (active sponsors from `phpserverlessproject_sponsors`).
  Displays the resolved intro for review and editing.
  Option to skip if intro was already added manually.

- **Step 6** — Append the outro.
  Resolves the show's `outro_template`.
  Displays the resolved outro for review and editing.
  Option to skip if outro was already added manually.

- **Step 7** — Final proof and confirmation.
  Displays the complete assembled script (intro + body + outro) in full.
  User confirms. Status set to `ready_to_record`.

---

### Prepare for Publishing Wizard
`MEDIA_PLATFORM/Podcasts/Planning/PrepareForPublishingWizard/`

- Entry point status: TBD
- Sets status on completion: `ready_for_publishing`
- This wizard is the handoff point: reads from `podcast_episodes_planning`,
  populates `podcast_episodes_published`, hard deletes the planning record.

**Steps:**

- **Step 1** — Introduction page. Explains the purpose of this wizard:
  prepare the episode for publishing, populate all required publishing fields,
  and hand the episode off to the Publishing phase. Includes a confirmation
  checklist before proceeding:
  - ☐ WAV file is ready and accessible
  - ☐ Website fields are complete

- **Step 2 onwards** — TBD

---

## Focused Field Editors

### EditThemeField
`MEDIA_PLATFORM/Podcasts/Planning/EditThemeField/`

- Edits the `theme` field on a planning episode
- Inline save via Alpine.js if feasible; otherwise standard controller/view sequence

### EditScriptField
`MEDIA_PLATFORM/Podcasts/Planning/EditScriptField/`

- Edits the `script` field on a planning episode
- Same inline-save approach as EditThemeField

---

## CRUD — `podcast_episodes_planning`
`MEDIA_PLATFORM/Podcasts/Planning/CRUD/Controllers/`

Standard resource actions: `index`, `show`, `edit`, `update`, `destroy`.
No `create` / `store` — episode creation is handled exclusively by the
Create Episode Wizard. Used for editing between wizards.

---

## Phases

### Phase 1 — Structural Reshuffle

Move files to the new folder structure. Update namespaces, route names, view
paths, service providers. **No logic changes.** Tests must be green at the end.

**Execution order** (smallest dependencies first):

#### Step 1 — Dashboard
- Move `PodcastStudio/Dashboard/` → `Podcasts/Dashboard/`
- Update namespace: `MediaPlatform\PodcastStudio\Dashboard\` → `MediaPlatform\Podcasts\Dashboard\`
- Update view paths: `media_platform.podcast_studio.dashboard.*` → `media_platform.podcasts.dashboard.*`
- Update route file `require` in `routes/web.php`
- Update tests

#### Step 2 — Guests
- Move `PodcastStudio/Management/Controllers/PodcastGuestController.php` → `Podcasts/Guests/Controllers/`
- Move `PodcastStudio/Management/Models/PodcastGuest.php` → `Podcasts/Guests/Models/`
- Move `PodcastStudio/Management/Requests/PodcastGuestRequest.php` → `Podcasts/Guests/Requests/`
- Move `PodcastStudio/Management/Routes/podcast_guests.php` → `Podcasts/Guests/Routes/`
- Update namespace: `MediaPlatform\PodcastStudio\Management\` → `MediaPlatform\Podcasts\Guests\`
- Update view paths
- Update morph map in `AppServiceProvider` if applicable
- Update route file `require` in `routes/web.php`
- Update tests

#### Step 3 — Links
- Move `PodcastStudio/Management/Controllers/PodcastLinkController.php` → `Podcasts/Links/Controllers/`
- Move `PodcastStudio/Management/Models/PodcastLink.php` → `Podcasts/Links/Models/`
- Move `PodcastStudio/Management/Requests/PodcastLinkRequest.php` → `Podcasts/Links/Requests/`
- Move `PodcastStudio/Management/Routes/podcast_links.php` → `Podcasts/Links/Routes/`
- Update namespace: `MediaPlatform\PodcastStudio\Management\` → `MediaPlatform\Podcasts\Links\`
- Update view paths
- Update route file `require` in `routes/web.php`
- Update tests

#### Step 4 — Shows
- Move `PodcastStudio/Management/Controllers/PodcastShowController.php` → `Podcasts/Shows/Controllers/`
- Move `PodcastStudio/Management/Models/PodcastShow.php` → `Podcasts/Shows/Models/`
- Move `PodcastStudio/Management/Requests/PodcastShowRequest.php` → `Podcasts/Shows/Requests/`
- Move `PodcastStudio/Management/Routes/podcast_shows.php` → `Podcasts/Shows/Routes/`
- Update namespace: `MediaPlatform\PodcastStudio\Management\` → `MediaPlatform\Podcasts\Shows\`
- Update view paths
- Update morph map in `AppServiceProvider`: `podcast_show` alias → new `PodcastShow` class path
- Update route file `require` in `routes/web.php`
- Update tests

#### Step 5 — Episodes
- Move `PodcastStudio/Management/Controllers/PodcastEpisodeController.php` → `Podcasts/Episodes/Controllers/`
- Move `PodcastStudio/Management/Controllers/PodcastEpisodeUpdateController.php` → `Podcasts/Episodes/Controllers/`
- Move `PodcastStudio/Management/Models/PodcastEpisode.php` → `Podcasts/Episodes/Models/`
- Move `PodcastStudio/Management/Enums/PodcastEpisodeStatus.php` → `Podcasts/Episodes/Enums/`
- Move `PodcastStudio/Management/Requests/PodcastEpisodeRequest.php` → `Podcasts/Episodes/Requests/`
- Move `PodcastStudio/Management/Routes/podcast_episodes.php` → `Podcasts/Episodes/Routes/`
- Update namespace: `MediaPlatform\PodcastStudio\Management\` → `MediaPlatform\Podcasts\Episodes\`
- Update all cross-references in `Publishing/` services (e.g. `RssFeedGeneratorService`)
- Update view paths
- Update API route files
- Update route file `require` in `routes/web.php`
- Update tests

#### Step 6 — Publishing (PostProduction)
- Move `PodcastStudio/PostProduction/` → `Podcasts/Publishing/`
- Update namespace: `MediaPlatform\PodcastStudio\PostProduction\` → `MediaPlatform\Podcasts\Publishing\`
- Update view paths: `media_platform.podcast_studio.post_production.*` → `media_platform.podcasts.publishing.*`
- Update Auphonic webhook CSRF exclusion path in `bootstrap/app.php` if class-based; URL path is unchanged
- Update route file `require` in `routes/web.php`
- Update tests

#### Step 7 — Retire old folders
- Delete `PodcastStudio/PodcastEpisodeDrafts/` — no live data
- Delete `PodcastStudio/PreProduction/` — legacy, retired
- Delete `PodcastStudio/CreateProductionEpisode/` — planned but never built, discard
- Delete now-empty `PodcastStudio/Management/`
- Delete now-empty `PodcastStudio/`

#### Step 8 — AppServiceProvider
- Update all `loadMigrationsFrom()` paths for podcast migrations
- Update morph map: ensure `podcast_show` alias points to new `PodcastShow` class path
- Verify all podcast-related bindings and registrations

#### Step 9 — Final verification
- Run full test suite: `php artisan test`
- Smoke-test all podcast routes in browser
- Verify RSS feed generation still works
- Verify Auphonic webhook still reachable

### Phase 2 — Small Standalone Additions
Low-risk, isolated changes that do not require Version 2 architecture:
- Add `user_id` to `podcast_links` table; update CRUD and tests
- Add `intro_template` and `outro_template` fields to `podcast_shows`
- Add `not_published` status to `PodcastEpisodeStatus`
- Other small additions TBD

### Phase 3 — Version 2
New `podcast_episodes_planning` table, new status enum, all Planning wizards,
focused field editors, CRUD, guest interaction feature, and Publishing refactor.

---

## Migration Notes (Phase 1 → Phase 3)

- `podcast_episode_drafts`: no live data. Table can be dropped in Phase 3.
- `podcast_episodes`: all records have status `published` except one
  (`not_published`). No data migration risk.
- Existing Post Production wizard: functional, retained under `Publishing/`.
  Known friction — to be addressed during Phase 3 Publishing refactor.
- `ready_to_upload_recording` status: retained during refactor for backward
  compatibility. Marked for removal once Publishing wizard refactor is complete.

---

## Pending Decisions / Open Questions

- **`podcast_episodes_published` rename**: confirm timing relative to API impact.
- **Batch publishing**: not doing this feature.
- **Post Production wizard friction**: known issues, to be addressed during
  Publishing refactor.
- **Recording view**: a dedicated read view for episodes at `ready_to_record` status.
  Displays: full script, guest name(s), guest profile(s) (Interviews show).
  To be designed and built as part of Phase 3.
- **`ready_for_publishing` meaning**: this status is the user's conscious
  declaration that BOTH the WAV file is ready AND all website content is done.
  The app does not track the external audio editing stages (two manual stages
  producing the final WAV). The Publishing wizard Step 1 checklist reinforces this.
- **`raw_audio_needs_editing`**: optional manual status set after recording,
  signals that the audio editing process is in progress externally.
- **FinalizeScriptWizard steps**: TBD — next item to define.
- **PrepareForPublishingWizard steps**: TBD.
- **Guest interaction feature**: new feature, to be scoped separately.
- **`podcast_links` table**: add `user_id` field; update CRUD and tests.