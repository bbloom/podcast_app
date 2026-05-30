# EMAIL_PLUMBING_IMPLEMENTATION_PLAN.md

**Path:** `INBOUND_EMAIL/EMAIL_PLUMBING_IMPLEMENTATION_PLAN.md`

Phased implementation plan for the inbound/outbound email plumbing described in `EMAIL_PLUMBING.md`.

**Provider:** Postmark (replaces SES/SNS/SQS). All live testing is done directly in production — no ngrok, no local webhook simulation.

Features (milestone emails, guest conversation UI, guest status) are out of scope here — this plan gets the pipes working end-to-end, proven in production, before any feature work begins.

---

## Phase 0 — Housekeeping (Do First) ✅ COMPLETE

These are pre-existing to-dos from `HANDOFF.md`. Complete before any new code.

### 0a — Rename Gemini directory ✅
```bash
git mv Gemini/ GEMINI/
```
- Updated `composer.json` PSR-4: `"Gemini\\Laravel\\": "GEMINI/"`
- `composer dump-autoload`
- Full test suite clean

### 0b — Convert Pest tests to PHPUnit ✅
- All Pest-style test files converted to PHPUnit class-based tests
- Full suite clean

### 0c — Register new package namespaces in composer.json ✅
Added to `autoload.psr-4`:
```json
"InboundEmail\\": "INBOUND_EMAIL/",
"InboundEmailProviders\\": "INBOUND_EMAIL_PROVIDERS/"
```
- `composer dump-autoload` clean
- No autoload errors

---

## Phase 1 — Postmark + DNS Infrastructure ✅ COMPLETE

`POSTMARK_SETUP.md` completed in full — all nine parts, verification checklist green.

**Outcomes:**
- `bobbloominterviews.com` verified in Postmark (DKIM, SPF, Return-Path all green)
- MX record live — inbound mail routes to Postmark
- Inbound and bounce webhook URLs configured in Postmark dashboard
- API token and webhook authentication credentials in `.env`

---

## Phase 2 — Package Scaffolding + Composer Dependency ✅ COMPLETE

Create the directory structure and install the one required package. No logic yet — just the skeleton.

### Simplified package footprint

Because Postmark delivers pre-parsed JSON (including `StrippedTextReply`), the two PHP parsing packages from the original SES plan are **not needed**:
- ~~`zbateson/mail-mime-parser`~~ — Postmark parses MIME for you
- ~~`willdurand/EmailReplyParser`~~ — Postmark strips quoted replies for you

One Composer dependency only:
```bash
composer require wildbit/postmark-php
```
The official Postmark PHP SDK. Used for sending via the Postmark API.

### Directory structure

```
INBOUND_EMAIL/
    Contracts/
        InboundEmailProviderInterface.php
    ValueObjects/
        ParsedInboundEmail.php
        BounceNotification.php

INBOUND_EMAIL_PROVIDERS/
    Postmark/
        PostmarkProvider.php
```

### Contracts

**`InboundEmailProviderInterface`**:
```php
namespace InboundEmail\Contracts;

interface InboundEmailProviderInterface
{
    public function handle(Request $request): ParsedInboundEmail|BounceNotification|null;
}
```

Returns:
- `ParsedInboundEmail` — a verified, parsed inbound message ready to store
- `BounceNotification` — a delivery failure notification
- `null` — non-actionable request (invalid signature, unrecognised type)

### Value objects

**`ParsedInboundEmail`** — immutable, carries what Postmark already parsed:
```
- fromAddress: string
- subject: string
- strippedReplyBody: string    (StrippedTextReply — what the guest wrote)
- fullTextBody: string         (Postmark's TextBody)
- messageId: string            (from Headers array — RFC 2822 Message-ID)
- inReplyTo: string|null       (from Headers array — In-Reply-To)
- receivedAt: Carbon
```

**`BounceNotification`** — immutable:
```
- bouncedAddress: string
- bounceType: string           ('HardBounce', 'SoftBounce', 'SpamComplaint', etc.)
- description: string
- occurredAt: Carbon
```

Follow the existing value object pattern (named static factories, private constructor — see `DeployHookTriggerResult`).

### Deliverable
`composer dump-autoload` clean. All classes exist and are autoloaded. No logic yet.

---

## Phase 3 — Outbound: Send a Guest Email + Store Message-ID ✅ COMPLETE

### Composer packages — install first
```bash
composer require symfony/postmark-mailer symfony/http-client
```
These are NOT bundled with Laravel. They provide the Symfony Postmark transport that Laravel's built-in Postmark mail driver requires. `wildbit/postmark-php` (installed in Phase 2) is separate — it handles the Postmark API SDK for sending only.

### Add `.env` values
```
POSTMARK_API_KEY=<rename from POSTMARK_API — must match services.php which reads env('POSTMARK_API_KEY')>
MAIL_MAILER=postmark
MAIL_FROM_ADDRESS=<your sending address @bobbloominterviews.com>
MAIL_FROM_NAME="<your display name>"
```

`config/services.php` is correct as-is (`'key' => env('POSTMARK_API_KEY')`) — this is what the Laravel docs specify. Do not change it. The `.env` variable name must match.

`config/mail.php` is correct as-is — the `postmark` mailer entry already exists.

### Migration — `guest_emails` table
Path: `database/migrations/media_platform/podcasts/`
Register in `AppServiceProvider::loadMigrationsFrom()`.

Required columns:
- `id`
- `podcast_guest_id` (FK → `podcast_guests`)
- `direction` — enum: `outbound` / `inbound`
- `subject`
- `body_stripped` — the reply only (inbound: `StrippedTextReply`; outbound: full body)
- `body_full` — full body before stripping (store both — fallback if stripping misfires)
- `message_id` — RFC 2822 `Message-ID` header value
- `in_reply_to` — `In-Reply-To` header value (inbound only)
- `sent_at` / `received_at`
- `timestamps`

### Mailable — `GuestEmail`
Path: `MEDIA_PLATFORM/Podcasts/Guests/Mail/GuestEmail.php`

- Sends via Postmark
- Sets `In-Reply-To` and `References` headers when replying (keeps thread intact in guest's email client)
- After sending, captures the `Message-ID` from the sent message and stores the outbound record in `guest_emails`

### Temporary route + controller (proof-of-life scaffolding)
An auth-protected page for production testing — not visible to guests:

- Select a guest from a dropdown
- Subject and body fields
- Submit sends via `GuestEmail` mailable and writes the outbound row to `guest_emails`

Name it obviously temporary: `TemporarySendTestEmailController`
Route: `GET|POST /dev/guest-email-test` — auth middleware only

### Deliverable
Send a real email to a real address from the production app. A row appears in `guest_emails` with the correct `message_id`. Confirm it arrives in the recipient's inbox and passes DKIM/SPF (check via mail-tester.com or inspect headers).

---

## Phase 4 — Inbound: Webhook + Postmark Parsing ✅ COMPLETE

### Cloudflare risk
Postmark POSTs to the webhook endpoints from its own servers. Cloudflare's WAF or firewall rules may block these requests — the same class of problem that affected the Auphonic webhook. If the Phase 6 end-to-end test shows emails arriving at Postmark but never hitting the app, the first thing to check is Cloudflare. Resolution: create a bypass or allow rule for `/webhooks/postmark/*`, or whitelist Postmark's published IP ranges in Cloudflare.

### Webhook endpoint
Route: `POST /webhooks/postmark/inbound`
- No auth middleware — Postmark posts from outside
- CSRF exempt — add to `VerifyCsrfToken::$except`
- Controller: `INBOUND_EMAIL_PROVIDERS/Postmark/Controllers/PostmarkInboundWebhookController`

### `PostmarkProvider`
Implements `InboundEmailProviderInterface`.

Responsibilities:
1. **Verify webhook token** — Postmark sends the authentication token in a request header. Compare against `POSTMARK_WEBHOOK_TOKEN` from `.env`. Reject with HTTP 403 if it does not match. Simple string comparison — no certificate fetching, no cryptographic ceremony.
2. **Parse the JSON payload** — Postmark delivers structured, pre-parsed JSON. Extract fields directly.
3. **Return `ParsedInboundEmail`** — populated from Postmark's payload fields.

Key payload fields to extract:
- `From` → `fromAddress`
- `Subject` → `subject`
- `StrippedTextReply` → `strippedReplyBody`
- `TextBody` → `fullTextBody`
- `Headers` array → find `Message-ID` and `In-Reply-To` by name

### Processing pipeline (in `INBOUND_EMAIL/` — provider-agnostic)
1. `PostmarkProvider::handle()` verifies and returns `ParsedInboundEmail`
2. Match `fromAddress` against `podcast_guests.email_address` — derive `podcast_guest_id`
3. Match `inReplyTo` against `guest_emails.message_id` (outbound records) — confirm correlation
4. Store inbound row in `guest_emails`

### Deliverable
Reply to the test email sent in Phase 3 from a real email client. Confirm the reply appears in `guest_emails` as an inbound row with correct `podcast_guest_id`, `in_reply_to`, and `body_stripped` containing only the reply text.

---

## Phase 5 — Bounce Handling ✅ COMPLETE

### Bounce webhook endpoint
Route: `POST /webhooks/postmark/bounce`
- Same CSRF exemption and token verification as the inbound webhook
- Controller: `INBOUND_EMAIL_PROVIDERS/Postmark/Controllers/PostmarkBounceWebhookController`

### `BounceNotification` handling (in `INBOUND_EMAIL/` — provider-agnostic)
- `PostmarkProvider` parses the bounce payload and returns a `BounceNotification`
- For `HardBounce` (permanent): flag `podcast_guests` record — `email_bounced: true`, `email_bounced_at: timestamp`
- For `SoftBounce` / `SpamComplaint`: log only — do not flag the guest

Postmark bounce payload key fields: `Type`, `Email`, `Description`, `BouncedAt`

---

## Phase 6 — Proof of Life: Full End-to-End in Production

⚠️ **Postmark account approved. Outbound confirmed working in production. Inbound and bounce live tests still pending.**

Work through this sequence with real email addresses:

1. ✅ Use the temporary send form (Phase 3) to send an email to yourself
2. ✅ Approve it through Hey's Screener (first time only)
3. ⏳ Reply from your inbox
4. ⏳ Confirm the reply appears in `guest_emails` as an inbound row
5. ⏳ Confirm `podcast_guest_id` and `in_reply_to` are correctly populated
6. ⏳ Confirm `body_stripped` contains only your reply — no quoted original
7. ⏳ Trigger a test bounce — send to Postmark's test address `bounce@simulator.postmarkapp.com` — confirm the guest record gets flagged
8. ⏳ Check the Postmark dashboard activity log — confirm all sends and receives are visible there

Once all eight steps pass, the plumbing is proven.

---

## Phase 7 — Clean Up Temporary Scaffolding ✅ COMPLETE

Dev routes gated behind `if (! app()->isProduction())` in `web.php` — invisible in production, available in local and test environments. Full removal is deferred until Phase 6 proof-of-life is complete.

**To re-enable for Phase 6:** temporarily remove the `isProduction()` gate in `web.php`. Re-apply and remove the scaffolding entirely once Phase 6 passes.

---

## Post-Implementation Clean-Up

To be completed after the plumbing is proven end-to-end. Do not begin until Phase 7 is done and the test suite is green.

1. **Remove future-development placeholders** — find and delete any folders, dashboard items, or UI elements that were placeholders for deferred features
2. **PodcastStudio rename sweep** — search for `PodcastStudio`, `podcast_studio`, `podcast-studio`, `Podcast Studio` and any other permutations throughout the codebase (comments, URLs, config values) and update to current naming
3. **Composer dependency audit** — update all package version constraints in `composer.json` to latest, then run `composer update` and confirm the test suite passes clean

---

## What Comes Next (Out of Scope Here)

- Guest status ENUM and milestone email triggers
- Guest conversation UI (thread view per guest)
- Guest dashboard / action-oriented overview
- Tadpole (pre-commitment guest ideas) tracking
- Reply-from-app compose UI
- Episode association for interview show