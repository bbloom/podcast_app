## Handoff Note

### Project
Laravel/PHP podcasting application. Repo: **https://github.com/bbloom/podcast_app**

The GitHub MCP connector is not working. However, the repo has been made **temporarily public**, so you can fetch files directly via URLs. Expect to be given direct URLs to specific files when needed — raw GitHub URLs will work. Example pattern:
`https://raw.githubusercontent.com/bbloom/podcast_app/master/path/to/file.php`

The project knowledge attached to this project contains the key `.md` reference files: `ARCHITECTURE.md`, `CONVENTIONS.md`, `php-laravel.md`, and `Claude.md`. Read those for full project context before doing anything.

---

### What We Have Been Working On

A **guest email feature** for `MEDIA_PLATFORM/Podcasts/Guests/`. The existing `PodcastGuest` model already has `email_address` as a required field. The goal is to build a holistic, professional guest communication system — inbound and outbound email, entirely within the app, with guests experiencing nothing but normal email.

---

### What Is Already Decided — Mechanics

We completed a thorough mechanics identification session. Everything is documented in:

**`INBOUND_EMAIL/EMAIL_PLUMBING.md`** — pasted below.

Do not re-litigate the mechanics. They are settled. That document is the reference.

Key decisions at a glance:
- **SES** for both sending and receiving (already set up and working)
- **`bobbloominterviews.com`** as the email domain
- **DIY inbound parsing** — no Postmark. Two PHP packages: `zbateson/mail-mime-parser` (MIME) and `willdurand/EmailReplyParser` (reply stripping)
- **Two new internal packages**: `INBOUND_EMAIL/` (provider-agnostic core) and `INBOUND_EMAIL_PROVIDERS/` (provider-specific adapters, starting with `SesProvider`)
- **Correlation** via standard `Message-ID` / `In-Reply-To` headers — no DB IDs in email headers
- **Testing in production** — no ngrok, no local webhook testing
- **Dead-letter queue (SQS)** — part of the initial build, not deferred

---

### Housekeeping To-Dos (Do Before Building)
1. `git mv Gemini/ GEMINI/` + update `composer.json` PSR-4 + `composer dump-autoload` + run tests
2. Hunt down and convert all remaining Pest-style tests to PHPUnit class-based tests
3. Add PSR-4 entries to `composer.json` for `INBOUND_EMAIL/` and `INBOUND_EMAIL_PROVIDERS/`

---

### What Is Next

The mechanics are the appetizer. The **value-added features** are the main course — the things that make this effort actually worthwhile as a podcasting tool. That is what this new conversation is for.

We are starting a **second reference document** for value-added features. The working title is `INBOUND_EMAIL/GUEST_EMAIL_FEATURES.md` (or similar — decide together).

The approach: draw out the features through conversation before writing a single line of code or spec. The developer has features "lurking in their head" that have not yet been articulated. Surface those first.

---

### `INBOUND_EMAIL/EMAIL_PLUMBING.md`
*(paste the full contents of the saved document here)*