# Static Site Output Type — Implementation Plan

## Overview

Add a third output type (`static_site`) to the Digest system. Unlike the existing push-based delivery mechanisms (email, SFTP webpage), this output type uses a pull model: the app persists structured digest data, fires a deploy hook to trigger a static site rebuild (Cloudflare Pages / Astro), and the static site generator calls the app's API to fetch the data.

This feature builds on two existing systems: **Output Destinations** (the Digest delivery mechanism) and **Static Site Deploy Hooks** (the polymorphic deploy hook infrastructure).

---

## Guiding Principles

- **No production impact** — pre-production, so original migrations are edited directly, never ALTER migrations.
- **No recreation of existing plumbing** — deploy hooks, API auth, and API middleware are reused as-is.
- **Decouple build from deliver** — `PublishDigest` is refactored so digest assembly is output-type-agnostic; delivery is delegated to per-type strategy classes.
- **`published_digests` records are only created for `static_site` lists** — email and SFTP continue to work exactly as they do today.
- **Retention policy** is deferred to a separate Q&A after this feature is complete.
- **Simulation testing** is deferred until the feature is working.
- **Lean, clean, intuitively understandable code** — delete and rebuild where it produces the best result.

---

## Phase 1: Data Layer

### 1.1 — Edit `OutputType` enum

**File:** `MEDIA_PLATFORM/Digest/Enums/OutputType.php`

**Changes:**
- Add `case StaticSite = 'static_site'`
- Update `label()`: `self::StaticSite => 'Static Site'`
- Update `requiresDestination()`: `self::StaticSite => false`
- Add new method `requiresDeployHooks()`:
  ```php
  public function requiresDeployHooks(): bool
  {
      return match ($this) {
          self::StaticSite => true,
          default          => false,
      };
  }
  ```
- Update the `ADDING A NEW TYPE` docblock to reflect the refactored architecture (delivery strategies, not inline logic in PublishDigest)

### 1.2 — Edit `lists` table migration — change `output_type` from MySQL enum to string

**File:** `database/migrations/media_platform/digests/lists_and_feeds/2026_02_27_000003_create_lists_table.php`

**Changes:**
- Replace `$table->enum('output_type', ['webpage', 'email'])` with `$table->string('output_type')`
- Update the column comment to: `'Delivery mechanism: webpage, email, or static_site. Validated by the PHP OutputType enum, not by a database constraint.'`
- Add `$table->unsignedInteger('retention_count')->default(10)->comment('Number of published_digests records to retain. Only used when output_type is static_site.');`

### 1.3 — Create `published_digests` table migration

**File:** `database/migrations/media_platform/digests/processing/2026_04_18_000001_create_published_digests_table.php`

**Columns:**
- `id` — primary key
- `list_id` — foreign key to `lists`, cascadeOnDelete. Comment: `'The list this digest was built for.'`
- `user_id` — foreign key to `users`, cascadeOnDelete. Comment: `'Owner of the list. Stored for query convenience and auditing.'`
- `slug` — string. Comment: `'URL-friendly identifier for this digest run, e.g. morning-tech-digest-2026-04-15. Used as the page path on the static site.'`
- `digest_date` — date. Comment: `'The date this digest pertains to.'`
- `total_items` — unsignedInteger. Comment: `'Number of content items in this digest.'`
- `source_count` — unsignedInteger. Comment: `'Number of distinct content sources in this digest.'`
- `payload` — json. Comment: `'The full structured digest data (groups, items with source_url, source_title, source_description, source_published_at, summary_html, source_type). Stored as JSON for API delivery.'`
- `deploy_hook_fired_at` — timestamp, nullable. Comment: `'When the deploy hook was fired after persisting this digest. Null if not yet fired or if firing failed.'`
- `api_fetched_at` — timestamp, nullable. Comment: `'When the static site generator last fetched this digest via the API. Null until fetched. Used for observability.'`
- `timestamps`

**Indexes:**
- `$table->index(['list_id', 'digest_date'], 'published_digests_list_date')` — for API queries
- `$table->unique(['list_id', 'slug'], 'published_digests_list_slug_unique')` — prevent duplicate slugs per list

**Table comment:** `'Persisted digest payloads for static site output type. One record per digest run per list. The API serves these to Astro during static site builds.'`

**Register migration path:** Add `database_path('migrations/media_platform/digests/processing')` — already registered in `AppServiceProvider`.

### 1.4 — Create `PublishedDigest` model

**File:** `MEDIA_PLATFORM/Digest/Publishing/Models/PublishedDigest.php`

**Namespace:** `MediaPlatform\Digest\Publishing\Models`

- `$table = 'published_digests'`
- `$fillable`: all columns except `id` and timestamps
- `$casts`: `'payload' => 'array'`, `'digest_date' => 'date'`, `'deploy_hook_fired_at' => 'datetime'`, `'api_fetched_at' => 'datetime'`
- Relationships: `list()` → BelongsTo ListModel, `user()` → BelongsTo User
- Factory resolution: `PublishedDigestFactory`

### 1.5 — Create `PublishedDigestFactory`

**File:** `database/factories/Media_platform/Digest/Publishing/PublishedDigestFactory.php`

- `forUser(User $user)` state
- `forList(ListModel $list)` state
- Default definition generates realistic fake payload JSON

### 1.6 — Update `ListModel`

**File:** `MEDIA_PLATFORM/Digest/ContentSources/Lists/Models/ListModel.php`

**Changes:**
- Add `'retention_count'` to `$fillable`
- Add `'retention_count' => 'integer'` to `$casts`
- Add relationship:
  ```php
  public function publishedDigests(): HasMany
  {
      return $this->hasMany(PublishedDigest::class, 'list_id');
  }
  ```
- Add relationship:
  ```php
  public function deployHooks(): MorphMany
  {
      return $this->morphMany(DeployHook::class, 'triggerable');
  }
  ```
- Update the docblock comment about `output_destination_id` to mention it is also null for `OutputType::StaticSite`

### 1.7 — Register `digest_list` morph alias

**File:** `app/Providers/AppServiceProvider.php`

**Changes:**
- Add to `Relation::enforceMorphMap()`:
  ```php
  'digest_list' => \MediaPlatform\Digest\ContentSources\Lists\Models\ListModel::class,
  ```

### 1.8 — Update `ListModelFactory`

**File:** `database/factories/Media_platform/Digest/Lists/ListModelFactory.php`

**Changes:**
- Add `staticSite()` state method:
  ```php
  public function staticSite(): static
  {
      return $this->state(fn () => [
          'output_type'           => 'static_site',
          'output_destination_id' => null,
          'notify_by_email'       => true,
          'retention_count'       => 10,
      ]);
  }
  ```

### 1.9 — Add `getStatus()` to `ApiControl`

**File:** `MEDIA_PLATFORM/API/v1/Models/ApiControl.php`

**Changes:**
- Add method:
  ```php
  public static function getStatus(): bool
  {
      return static::instance()->is_enabled;
  }
  ```

---

## Phase 2: Refactor `PublishDigest` — Delivery Strategies

### 2.1 — Create delivery strategy interface

**File:** `MEDIA_PLATFORM/Digest/Publishing/Contracts/DigestDeliveryStrategy.php`

**Namespace:** `MediaPlatform\Digest\Publishing\Contracts`

```php
interface DigestDeliveryStrategy
{
    /**
     * Deliver a built digest for the given list.
     * Returns true on success, false on failure.
     */
    public function deliver(ListModel $list, array $digestData, DigestBuilderService $builder): bool;
}
```

### 2.2 — Create `EmailDeliveryStrategy`

**File:** `MEDIA_PLATFORM/Digest/Publishing/Strategies/EmailDeliveryStrategy.php`

**Namespace:** `MediaPlatform\Digest\Publishing\Strategies`

- Extract the `deliverEmail()` method body from current `PublishDigest` into `deliver()`.
- The `DigestMailable`, `Mail::to()`, `AdminAlert::raiseIfNew()` logic moves here unchanged.

### 2.3 — Create `WebpageDeliveryStrategy`

**File:** `MEDIA_PLATFORM/Digest/Publishing/Strategies/WebpageDeliveryStrategy.php`

**Namespace:** `MediaPlatform\Digest\Publishing\Strategies`

- Constructor receives `SftpService` via dependency injection.
- Extract the `deliverWebpage()` method body from current `PublishDigest` into `deliver()`.
- Blade rendering, SFTP upload, `DigestReadyNotification` logic moves here unchanged.
- `buildSlug()` and `buildExcerpt()` calls remain on `DigestBuilderService`.

### 2.4 — Create `StaticSiteDeliveryStrategy`

**File:** `MEDIA_PLATFORM/Digest/Publishing/Strategies/StaticSiteDeliveryStrategy.php`

**Namespace:** `MediaPlatform\Digest\Publishing\Strategies`

- Constructor receives `DeployHookTriggerService` via dependency injection.
- `deliver()` does:
  1. Build the JSON payload from `$digestData` (groups with items including `source_url`, `source_title`, `source_description`, `source_published_at`, `summary_html`, `source_type`; plus metadata `slug`, `digest_date`, `total_items`, `source_count`).
  2. Create a `PublishedDigest` record with the payload.
  3. Prune old `published_digests` records for this list beyond `$list->retention_count`.
  4. Fire all enabled deploy hooks attached to this list (`$list->deployHooks()->where('enabled', true)->get()`, then `$triggerService->trigger($hook)` for each).
  5. Record `deploy_hook_fired_at` on the published digest.
  6. If `$list->notify_by_email` is true, send a `StaticSiteDigestReadyNotification`.
  7. Return true if at least the published digest was persisted successfully (deploy hook failure is logged but non-fatal — the data is there for manual trigger).

### 2.5 — Create `StaticSiteDigestReadyNotification`

**File:** `MEDIA_PLATFORM/Digest/Publishing/Notifications/StaticSiteDigestReadyNotification.php`

**Namespace:** `MediaPlatform\Digest\Publishing\Notifications`

- Constructor: `ListModel $list`, `string $slug`, `string $excerpt`
- `toMail()`: Subject line "Your digest is ready: {list name}". Body includes the list name, excerpt ("5 items from 3 sources"), and the slug/date. Does NOT include the actual digest content. Does NOT construct a URL (the user knows where their static site lives).

### 2.6 — Create `DeliveryStrategyResolver`

**File:** `MEDIA_PLATFORM/Digest/Publishing/Services/DeliveryStrategyResolver.php`

**Namespace:** `MediaPlatform\Digest\Publishing\Services`

```php
class DeliveryStrategyResolver
{
    public function resolve(OutputType $type): DigestDeliveryStrategy
    {
        return match ($type) {
            OutputType::Email      => app(EmailDeliveryStrategy::class),
            OutputType::Webpage    => app(WebpageDeliveryStrategy::class),
            OutputType::StaticSite => app(StaticSiteDeliveryStrategy::class),
        };
    }
}
```

### 2.7 — Refactor `PublishDigest` job

**File:** `MEDIA_PLATFORM/Digest/Processing/Jobs/PublishDigest.php`

**Changes:**
- Remove `SftpService` from `handle()` signature.
- Add `DeliveryStrategyResolver` to `handle()` signature.
- Remove `deliverEmail()` and `deliverWebpage()` private methods entirely.
- Replace the `match` block with:
  ```php
  $strategy = $resolver->resolve($list->output_type);
  $success  = $strategy->deliver($list, $digestData, $builder);
  ```
- Update the gate check: static site lists should also be blocked by ProcessingGate (same as webpage).
- Add API guard: before building, if any enabled list has `output_type = static_site`, ensure the API is on:
  ```php
  if ($list->output_type === OutputType::StaticSite) {
      if (! ApiControl::getStatus()) {
          ApiControl::instance()->enable();
          Log::info("PublishDigest: API auto-enabled for static site list '{$list->name}'.");
      }
  }
  ```
- Update the class docblock to describe the new strategy-based architecture.
- Change `deliverEmail` and `deliverWebpage` from `private` to removed (they no longer exist in this class).

### 2.8 — Update `DigestBuilderService` — make `buildSlug()` and `buildExcerpt()` public (already public — no change needed)

Confirmed: `buildSlug()` and `buildExcerpt()` are already public. No change required.

---

## Phase 3: API Endpoint

### 3.1 — Create `DigestApiController`

**File:** `MEDIA_PLATFORM/API/v1/Controllers/DigestApiController.php`

**Namespace:** `MediaPlatform\API\v1\Controllers`

- Single `__invoke(Request $request)` method.
- Reads the list identifier from the `X-Digest-List` request header (the list `name` field — human-readable and stable).
- Looks up the list by name, confirms `output_type = static_site`.
- Queries `published_digests` for that list, ordered by `digest_date` descending, limited by `retention_count`.
- Updates `api_fetched_at` on all returned records to `now()`.
- Returns the JSON response:
  ```json
  {
      "list": {
          "name": "...",
          "description": "..."
      },
      "digests": [
          {
              "slug": "...",
              "date": "2026-04-15",
              "total_items": 5,
              "source_count": 3,
              "groups": [ ... ]
          }
      ]
  }
  ```
- Uses a `DigestResource` (or inline transformation) to shape the response.

### 3.2 — Create `DigestApiService`

**File:** `MEDIA_PLATFORM/API/v1/Services/DigestApiService.php`

**Namespace:** `MediaPlatform\API\v1\Services`

- `getDigestsForList(ListModel $list): array` — queries `published_digests`, shapes the response array, updates `api_fetched_at`.
- Keeps the controller thin.

### 3.3 — Add API route

**File:** `MEDIA_PLATFORM/API/v1/Routes/api.php`

**Changes:**
- Add inside the existing middleware group:
  ```php
  Route::get('/v1/digests', DigestApiController::class)
      ->name('api.v1.digests');
  ```

### 3.4 — Update API README

**File:** `MEDIA_PLATFORM/API/v1/README.md`

**Changes:**
- Add documentation for the new `GET /api/v1/digests` endpoint.
- Document the `X-Digest-List` header requirement.
- Document the response structure.
- Add to the endpoints table and test coverage table.

---

## Phase 4: Deploy Hooks — Support `digest_list` Triggerable Type

### 4.1 — Refactor `DeployHookController::resolveAndAuthorizeTriggerable()`

**File:** `MEDIA_PLATFORM/StaticSiteDeployHooks/Controllers/DeployHookController.php`

**Changes:**
- Extend the method to support `digest_list`:
  ```php
  private function resolveAndAuthorizeTriggerable(string $type, int $id): PodcastShow|ListModel|RedirectResponse
  {
      $triggerable = match ($type) {
          'podcast_show' => PodcastShow::find($id),
          'digest_list'  => ListModel::find($id),
          default        => null,
      };

      if (! $triggerable) {
          return redirect()->route('deploy_hooks.index')
              ->with('error', 'The selected item could not be found.');
      }

      if ($triggerable->user_id !== auth()->id()) {
          return redirect()->route('deploy_hooks.index')
              ->with('error', 'You do not have permission to access that item.');
      }

      return $triggerable;
  }
  ```
- Update `create()` to pass both shows and lists to the view:
  ```php
  public function create(Request $request): View
  {
      $shows     = $this->userShows();
      $lists     = $this->userLists();
      $providers = DeployHookProvider::cases();

      // Pre-fill triggerable from query params (used by list wizard redirect)
      $prefillType = $request->query('triggerable_type');
      $prefillId   = $request->query('triggerable_id');

      return view('media_platform.static_site_deploy_hooks.create', compact(
          'shows', 'lists', 'providers', 'prefillType', 'prefillId'
      ));
  }
  ```
- Add `userLists()` helper:
  ```php
  private function userLists()
  {
      return ListModel::where('user_id', auth()->id())
          ->where('output_type', 'static_site')
          ->orderBy('name')
          ->get();
  }
  ```
- Update `edit()` to pass both shows and lists.
- Update `index()` to also query hooks where `triggerable_type = 'digest_list'`.
- Accept `redirect_to` query parameter in `store()` — after saving, if `redirect_to` is set and is a valid route, redirect there instead of to the show page.

### 4.2 — Update deploy hook create view

**File:** `views/media_platform/static_site_deploy_hooks/create.blade.php`

**Changes:**
- Replace the hidden `triggerable_type` input with a visible dropdown (or use Alpine.js to toggle between "Podcast Show" and "Digest List" sections).
- When `$prefillType` and `$prefillId` are set (from the list wizard redirect), pre-select the triggerable type and ID, and make the triggerable fields read-only.
- Show the appropriate dropdown (shows or lists) based on the selected triggerable type.

### 4.3 — Update deploy hook edit view

**File:** `views/media_platform/static_site_deploy_hooks/edit.blade.php`

**Changes:**
- Same triggerable type/ID dropdown treatment as the create view.
- Pre-fill from the existing hook's `triggerable_type` and `triggerable_id`.

### 4.4 — Update deploy hook index view

**File:** `views/media_platform/static_site_deploy_hooks/index.blade.php`

**Changes:**
- The "Show" column header should become "Owner" or similar, since hooks can now belong to lists.
- Display `$hook->triggerable->title` for podcast shows, `$hook->triggerable->name` for lists. Consider adding a helper method on `DeployHook` or using a match on `triggerable_type`.

### 4.5 — Update deploy hook show view

**File:** `views/media_platform/static_site_deploy_hooks/show.blade.php`

**Changes:**
- The "Show" label and link should adapt based on triggerable type. For `digest_list`, link to `route('lists.show', $hook->triggerable)` and display `$hook->triggerable->name`.

### 4.6 — Update remaining deploy hook views

**Files:**
- `views/media_platform/static_site_deploy_hooks/delete_confirm.blade.php` — update `$hook->triggerable->title` to handle both types
- `views/media_platform/static_site_deploy_hooks/trigger_confirm.blade.php` — same
- `views/media_platform/static_site_deploy_hooks/trigger_result.blade.php` — same

### 4.7 — Add display name helper to `DeployHook` model

**File:** `MEDIA_PLATFORM/StaticSiteDeployHooks/Models/DeployHook.php`

**Changes:**
- Add accessor method:
  ```php
  public function getTriggerableDisplayNameAttribute(): string
  {
      return match ($this->triggerable_type) {
          'podcast_show' => $this->triggerable->title ?? "Show #{$this->triggerable_id}",
          'digest_list'  => $this->triggerable->name ?? "List #{$this->triggerable_id}",
          default        => "#{$this->triggerable_id}",
      };
  }
  ```
- Use `$hook->triggerable_display_name` in all views instead of `$hook->triggerable->title`.

---

## Phase 5: List Wizard & Edit — Static Site Path

### 5.1 — Update list wizard step 3 (output type selection)

**File:** `views/media_platform/digest/content_sources/lists/wizard-step3.blade.php`

**Changes:**
- Add a third radio button: "Static Site" with value `static_site`.
- Add description text explaining what static site output means.

### 5.2 — Update `ListWizardController::step3Submit()`

**File:** `MEDIA_PLATFORM/Digest/ContentSources/Lists/Controllers/ListWizardController.php`

**Changes:**
- Update validation: `'output_type' => ['required', 'in:webpage,email,static_site']`
- Add routing logic:
  ```php
  if ($request->input('output_type') === 'static_site') {
      return redirect()->route('lists.create.step4_static_site');
  }
  ```

### 5.3 — Create list wizard step 4 (static site) — deploy hook association

**New file:** `views/media_platform/digest/content_sources/lists/wizard-step4-static-site.blade.php`

- Displays existing deploy hooks for this list (if editing) or a message that hooks can be added after list creation.
- Provides a link to the deploy hook create page with `redirect_to=lists.create.step5_static_site&triggerable_type=digest_list&triggerable_id=pending` (since the list doesn't exist yet during creation, deploy hooks are attached post-creation).
- **Alternative simpler approach:** Skip deploy hook association during the wizard. Instead, after step 6 (confirm & save), redirect to a "next steps" page that says "Your list has been created. Now add a deploy hook for it." with a link to the deploy hook create page pre-filled with the new list's type and ID. This avoids the chicken-and-egg problem of needing a list ID before the list exists.

**Recommendation:** Use the simpler approach. The list must exist before a deploy hook can reference it. The wizard creates the list at step 6, then the "done" page (step 7) links to deploy hook creation with `triggerable_type=digest_list&triggerable_id={new list id}`.

### 5.4 — Create list wizard step 5 (static site) — notify by email

**New file:** `views/media_platform/digest/content_sources/lists/wizard-step5-static-site.blade.php`

- Same yes/no radio as the existing step 5 for webpage, but with copy explaining that the notification email will confirm the digest was published and the deploy hook was fired — it will not contain the digest content itself.

### 5.5 — Update list wizard step 6 (confirm & save)

**File:** `views/media_platform/digest/content_sources/lists/wizard-step6.blade.php`

**Changes:**
- Add a section for static site output type showing the summary: output type, notification preference.
- No output destination or deploy hook info shown here (deploy hooks are added post-creation).

### 5.6 — Update `ListWizardController::step6Submit()`

**File:** `MEDIA_PLATFORM/Digest/ContentSources/Lists/Controllers/ListWizardController.php`

**Changes:**
- Handle `static_site` output type:
  ```php
  'output_type'           => $outputType,
  'output_destination_id' => $outputType === 'webpage' ? ($data['output_destination_id'] ?? null) : null,
  'notify_by_email'       => in_array($outputType, ['webpage', 'static_site']) ? ($data['notify_by_email'] ?? false) : false,
  'retention_count'       => $outputType === 'static_site' ? 10 : 10, // default
  ```

### 5.7 — Update list wizard step 7 (done page)

**File:** `views/media_platform/digest/content_sources/lists/wizard-step7.blade.php`

**Changes:**
- For static site lists, add a prominent call-to-action: "Add a deploy hook for this list" linking to `route('deploy_hooks.create', ['triggerable_type' => 'digest_list', 'triggerable_id' => $list->id, 'redirect_to' => 'lists.show'])`.

### 5.8 — Update list edit form

**File:** `views/media_platform/digest/content_sources/lists/edit.blade.php`

**Changes:**
- Add `static_site` radio button to the output type section.
- Show/hide the output destination dropdown: visible only when `outputType === 'webpage'` (already handled by Alpine.js `x-show`).
- Show/hide the notify_by_email option: visible when `outputType === 'webpage'` OR `outputType === 'static_site'`.
- Add a retention_count field (number input) visible only when `outputType === 'static_site'`.
- Add a "Deploy Hooks" section visible only when `outputType === 'static_site'`, listing attached hooks with a link to manage them.

### 5.9 — Update `ListWizardController::update()`

**File:** `MEDIA_PLATFORM/Digest/ContentSources/Lists/Controllers/ListWizardController.php`

**Changes:**
- Update validation: `'output_type' => ['required', 'in:webpage,email,static_site']`
- Add validation: `'retention_count' => ['nullable', 'integer', 'min:1', 'max:100']`
- Handle static_site in the update logic:
  ```php
  'output_destination_id' => $outputType === 'webpage' ? $destinationId : null,
  'notify_by_email'       => in_array($outputType, ['webpage', 'static_site']) ? $request->boolean('notify_by_email') : false,
  'retention_count'       => $outputType === 'static_site' ? ($request->input('retention_count') ?? 10) : $list->retention_count,
  ```

### 5.10 — Update list show page

**File:** `views/media_platform/digest/content_sources/lists/show.blade.php`

**Changes:**
- For static site lists, display:
  - Output type: "Static Site"
  - Retention: "{n} digests"
  - Deploy hooks section: list attached hooks with status, link to manage
  - **"Trigger Deploy Hook" button** — links to the deploy hook trigger confirm page for each attached hook (reuses existing deploy hook trigger flow)
  - Published digests section: show the most recent published digests with date, slug, item count, and `api_fetched_at` status

### 5.11 — Add new wizard routes

**File:** `MEDIA_PLATFORM/Digest/Routes/lists.php`

**Changes:**
- Add routes for the new static site wizard steps:
  ```
  GET  /lists/create/step4-static-site    → step4StaticSite
  POST /lists/create/step4-static-site    → step4StaticSiteSubmit
  GET  /lists/create/step5-static-site    → step5StaticSite
  POST /lists/create/step5-static-site    → step5StaticSiteSubmit
  ```

### 5.12 — Add wizard controller methods

**File:** `MEDIA_PLATFORM/Digest/ContentSources/Lists/Controllers/ListWizardController.php`

**Changes:**
- Add `step4StaticSite()`, `step4StaticSiteSubmit()`, `step5StaticSite()`, `step5StaticSiteSubmit()` methods mirroring the pattern of existing wizard steps.

---

## Phase 6: API Dashboard — Deploy Hook Observability

### 6.1 — Update API Management Dashboard

**File:** `MEDIA_PLATFORM/API/v1/Dashboard/DashboardController.php`

**Changes:**
- Query for static site lists that have published digests where `deploy_hook_fired_at` is not null but `api_fetched_at` is null — these are "waiting for the static site to fetch."
- Pass this data to the view as `$pendingFetches`.

### 6.2 — Update API Management Dashboard view

**File:** `views/media_platform/api/v1/dashboard.blade.php` (or wherever this view lives)

**Changes:**
- In the "API Status" section, if `$pendingFetches` is not empty, show a warning:
  ```
  ⚠️ Waiting for static site build:
  • "Morning Tech Digest" — deploy hook fired at 2:15 AM, not yet fetched
  ```
- This prevents accidental manual API disable while a build is pending.

---

## Phase 7: Tests

### 7.1 — Update existing test: `PublishDigestTest.php`

**File:** `tests/Feature/MEDIA_PLATFORM/Digest/Processing/PublishDigestTest.php`

**Changes:**
- Update `runPublishDigest()` helper: replace `SftpService` parameter with the new strategy-based approach. Since `PublishDigest::handle()` now receives `DeliveryStrategyResolver` instead of `SftpService`, the helper needs to bind mocked strategies via the container.
- Update all existing tests to work with the refactored `handle()` signature.
- Add **GROUP 8: Static site delivery**:
  - `it persists a PublishedDigest record for static site list`
  - `it fires deploy hooks after persisting PublishedDigest`
  - `it calls markAsIncluded after successful static site delivery`
  - `it does not call markAsIncluded when PublishedDigest persistence fails`
  - `it sends StaticSiteDigestReadyNotification when notify_by_email is true`
  - `it does not send notification when notify_by_email is false`
  - `it updates last_run_at after successful static site delivery`
  - `it prunes old PublishedDigest records beyond retention_count`
  - `it auto-enables the API when processing a static site list`
  - `it logs deploy hook failure but still returns true (data persisted)`

### 7.2 — Update existing test: `ListCrudTest.php`

**File:** `tests/Feature/MEDIA_PLATFORM/Digest/ContentSources/ListCrudTest.php`

**Changes:**
- Add to GROUP 3 (update — happy paths):
  - `test update saves static site list correctly` — verify output_type, output_destination_id null, notify_by_email, retention_count
  - `test update clears destination when switching to static_site`
- Update GROUP 4 (validation):
  - Ensure `static_site` is accepted as valid output_type

### 7.3 — Update existing test: `WizardFlowTest.php`

**File:** `tests/Feature/MEDIA_PLATFORM/Digest/ContentSources/WizardFlowTest.php`

**Changes:**
- Add test: `test_list_wizard_static_site_path` — walk through steps 1→2→3 (select static_site)→step4-static-site→step5-static-site→step6 confirm→step7 done.

### 7.4 — Create new test: `StaticSiteDeliveryStrategyTest.php`

**File:** `tests/Feature/MEDIA_PLATFORM/Digest/Publishing/StaticSiteDeliveryStrategyTest.php`

**Tests:**
- `it creates a PublishedDigest record with correct payload structure`
- `it builds correct slug from list name and date`
- `it fires all enabled deploy hooks for the list`
- `it skips disabled deploy hooks`
- `it records deploy_hook_fired_at on the PublishedDigest`
- `it prunes oldest records when count exceeds retention_count`
- `it handles deploy hook failure gracefully`
- `it sends StaticSiteDigestReadyNotification when notify_by_email is true`

### 7.5 — Create new test: `DigestApiControllerTest.php`

**File:** `tests/Feature/MEDIA_PLATFORM/API/v1/DigestApiControllerTest.php`

**Tests:**
- `it returns 503 when API is disabled`
- `it returns 403 with invalid bearer token`
- `it returns 403 with invalid domain header`
- `it returns 404 when list name not found`
- `it returns 200 with correct JSON structure`
- `it returns digests ordered by date descending`
- `it returns only digests for the requested list`
- `it updates api_fetched_at on returned records`
- `it respects retention_count limit`
- `it includes source_description in item data`
- `it returns empty digests array when no published digests exist`

### 7.6 — Create new test: `DeployHookControllerDigestListTest.php`

**File:** `tests/Feature/MEDIA_PLATFORM/StaticSiteDeployHooks/DeployHookControllerDigestListTest.php`

**Tests:**
- `it creates a deploy hook for a digest list`
- `it rejects deploy hook creation for a list not owned by user`
- `it displays digest list hooks on the index page`
- `it resolves and authorizes digest_list triggerable type`
- `it shows correct display name for digest list hooks`
- `it allows manual trigger of a digest list hook`

### 7.7 — Create new test: `EmailDeliveryStrategyTest.php`

**File:** `tests/Feature/MEDIA_PLATFORM/Digest/Publishing/EmailDeliveryStrategyTest.php`

**Tests:**
- `it sends DigestMailable to list owner`
- `it returns false on mail failure`
- `it raises AdminAlert on mail failure`

### 7.8 — Create new test: `WebpageDeliveryStrategyTest.php`

**File:** `tests/Feature/MEDIA_PLATFORM/Digest/Publishing/WebpageDeliveryStrategyTest.php`

**Tests:**
- `it uploads HTML via SFTP`
- `it returns false when output destination is missing`
- `it returns false when SFTP upload fails`
- `it sends DigestReadyNotification when notify_by_email is true`
- `it does not send notification when notify_by_email is false`

### 7.9 — Update `DeployHookRequest` test coverage (if exists)

Ensure validation accepts `digest_list` as a valid `triggerable_type`.

---

## Phase 8: Seeders & Factories

### 8.1 — Create `PublishedDigestsSeeder`

**File:** `database/seeders/Media_platform/Digest/PublishedDigestsSeeder.php`

- Seeds 5–10 published digest records for each static site list.
- Uses realistic fake payload data.
- Gated behind `ADMIN_SEEDING_ENABLED`.

### 8.2 — Update `DeployHooksSeeder`

**File:** `database/seeders/Media_platform/StaticSiteDeployHooks/DeployHooksSeeder.php`

**Changes:**
- Add seeding of deploy hooks for static site lists (in addition to existing podcast show hooks).

### 8.3 — Create a static site list in the main seeder

Ensure `DatabaseSeeder.php` (or the list seeder) creates at least one list with `output_type = 'static_site'` so the UI can be explored in development.

---

## Phase 9: Documentation Updates

### 9.1 — Update `ARCHITECTURE.md`

- Add `published_digests` to the database tables list.
- Update the "Output Destinations" section to mention static site.
- Document the delivery strategy pattern.
- Update the deploy hooks section to mention `digest_list` support.
- Document the API auto-enable guard.

### 9.2 — Update `CONVENTIONS.md`

- Add `digest_list` to the morph aliases list.
- Document the delivery strategy pattern.

### 9.3 — Update `OutputType.php` docblock

- Replace the current "ADDING A NEW TYPE" checklist with one that reflects the strategy-based architecture.

---

## Files to Delete

**None.** No files are deleted in this plan. The existing `deliverEmail()` and `deliverWebpage()` private methods inside `PublishDigest.php` are removed (not the file — the methods are extracted to strategy classes and the originals are deleted from the job). All other changes are edits to existing files or creation of new files.

---

## New Files Summary

| File | Type |
|---|---|
| `database/migrations/.../2026_04_18_000001_create_published_digests_table.php` | Migration |
| `MEDIA_PLATFORM/Digest/Publishing/Models/PublishedDigest.php` | Model |
| `database/factories/Media_platform/Digest/Publishing/PublishedDigestFactory.php` | Factory |
| `MEDIA_PLATFORM/Digest/Publishing/Contracts/DigestDeliveryStrategy.php` | Interface |
| `MEDIA_PLATFORM/Digest/Publishing/Strategies/EmailDeliveryStrategy.php` | Strategy |
| `MEDIA_PLATFORM/Digest/Publishing/Strategies/WebpageDeliveryStrategy.php` | Strategy |
| `MEDIA_PLATFORM/Digest/Publishing/Strategies/StaticSiteDeliveryStrategy.php` | Strategy |
| `MEDIA_PLATFORM/Digest/Publishing/Services/DeliveryStrategyResolver.php` | Service |
| `MEDIA_PLATFORM/Digest/Publishing/Notifications/StaticSiteDigestReadyNotification.php` | Notification |
| `MEDIA_PLATFORM/API/v1/Controllers/DigestApiController.php` | Controller |
| `MEDIA_PLATFORM/API/v1/Services/DigestApiService.php` | Service |
| `views/media_platform/digest/content_sources/lists/wizard-step4-static-site.blade.php` | View |
| `views/media_platform/digest/content_sources/lists/wizard-step5-static-site.blade.php` | View |
| `database/seeders/Media_platform/Digest/PublishedDigestsSeeder.php` | Seeder |
| `tests/Feature/MEDIA_PLATFORM/Digest/Publishing/StaticSiteDeliveryStrategyTest.php` | Test |
| `tests/Feature/MEDIA_PLATFORM/Digest/Publishing/EmailDeliveryStrategyTest.php` | Test |
| `tests/Feature/MEDIA_PLATFORM/Digest/Publishing/WebpageDeliveryStrategyTest.php` | Test |
| `tests/Feature/MEDIA_PLATFORM/API/v1/DigestApiControllerTest.php` | Test |
| `tests/Feature/MEDIA_PLATFORM/StaticSiteDeployHooks/DeployHookControllerDigestListTest.php` | Test |

## Edited Files Summary

| File | Nature of Change |
|---|---|
| `MEDIA_PLATFORM/Digest/Enums/OutputType.php` | Add `StaticSite` case, add `requiresDeployHooks()` |
| `database/migrations/.../2026_02_27_000003_create_lists_table.php` | Change `output_type` to string, add `retention_count` |
| `MEDIA_PLATFORM/Digest/ContentSources/Lists/Models/ListModel.php` | Add `retention_count` to fillable/casts, add relationships |
| `database/factories/.../ListModelFactory.php` | Add `staticSite()` state |
| `app/Providers/AppServiceProvider.php` | Add `digest_list` morph alias |
| `MEDIA_PLATFORM/API/v1/Models/ApiControl.php` | Add `getStatus()` |
| `MEDIA_PLATFORM/Digest/Processing/Jobs/PublishDigest.php` | Refactor to use strategies, add API guard |
| `MEDIA_PLATFORM/StaticSiteDeployHooks/Controllers/DeployHookController.php` | Support `digest_list`, accept redirect_to |
| `views/media_platform/static_site_deploy_hooks/create.blade.php` | Dynamic triggerable type selector |
| `views/media_platform/static_site_deploy_hooks/edit.blade.php` | Dynamic triggerable type selector |
| `views/media_platform/static_site_deploy_hooks/index.blade.php` | Adapt "Owner" column for both types |
| `views/media_platform/static_site_deploy_hooks/show.blade.php` | Adapt labels/links for both types |
| `views/media_platform/static_site_deploy_hooks/delete_confirm.blade.php` | Use display name helper |
| `views/media_platform/static_site_deploy_hooks/trigger_confirm.blade.php` | Use display name helper |
| `views/media_platform/static_site_deploy_hooks/trigger_result.blade.php` | Use display name helper |
| `MEDIA_PLATFORM/StaticSiteDeployHooks/Models/DeployHook.php` | Add display name accessor |
| `views/media_platform/digest/content_sources/lists/wizard-step3.blade.php` | Add static site radio |
| `views/media_platform/digest/content_sources/lists/wizard-step6.blade.php` | Handle static site in summary |
| `views/media_platform/digest/content_sources/lists/wizard-step7.blade.php` | Deploy hook CTA for static site |
| `views/media_platform/digest/content_sources/lists/edit.blade.php` | Add static site fields |
| `views/media_platform/digest/content_sources/lists/show.blade.php` | Add static site sections |
| `MEDIA_PLATFORM/Digest/ContentSources/Lists/Controllers/ListWizardController.php` | New wizard steps, update validation |
| `MEDIA_PLATFORM/Digest/Routes/lists.php` | Add new wizard routes |
| `MEDIA_PLATFORM/API/v1/Routes/api.php` | Add digest endpoint |
| `MEDIA_PLATFORM/API/v1/Dashboard/DashboardController.php` | Add pending fetch warning |
| `MEDIA_PLATFORM/API/v1/README.md` | Document new endpoint |
| `ARCHITECTURE.md` | Document new table, strategies, morph alias |
| `CONVENTIONS.md` | Document morph alias, strategy pattern |
| `database/seeders/.../DeployHooksSeeder.php` | Seed digest list hooks |
| `tests/Feature/.../PublishDigestTest.php` | Refactor helpers, add GROUP 8 |
| `tests/Feature/.../ListCrudTest.php` | Add static site update tests |
| `tests/Feature/.../WizardFlowTest.php` | Add static site wizard path |

---

## Recommended Implementation Order

1. **Phase 1** (Data Layer) — foundation everything else depends on
2. **Phase 2** (Delivery Strategies) — core refactor, must work before anything else
3. **Phase 7.1–7.3, 7.7–7.8** (Update existing tests, strategy tests) — verify the refactor didn't break anything
4. **Phase 3** (API Endpoint) — needed by the static site
5. **Phase 7.5** (API tests) — verify the endpoint
6. **Phase 4** (Deploy Hooks) — support digest_list triggerable
7. **Phase 7.6** (Deploy hook tests) — verify
8. **Phase 5** (List Wizard & Edit) — UI for creating static site lists
9. **Phase 7.2–7.4** (List CRUD tests, wizard tests, strategy integration tests)
10. **Phase 6** (Dashboard observability)
11. **Phase 8** (Seeders)
12. **Phase 9** (Documentation)