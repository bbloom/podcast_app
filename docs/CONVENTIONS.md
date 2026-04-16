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
│   └── v1/                ← active (public podcast API — see README.md)
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
│   ├── Processing/
│   ├── Publishing/
│   └── Services/
├── PodcastStudio/
│   ├── Management/        ← active (Controllers, Models, Requests, Routes)
│   ├── PreProduction/     ← active (CreateEpisode wizard — Step1, Step2, Step3)
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
- Database factories: `Database\Factories\Media_platform\...` maps to `database/factories/Media_platform/...`

### Views
- Root: `views/media_platform/`
- Dot-notation prefix: `media_platform.`
- Example: `view('media_platform.digest.content_sources.podcasts.index')`
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
- `database/migrations/media_platform/static_site_deploy_hooks/`
- Note: the migrations folder hierarchy does not fully mirror `MEDIA_PLATFORM/`

### Routes
- `routes/web.php` and `routes/console.php` are thin orchestrators that `require` feature route files
- Feature route files live inside their feature folder under a `Routes/` subfolder
- Example: `MEDIA_PLATFORM/Configuration/Routes/language_models.php`
- API routes are loaded via `routes/api.php`, which Laravel automatically prefixes with `/api`

## Naming
- "Youtube" not "YouTube" in code
- `ListModel` instead of `List` (reserved PHP word)
- Morph aliases: `youtube_channel`, `text_based_rss_feed`, `podcast`, `podcast_show`

## Models & Relationships
- All models use explicit `$table` names
- Polymorphic morph aliases registered in `AppServiceProvider` using `Relation::enforceMorphMap()`
- Ownership checks: `abort_if($model->user_id !== auth()->id(), 403)`
- Sensitive fields use Laravel's `encrypted` cast
- `DeployHook` uses `encrypted` cast on the `url` column

## Slugs
- Never use `Str::slug()`
- Always use the custom `makeSlug()` helper (preserves dots)

## Enums
- Enums are co-located within their feature folder under an `Enums/` subfolder
- The namespace mirrors the folder path exactly
- Examples:
  - `MEDIA_PLATFORM/Digest/Enums/OutputType.php` → `MediaPlatform\Digest\Enums\OutputType`
  - `MEDIA_PLATFORM/PodcastStudio/PostProduction/Enums/Bucket.php` → `MediaPlatform\PodcastStudio\PostProduction\Enums\Bucket`
  - `MEDIA_PLATFORM/StaticSiteDeployHooks/Enums/DeployHookProvider.php` → `MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider`
- There is no global top-level `Enums/` folder

## Seeding
- Seeding of admin/sensitive data is gated behind `ADMIN_SEEDING_ENABLED` in `.env`
- Checked via `config/admin.php` — the gate lives in `DatabaseSeeder.php`, not in individual seeders
- Individual seeders do not need their own gate check
- `DeployHooksSeeder` seeds fake deploy hooks for all six podcast shows — local/testing only

## Static Site Deploy Hooks
- Shared infrastructure at `MEDIA_PLATFORM/StaticSiteDeployHooks/`
- Polymorphic — `triggerable_type` / `triggerable_id` — currently supports `podcast_show`; extensible to `digest_list` and others
- `DeployHookTriggerService::trigger(DeployHook $hook)` — fires one hook, records outcome, returns `DeployHookTriggerResult`
- `DeployHookTriggerResult` — immutable value object: `succeeded()`, `httpStatus()`, `buildId()`, `alreadyExists()`, `errorMessage()`
- Two trigger flows:
  1. Single hook — `DeployHookController::confirmTrigger()` → `executeTrigger()` → `triggerResult()`
  2. Multi-hook — `TriggerBuildsController::select()` → `trigger()` → `TriggerBuildsResultController`
- Hook URLs are stored encrypted; never logged or displayed after creation
- `last_triggered_at`, `last_build_id`, `last_trigger_status` recorded on every attempt — success or failure

## API
- The public API uses a bearer token plus a `RequestingDomain` header for authentication
- Bearer tokens are stored as bcrypt hashes — never as plain text
- The API has an on/off switch persisted in the `api_controls` database table
- Admin-only access checks in API management controllers use `if (! auth()->user()->can('admin'))` with a redirect, not `abort_if`, so non-admin users are redirected gracefully within the Admin UI
- See `MEDIA_PLATFORM/API/v1/README.md` for full API documentation

## UI & Blade
- Purple / `purple-700` accent theme throughout
- No modals — use dedicated confirmation pages for destructive actions
- No bulk delete on index pages
- Wizards used for multi-step create flows
- Wizard step dots: each wizard has its own dedicated `_step_dots.blade.php` partial — never share step dot partials between wizards
- Section headers in show/edit views use `<div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">` above a `border border-purple-500 rounded-lg` card
- Informational hint text below form fields uses `<ul class="mt-3 ml-3 space-y-1 text-xs text-gray-400 list-disc list-outside pl-5">`
- Dashboard layout: two-column grid (`md:grid-cols-2`), left column for everyday workflow, right column for admin housekeeping
- Dashboard section cards: `border border-purple-300 rounded-lg overflow-hidden`, header `bg-purple-50 border-b border-purple-300`, links with `‹span class="text-purple-400 font-bold"››‹/span›` prefix

## Testing
- PHPUnit class-based tests are used for all tests.
- Extend Tests\TestCase and use the RefreshDatabase trait per class.
- CSRF is bypassed in bootstrap/app.php via defined('PHPUNIT_COMPOSER_INSTALL').
- Pest does not define this constant automatically, so it is manually defined at the top of `tests/Pest.php` with `define('PHPUNIT_COMPOSER_INSTALL', true)`
- Test namespaces mirror folder paths: Tests\Feature\MEDIA_PLATFORM\Digest\ContentSources\Youtube\
- Note: the tests folder hierarchy does not fully mirror MEDIA_PLATFORM/
- One test class per controller — e.g. `Step1ControllerTest`, `Step2ControllerTest`, `Step3ControllerTest`

## Controller method visibility
- Population methods in wizard Step3 controllers are `public` to allow direct unit testing of individual field population logic
- This is intentional — do not change them to `private` or `protected`

## Wizard conventions
- Each wizard step has its own dedicated controller: `Step1Controller`, `Step2Controller`, `Step3Controller`
- Session key pattern for wizard state: `wizard.<wizard-name>.<field>` — e.g. `wizard.create_episode.podcast_show_id`
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
- Prompt pattern: 2–3 sentence overview + bullet points, HTML formatted, ignores ads/filler

## Commenting
- Add lots (and lots) of comments in the source code
- For migrations, comment the database, and comment the fields (using `->comment()`)

## Misc conventions
- `digest-processing` is the exclusive use-case slug — hardcoded as the string `'digest-processing'` in both `LanguageModelController` and `LanguageModelUseCaseController`
- `cleanDescription()` on `YoutubeContentProcessor` is public intentionally — comment explains why
- No LLM call is made when falling back to description on transcript unavailability — the cleaned description HTML is returned directly