# Conventions

## Stack

- PHP 8.5, Laravel 13, PostgreSQL, Docker
- FrankenPHP (Caddy Server)
- VS Code with devcontainer
- Alpine.js for reactive UI
- Gemini AI — model: `gemini-2.5-flash`, accessed via a custom wrapper in `Gemini/`

## Folder Structure

### Project root

- `MEDIA_PLATFORM/` — all domain code (features, tools, configuration)
- `views/` — all Blade files
- `tests/` — all tests
- `database/` — migrations and factories
- `app/` — Laravel plumbing only (base controller, User model, AppServiceProvider, etc.)
- `Gemini/` — custom wrapper around the `gemini-php/laravel` client package
- `config/`, `routes/`, `bootstrap/`, `storage/`, `vendor/` — standard Laravel

### MEDIA_PLATFORM structure

```
MEDIA_PLATFORM/
├── API/
│   └── v1/                ← active (public API — podcast + digest endpoints)
├── Tools/
│   ├── AdHocPrompt/
│   ├── DatabaseBackup/
│   └── HealthChecks/
├── Configuration/
│   ├── LanguageModels/
│   ├── Providers/
│   └── UseCases/
├── Digest/
│   ├── ContentSources/
│   │   ├── Youtube/
│   │   ├── Podcasts/
│   │   ├── TextBasedRssFeeds/
│   │   ├── Lists/
│   │   ├── OutputDestinations/
│   │   └── Traits/
│   ├── Enums/
│   │   └── OutputType.php
│   ├── Processing/
│   ├── Publishing/
│   │   ├── Contracts/
│   │   │   └── DigestDeliveryStrategy.php
│   │   ├── Mail/
│   │   ├── Models/
│   │   │   └── PublishedDigest.php
│   │   ├── Notifications/
│   │   ├── Services/
│   │   │   └── DeliveryStrategyResolver.php
│   │   └── Strategies/
│   │       ├── EmailDeliveryStrategy.php
│   │       ├── WebpageDeliveryStrategy.php
│   │       └── StaticSiteDeliveryStrategy.php
│   ├── Services/
│   └── README_STATIC_SITE.md
├── PodcastStudio/
│   ├── Dashboard/
│   │   ├── Controllers/
│   │   │   └── PodcastStudioDashboardController.php
│   │   └── Routes/
│   │       └── podcast_studio_dashboard.php
│   ├── PodcastEpisodeDrafts/
│   │   ├── Controllers/
│   │   │   └── PodcastEpisodeDraftController.php
│   │   ├── CreateDraft/
│   │   │   └── Controllers/
│   │   │       ├── Step1Controller.php
│   │   │       └── Step2Controller.php
│   │   ├── PreProduction/
│   │   │   ├── Controllers/
│   │   │   │   ├── Step1Controller.php
│   │   │   │   ├── Step2Controller.php
│   │   │   │   ├── Step3Controller.php
│   │   │   │   └── Step4Controller.php
│   │   │   └── Routes/
│   │   │       └── pre_production.php
│   │   ├── Enums/
│   │   │   └── PodcastEpisodeDraftStatus.php
│   │   ├── Models/
│   │   │   └── PodcastEpisodeDraft.php
│   │   ├── Requests/
│   │   │   └── PodcastEpisodeDraftRequest.php
│   │   └── Routes/
│   │       └── podcast_episode_drafts.php
│   ├── CreateProductionEpisode/  ← planned (Wizard 2 — draft → production episode)
│   │   ├── Controllers/
│   │   └── Routes/
│   ├── Management/        ← active (Controllers, Models, Requests, Routes)
│   ├── PreProduction/     ← legacy (CreateEpisode wizard — to be retired once CreateProductionEpisode is complete)
│   └── PostProduction/    ← active
│       ├── AuphonicProcessing/
│       │   └── Presets/   ← Auphonic_preset.php — per-show preset UUIDs
│       ├── CloudStorage/  ← S3 and R2 bucket/endpoint resolution classes
│       │   ├── R2_production_audio.php
│       │   ├── R2_rss.php
│       │   ├── S3_production_audio.php
│       │   ├── S3_rss.php
│       │   └── S3_work_in_progress_audio.php
│       └── PublishOnWebsite/  ← includes TriggerBuildsController
├── StaticSiteDeployHooks/ ← shared deploy hook infrastructure
│   ├── Controllers/
│   │   └── DeployHookController.php
│   ├── Enums/
│   │   └── DeployHookProvider.php
│   ├── Models/
│   │   └── DeployHook.php
│   ├── Requests/
│   │   └── DeployHookRequest.php
│   ├── Routes/
│   │   └── deploy_hooks.php
│   └── Services/
│       ├── DeployHookTriggerService.php
│       └── DeployHookTriggerResult.php
├── PsnContentManager/     ← future development
└── (no top-level Enums/ folder — enums are co-located within their feature)
```

### Namespaces

- `MediaPlatform\` maps to `MEDIA_PLATFORM/`
- Example: `MediaPlatform\Digest\ContentSources\Youtube\Controllers\YoutubeChannelWizardController`
- Example: `MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Models\PodcastEpisodeDraft`
- Example: `MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\PreProduction\Controllers\Step1Controller`
- Database factories: `Database\Factories\Media_platform\...` maps to `database/factories/Media_platform/...`

### Views

- Root: `views/media_platform/`
- Dot-notation prefix: `media_platform.`
- Example: `view('media_platform.digest.content_sources.podcasts.index')`
- Example: `view('media_platform.podcast_studio.dashboard.dashboard')`
- Example: `view('media_platform.podcast_studio.podcast_episode_drafts.show')`
- Example: `view('media_platform.podcast_studio.podcast_episode_drafts.pre_production.wizard_step1')`
- Shared components: `views/components/`
- Digest items partial: `media_platform.digest._items`
- Static site deploy hooks views: `views/media_platform/static_site_deploy_hooks/`
- Note: the views folder hierarchy does not fully mirror `MEDIA_PLATFORM/` — intermediate subfolders are omitted where they add no value

### Migrations

- All paths registered explicitly in `AppServiceProvider` — Laravel does not scan subfolders
- `database/migrations/media_platform/configuration/language_models/`
- `database/migrations/media_platform/digests/processing/`
- `database/migrations/media_platform/digests/lists_and_feeds/`
- `database/migrations/media_platform/tools/database_backup/`
- `database/migrations/media_platform/api/`
- `database/migrations/media_platform/podcast_studio/management/`
- `database/migrations/media_platform/podcast_studio/podcast_episode_drafts/`
- `database/migrations/media_platform/static_site_deploy_hooks/`
- Note: the migrations folder hierarchy does not fully mirror `MEDIA_PLATFORM/`

### Routes

- `routes/web.php` and `routes/console.php` are thin orchestrators that `require` feature route files
- Feature route files live inside their feature folder under a `Routes/` subfolder
- Example: `MEDIA_PLATFORM/Configuration/Routes/language_models.php`
- Example: `MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/Routes/podcast_episode_drafts.php`
- Example: `MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/Routes/pre_production.php`
- Example: `MEDIA_PLATFORM/PodcastStudio/Dashboard/Routes/podcast_studio_dashboard.php`
- API routes are loaded via `routes/api.php`, which Laravel automatically prefixes with `/api`

## Naming

- "Youtube" not "YouTube" in code
- `ListModel` instead of `List` (reserved PHP word)
- Morph aliases: `youtube_channel`, `text_based_rss_feed`, `podcast`, `podcast_show`, `digest_list`

## Models & Relationships

- All models use explicit `$table` names
- Polymorphic morph aliases registered in `AppServiceProvider` using `Relation::enforceMorphMap()`
- Ownership checks: `abort_if($model->user_id !== auth()->id(), 403)`
- Sensitive fields use Laravel's `encrypted` cast
- `DeployHook` uses `encrypted` cast on the `url` column
- Define named Eloquent scopes on models to avoid duplicating query logic across controllers and services. See `PodcastEpisode` for examples: `scopeForUser()`, `scopeWithStatus()`, `scopeOrderByScheduledDate()`, `scopeEligibleForRssFeed()`, `scopeEligibleForPublishOnWebsite()`
- `PodcastShow` has `drafts()` (HasMany → PodcastEpisodeDraft) and `episodes()` (HasMany → PodcastEpisode) relationships

## Slugs

- Never use `Str::slug()`
- Always use the custom `makeSlug()` helper (preserves dots)

## Enums

- Enums are co-located within their feature folder under an `Enums/` subfolder
- The namespace mirrors the folder path exactly
- Examples:
  - `MEDIA_PLATFORM/Digest/Enums/OutputType.php` — `MediaPlatform\Digest\Enums\OutputType`
  - `MEDIA_PLATFORM/PodcastStudio/PostProduction/Enums/Bucket.php` — `MediaPlatform\PodcastStudio\PostProduction\Enums\Bucket`
  - `MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/Enums/PodcastEpisodeDraftStatus.php` — `MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Enums\PodcastEpisodeDraftStatus`
  - `MEDIA_PLATFORM/StaticSiteDeployHooks/Enums/DeployHookProvider.php` — `MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider`
- There is no global top-level `Enums/` folder
- `OutputType` enum: `Webpage`, `Email`, `StaticSite` — controls digest delivery mechanism
- `PodcastEpisodeDraftStatus` enum: `working_on_draft`, `ready_to_create_production_episode` — tracks draft lifecycle
- `PodcastEpisodeStatus` enum: tracks production pipeline from `created` through `published` — does NOT include draft-phase statuses (those belong to `PodcastEpisodeDraftStatus`)
- The `lists.output_type` column is a plain `string`, not a MySQL `enum` — the PHP `OutputType` enum is the sole authority on valid values
- Adding a new output type requires only: a new enum case, a new strategy class, and registration in `DeliveryStrategyResolver`

## Seeding

- Seeding of admin/sensitive data is gated behind `ADMIN_SEEDING_ENABLED` in `.env`
- Checked via `config/admin.php` — the gate lives in `DatabaseSeeder.php`, not in individual seeders
- Individual seeders do not need their own gate check
- `DeployHooksSeeder` seeds fake deploy hooks for all six podcast shows and all static site digest lists — local/testing only
- `PublishedDigestsSeeder` seeds 5 published digest records per static site list — local/testing only
- `ListModelsSeeder` seeds digest lists including at least one static site list — local/testing only

## Digest Delivery Strategies

- `DigestDeliveryStrategy` interface at `MEDIA_PLATFORM/Digest/Publishing/Contracts/`
- Three implementations at `MEDIA_PLATFORM/Digest/Publishing/Strategies/`:
  - `EmailDeliveryStrategy` — email delivery
  - `WebpageDeliveryStrategy` — SFTP upload
  - `StaticSiteDeliveryStrategy` — JSON persistence + deploy hooks
- `DeliveryStrategyResolver` at `MEDIA_PLATFORM/Digest/Publishing/Services/` — resolves strategy by `OutputType`
- `PublishDigest` job uses `DeliveryStrategyResolver` — no delivery logic in the job itself
- Adding a new output type: add a case to `OutputType` enum, create a strategy class, register in `DeliveryStrategyResolver::resolve()`
### Digest Retention
- `DigestRetentionService` at `MEDIA_PLATFORM/Digest/Publishing/Services/` — prunes old digest data
- Called by `PublishDigest` after `markAsIncluded()` for all output types
- Static site lists: prunes `published_digests` (oldest records beyond `retention_count`)
- Email/SFTP lists: prunes `summaries` where `included_in_digest = true` (oldest digest runs beyond `retention_count`)
- The `retention_count` field on `lists` is editable for all output types via the edit form
- Safety guarantees: never prunes pending summaries, irrelevant summaries, or `content_already_processed` bookmarks

## Static Site Deploy Hooks

- Shared infrastructure at `MEDIA_PLATFORM/StaticSiteDeployHooks/`
- Polymorphic — `triggerable_type` / `triggerable_id` — supports `podcast_show` and `digest_list`
- `DeployHookTriggerService::trigger(DeployHook $hook)` — fires one hook, records outcome, returns `DeployHookTriggerResult`
- `DeployHookTriggerResult` — immutable value object: `succeeded()`, `httpStatus()`, `buildId()`, `alreadyExists()`, `errorMessage()`
- `DeployHook` model provides `triggerable_display_name`, `triggerable_type_label`, and `triggerable_show_route` accessors for polymorphic view rendering
- Three trigger flows:
  1. Single hook — `DeployHookController::confirmTrigger()` → `executeTrigger()` → `triggerResult()`
  2. Multi-hook — `TriggerBuildsController::select()` → `trigger()` → `TriggerBuildsResultController`
  3. Automatic — `StaticSiteDeliveryStrategy` fires all enabled hooks after persisting a published digest
- Hook URLs are stored encrypted; never logged or displayed after creation
- `last_triggered_at`, `last_build_id`, `last_trigger_status` recorded on every attempt — success or failure

## API

- The public API uses a bearer token plus a `RequestingDomain` header for authentication
- Bearer tokens are stored as bcrypt hashes — never as plain text
- The API has an on/off switch persisted in the `api_controls` database table
- `PublishDigest` auto-enables the API when processing a static site list via `ApiControl::getStatus()` and `ApiControl::instance()->enable()`
- Admin-only access checks in API management controllers use `if (! auth()->user()->can('admin'))` with a redirect, not `abort_if`, so non-admin users are redirected gracefully within the Admin UI
- API dashboard shows pending fetch warnings for published digests awaiting static site retrieval
- See `MEDIA_PLATFORM/API/v1/README.md` for full API documentation

## Podcast Studio

### Assembly Line Model

- The Podcast Studio follows an assembly line model: episodes move through stations from idea to publication
- The stations are: Drafting → Pre-Production → Episode Creation → Recording → Post-Production → Publishing
- The Podcast Studio Dashboard (`PodcastStudio/Dashboard/`) is the main entry point — it shows the assembly line at a glance
- The main app dashboard links to the Podcast Studio Dashboard as a single card entry point

### Episode Drafts

- Every episode begins as a draft — drafts are mandatory, not optional
- Drafts are lightweight: only `title` is required at creation; all other fields are optional during drafting
- The `podcast_episode_drafts` table accumulates all inputs needed for episode creation
- Draft content supports Markdown, rendered via `Str::markdown()` with custom `.markdown-content` CSS in `head.blade.php`
- Confirmed guests are attached via the `podcast_guest_episode_draft` pivot; the `guest_notes` string field captures prospective guest names not yet in the system

### Pre-Production

- Pre-production is the focused process of finalizing a draft for production — distinct from the open-ended drafting phase
- A 4-step wizard at `PodcastEpisodeDrafts/PreProduction/`
- Upon completion, the draft's status changes from `working_on_draft` to `ready_to_create_production_episode`

### Create Production Episode — planned

- A wizard at `CreateProductionEpisode/` that converts a finalized draft into a `podcast_episodes` record
- Includes a pre-flight checklist and user confirmation before the one-way door
- Reuses existing Step3Controller derivation logic for the 30+ production fields

### Five Active Shows

- Controllers that list shows use a `private const ACTIVE_SHOWS` array to filter and order:
  1. The Bob Bloom Show
  2. The Bob Bloom Interviews
  3. PHP Serverless News
  4. PHP Serverless Profiles
  5. PHP Serverless Project Updates
- This pattern is used in: Create Draft wizard Step1, Pre-Production wizard Step1, Create Episode wizard Step1, Podcast Studio Dashboard

## UI & Blade

- Purple / `purple-700` accent theme throughout
- No modals — use dedicated confirmation pages for destructive actions
- No bulk delete on index pages
- Wizards for multi-step create flows
- Wizard step dots: each wizard has its own dedicated `_step_dots.blade.php` partial — never share step dot partials between wizards
- Section headers in show/edit views use `<div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">` above a `border border-purple-500 rounded-lg` card
- Informational hint text below form fields uses `<ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">`
- Dashboard layout: two-column grid (`md:grid-cols-2`), left column for everyday workflow, right column for admin housekeeping
- Dashboard section cards: `border border-purple-300 rounded-lg overflow-hidden`, header `bg-purple-50 border-b border-purple-300`, links with `<span class="text-purple-400 font-bold">›</span>` prefix
- Markdown rendering: use `{!! Str::markdown($content) !!}` wrapped in a `<div class="markdown-content">` — custom CSS defined in `head.blade.php` (Tailwind CDN does not include the typography plugin, so `prose` classes are not available)
- Tailwind CSS loaded via CDN (`<script src="https://cdn.tailwindcss.com"></script>`) — not compiled locally
- Alpine.js loaded via CDN

## Testing

- PHPUnit class-based tests are used for all tests
- Extend `Tests\TestCase` and use the `RefreshDatabase` trait per class
- CSRF is bypassed in `bootstrap/app.php` via `defined('PHPUNIT_COMPOSER_INSTALL')`
- Pest does not define this constant automatically, so it is manually defined at the top of `tests/Pest.php` with `define('PHPUNIT_COMPOSER_INSTALL', true)`
- Test namespaces mirror folder paths: `Tests\Feature\MEDIA_PLATFORM\Digest\ContentSources\Youtube\`
- Test namespaces mirror folder paths: `Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PodcastEpisodeDrafts\`
- Test namespaces mirror folder paths: `Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PodcastEpisodeDrafts\PreProduction\`
- Note: the tests folder hierarchy does not fully mirror `MEDIA_PLATFORM/`
- One test class per controller — e.g. `Step1ControllerTest`, `Step2ControllerTest`, `Step3ControllerTest`

### Before Writing Tests

1. Check database schema — understand which columns have defaults, which are nullable, and foreign key relationship names
2. Verify relationship names — read the model file to confirm exact relationship method names, return types, and related models
3. Test realistic states — don't assume empty model means all nulls; check for defaults. Don't assume `user_id` maps to a `user()` relationship
4. When testing form submissions that redirect back with errors, assert old input is preserved using `assertSessionHasOldInput()`
5. When testing views that list shows, create shows with titles from the `ACTIVE_SHOWS` constant — factory-generated random titles won't appear

### Coverage Goals

- Every controller method must have a corresponding test
- Tests must cover the happy path, validation errors, forbidden access (403), and not found (404)
- The test suite serves as a regression safety net — if Laravel, PHP, or any dependency updates and something breaks, the tests should catch it. Run the full test suite after every `composer update`

### General

- Always use PHPUnit class-based tests, following the pattern in `YoutubeChannelWizardControllerTest`
- Use `use RefreshDatabase;` as a trait on the test class
- Test class names mirror the controller they test, suffixed with `Test`
- Test method names are prefixed with `test_` and describe the behaviour being tested
- CSRF is bypassed via `defined('PHPUNIT_COMPOSER_INSTALL')` in `bootstrap/app.php`
- When a controller redirects instead of returning 403 for ownership failures, assert `assertRedirect()->assertSessionHas('error')` rather than `assertForbidden()`

## Controller method visibility

- Population methods in wizard Step3 controllers are `public` to allow direct unit testing of individual field population logic
- This is intentional — do not change them to `private` or `protected`

## Wizard conventions

- Each wizard step has its own dedicated controller: `Step1Controller`, `Step2Controller`, `Step3Controller`
- Session key pattern for wizard state: `wizard.<wizard-name>.<field>` — e.g. `wizard.create_episode.podcast_show_id`, `wizard.create_draft.podcast_show_id`, `wizard.draft_pre_production.draft_id`
- The final step controller owns all population methods and the database persist
- Population methods are named `get_field_name()` in snake_case
- Population methods are grouped and commented by section (General, Status, iTunes, Website, etc.)
- Section headings use `// --- SECTION NAME ---` style dividers
- Individual method headings use the box-drawing style:

```
// ┌────────────────────────────────────────────────────────────────────────┐
// │  method_name()                                                         │
// └────────────────────────────────────────────────────────────────────────┘
```

- Major section headings (Population Methods, Helper Methods) use:

```
// ╔════════════════════════════════════════════════════════════════════════╗
// ║  SECTION NAME                                                          ║
// ╚════════════════════════════════════════════════════════════════════════╝
```

## Gemini Integration

- Client package: `gemini-php/laravel`
- Custom wrapper lives in `Gemini/` — this is what the application uses directly
- Usage: `Gemini::generativeModel(model: 'gemini-2.5-flash')->generateContent($prompt)`
- Prompt pattern: 2-3 sentence overview + bullet points, HTML formatted, ignores ads/filler

## Commenting

- Add lots (and lots) of comments in the source code
- For migrations, comment the database, and comment the fields (using `->comment()`)

## Misc conventions

- `digest-processing` is the exclusive use-case slug — hardcoded as the string `'digest-processing'` in both `LanguageModelController` and `LanguageModelUseCaseController`
- `cleanDescription()` on `YoutubeContentProcessor` is public intentionally — comment explains why
- No LLM call is made when falling back to description on transcript unavailability — the cleaned description HTML is returned directly