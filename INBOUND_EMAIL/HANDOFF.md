# HANDOFF.md

## Project
Laravel/PHP podcasting application. Repo: **https://github.com/bbloom/podcast_app**

The GitHub MCP connector is not working. The repo has been made **temporarily public**, so fetch files directly via raw GitHub URLs:
`https://raw.githubusercontent.com/bbloom/podcast_app/master/path/to/file.php`

The project knowledge attached to this project contains the key `.md` reference files: `ARCHITECTURE.md`, `CONVENTIONS.md`, `php-laravel.md`, and `Claude.md`. Read those for full project context before doing anything.

---

## What This Session Accomplished

We built the guest email feature from zero to ready for coding in two sessions:

**Session 1** — Mechanics design. Decided on provider, architecture, package structure, correlation strategy. Documented in `EMAIL_PLUMBING.md`.

**Session 2** — This session. Completed:
1. Switched from AWS SES/SNS/SQS to **Postmark** (simpler, no AWS ceremony)
2. Completed full **Postmark + DNS infrastructure** setup for `bobbloominterviews.com`
3. Completed all **Phase 0 housekeeping**:
   - `git mv Gemini/ GEMINI/` + composer.json PSR-4 updated
   - All Pest-style tests converted to PHPUnit class-based tests
   - `INBOUND_EMAIL/` and `INBOUND_EMAIL_PROVIDERS/` PSR-4 entries added to composer.json
4. **Full test suite passing** — 1,586 tests, 3,694 assertions

---

## Current State

### Infrastructure — done
- `bobbloominterviews.com` verified in Postmark (DKIM, SPF, Return-Path green)
- MX record live — inbound mail routes to Postmark
- Inbound webhook URL configured in Postmark: `https://pmhook:<password>@yourdomain.com/webhooks/postmark/inbound`
- Bounce webhook URL configured in Postmark: `https://pmhook:<password>@yourdomain.com/webhooks/postmark/bounce`
- Webhook credentials in `.env`: `POSTMARK_WEBHOOK_USER`, `POSTMARK_WEBHOOK_PASSWORD`

### `.env` values needed for coding
```
POSTMARK_TOKEN=<server api token>
POSTMARK_WEBHOOK_USER=pmhook
POSTMARK_WEBHOOK_PASSWORD=<password>
MAIL_MAILER=postmark
```

### Test suite
1,586 tests passing. No known failures.

**Important gotcha discovered during this session:**
In PHPUnit, calling `Http::fake()` multiple times in the same test method **stacks rules** rather than replacing them — the first matching rule always wins. For multi-run tests (e.g. simulating 5 sequential processor runs), use `Http::sequence()` within a single `Http::fake()` call:
```php
Http::fake([
    'feed.url.com/feed.xml' => Http::sequence()
        ->push($feed1)
        ->push($feed2)
        ->push($feed3),
]);
```

---

## Reference Documents

All key decisions are settled and documented. Do not re-litigate.

| Document | Path | What it covers |
|---|---|---|
| `EMAIL_PLUMBING.md` | `INBOUND_EMAIL/EMAIL_PLUMBING.md` | Mechanics: provider, parsing, correlation, package architecture |
| `POSTMARK_SETUP.md` | `INBOUND_EMAIL/POSTMARK_SETUP.md` | Postmark + DNS setup (complete — for reference only) |
| `EMAIL_PLUMBING_IMPLEMENTATION_PLAN.md` | `INBOUND_EMAIL/EMAIL_PLUMBING_IMPLEMENTATION_PLAN.md` | Phased build plan — Phases 0 and 1 complete |

---

## What To Build Next — Phase 2

Phase 0 (housekeeping) and Phase 1 (Postmark/DNS) are complete. Start on **Phase 2: Package Scaffolding**.

### Phase 2 — Package Scaffolding + Composer Dependency

Install the one Composer dependency:
```bash
composer require wildbit/postmark-php
```

Create the directory and file skeleton (no logic yet — just autoloadable classes):

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

**`InboundEmailProviderInterface`**:
```php
namespace InboundEmail\Contracts;

interface InboundEmailProviderInterface
{
    public function handle(Request $request): ParsedInboundEmail|BounceNotification|null;
}
```

**`ParsedInboundEmail`** value object fields:
```
fromAddress: string
subject: string
strippedReplyBody: string    (Postmark's StrippedTextReply)
fullTextBody: string         (Postmark's TextBody)
messageId: string            (from Headers array)
inReplyTo: string|null       (from Headers array)
receivedAt: Carbon
```

**`BounceNotification`** value object fields:
```
bouncedAddress: string
bounceType: string           ('HardBounce', 'SoftBounce', 'SpamComplaint', etc.)
description: string
occurredAt: Carbon
```

Follow the existing value object pattern — named static factories, private constructor. See `DeployHookTriggerResult` as the reference example.

**Deliverable:** `composer dump-autoload` clean, all classes autoloaded, no logic yet.

### Phase 3 — Outbound: Send a Guest Email + Store Message-ID

After Phase 2 is clean, move to Phase 3. Full details in `EMAIL_PLUMBING_IMPLEMENTATION_PLAN.md`.

Summary:
- Migration: `guest_emails` table
- Mailable: `GuestEmail` (sends via Postmark, captures `Message-ID` after send)
- Temporary route + controller: `TemporarySendTestEmailController` at `GET|POST /dev/guest-email-test`
- Deliverable: real email sent from production app, row in `guest_emails` with correct `message_id`

---

## What Is NOT In Scope For This Coding Window

The plumbing plan covers Phases 2–7 only. The following are explicitly deferred to a features conversation after the plumbing is proven:

- Guest status ENUM
- Milestone email triggers
- Guest conversation UI (thread view)
- Guest dashboard / action-oriented overview
- Tadpole (pre-commitment guest ideas)
- Reply-from-app compose UI
- Episode association for the interview show