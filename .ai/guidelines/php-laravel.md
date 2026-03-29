# PHP & Laravel AI Guidelines

## General Code Instructions
- Every method should have a brief docblock comment summarising what it does.
- Every migration table should have a `->comment()` on the table and on each column, explaining its purpose.
- Don't add docblock comments when defining variables unless instructed, like `/** @var \App\Models\User $currentUser */`.
- For new features, always generate Pest automated tests.
- Never hard-code AI provider logic directly — summarisation must go through an abstracted service/interface so providers (Gemini, OpenAI, etc.) are swappable via config.

---

## PHP Instructions
- Use `match` operator over `switch` whenever possible.
- Generate Enums always in `app/Enums/`, never in the root `app/` folder unless instructed otherwise.
- When a database column only accepts a fixed set of values (e.g. a status column), create a PHP Enum for those values. In the migration, set the Enum's value as the column default. In the Model, cast that column to the Enum type so Laravel automatically converts it. Then use the Enum everywhere — in routes, Blade files, tests, seeds, and configs — instead of hardcoding raw strings.
- Don't create temporary variables like `$currentUser = auth()->user()` if that variable is only used once.

---

## Laravel Instructions

### Controllers
- Slim controllers — put larger logic in Service classes.
- Multi-method RESTful controllers use `Route::resource()->only([])`.
- Don't create controllers with a single method that only returns `view()` — use `Route::view()` directly instead.
- Inject a Service into the method directly if used in only one method; inject via constructor if used in multiple methods.

### Eloquent & Models
- Never use `::query()` before `create()`. Use `User::create()` not `User::query()->create()`.
- Don't use `whereKey()` or `whereKeyNot()` — use explicit fields like `->where('id', '!=', $id)`.
- Register Eloquent Observers on the Model using PHP Attributes, not in `AppServiceProvider`. Example: `#[ObservedBy([UserObserver::class])]`.
- When adding columns in a migration, always update the model's `$fillable` array.
- All models must have explicit `$table` names.
- Ownership checks: `abort_if($model->user_id !== auth()->id(), 403)`.
- Sensitive fields use Laravel's `encrypted` cast.

### Morph Aliases
- Morph aliases are registered in `AppServiceProvider`: `youtube_channel`, `text_based_rss_feed`, `podcast`.
- Never rely on fully-qualified class names in polymorphic columns.

### Migrations
- **Never** chain multiple migration-creating commands with `&&` or `;` — they may get identical timestamps. Run each command separately.
- Migration paths are registered explicitly in `AppServiceProvider` — Laravel does not scan subfolders automatically.
- Every table must have a comment using `$table->comment('...')` describing its purpose.
- Every column must have a `->comment('...')` describing what it stores.

### Helpers & Directives
- Use Laravel helpers over facade imports: `auth()->id()` not `Auth::id()`, `redirect()->route()` not `Redirect::route()`.
- Never use Str::slug() in production code — always use the custom makeSlug() helper (preserves dots); or, for podcasts, there is a custom slug helper. Str::slug() is acceptable in test factories only.
- In Blade, always use `@selected()` and `@checked()` directives instead of inline ternaries.
- Always use `@session()` directive instead of `@if(session())` for flash messages.

### Misc
- No Livewire Volt — only Livewire class components.

---

## Routes
- Each feature has its own dedicated routes file (e.g. `routes/youtube.php`, `routes/podcasts.php`)
- Feature route files are loaded via `require` in `routes/web.php`
- Routes are always defined explicitly — never use `->group()`, `Route::resource()`, or other convenience grouping methods
- Every route is declared individually with its own `Route::get()`, `Route::post()`, etc.
- Views: `/app/views`
- Features: `app/app/FeatureName/Controllers|Models|Services|Requests`
- Tests: `tests/Feature/FeatureName/`
- Enums: `app/Enums/`
- Migrations: `database/migrations/language_models/` and `database/migrations/lists_and_feeds/`

---

## UI & Blade
- Purple / `purple-700` accent theme throughout.
- No modals — use dedicated confirmation pages for destructive actions.
- No bulk delete on index pages.
- Wizards for multi-step create flows.

---

## Testing Instructions

### Before Writing Tests
1. Check database schema — understand which columns have defaults, which are nullable, and foreign key relationship names.
2. Verify relationship names — read the model file to confirm exact relationship method names, return types, and related models.
3. Test realistic states — don't assume empty model means all nulls; check for defaults. Don't assume `user_id` maps to a `user()` relationship.
4. When testing form submissions that redirect back with errors, assert old input is preserved using `assertSessionHasOldInput()`.

### Coverage Goals
- Every controller method must have a corresponding test.
- Tests must cover the happy path, validation errors, forbidden access (403), and not found (404).
- The test suite serves as a regression safety net — if Laravel, PHP, or any dependency, updates and something breaks, the tests should catch it. Run the full test suite after every `composer update`.

### General
- Always use PHPUnit class-based tests, following the pattern in YoutubeChannelWizardControllerTest.
- Use `use RefreshDatabase;` as a trait on the test class.
- Test class names mirror the controller they test, suffixed with `Test`.
- Test method names are prefixed with `test_` and describe the behaviour being tested.
- CSRF is bypassed via `defined('PHPUNIT_COMPOSER_INSTALL')` in `bootstrap/app.php`.