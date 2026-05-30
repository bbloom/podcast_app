# HANDOFF.md

## Project
Laravel/PHP podcasting application. Repo: **https://github.com/bbloom/podcast_app**

The GitHub MCP connector is not working. The repo has been made **temporarily public**, so fetch files directly via raw GitHub URLs:
`https://raw.githubusercontent.com/bbloom/podcast_app/master/path/to/file.php`

The project knowledge attached to this project contains the key `.md` reference files: `ARCHITECTURE.md`, `CONVENTIONS.md`, `php-laravel.md`, and `Claude.md`. Read those for full project context before doing anything.

---

## Current State

### Guest Email Plumbing — Phases 0–5 complete

| Phase | Status |
|---|---|
| Phase 0 — Housekeeping | ✅ Complete |
| Phase 1 — Postmark + DNS Infrastructure | ✅ Complete |
| Phase 2 — Package Scaffolding | ✅ Complete |
| Phase 3 — Outbound: Send a Guest Email + Store Message-ID | ✅ Complete |
| Phase 4 — Inbound: Webhook + Postmark Parsing | ✅ Complete |
| Phase 5 — Bounce Handling | ✅ Complete |
| Phase 6 — Proof of Life: Full End-to-End in Production | ⏳ Pending — see below |
| Phase 7 — Clean Up Temporary Scaffolding | ⏳ Pending Phase 6 |

### Phase 6 — Current Situation
- Postmark account is **approved** — sending restriction lifted
- Outbound email **confirmed working** in production (email received, `guest_emails` row written with correct `message_id`)
- Inbound and bounce **not yet live-tested** — do this next
- Cloudflare bypass rule for `/webhooks/postmark/*` is in place (Access Controls)

### Post-Implementation Clean-Up
- ✅ Item 1 — Future-development placeholders removed
- ✅ Item 2 — PodcastStudio rename sweep complete
- ⏳ Item 3 — `composer update` — intentionally deferred until after Phase 7

### Other
- `lasallesoftware.ca` added as a Postmark sender domain — DNS verified, Digest emails now route through Postmark. SES env vars can be removed from the other Laravel app at any time.

### Test suite
**1,614 tests passing, 3,746 assertions.** No known failures.

---

## What To Do Next

### Phase 6 — Live End-to-End Proof

1. Navigate to `/dev/guest-email-test` in production
2. Send an email to yourself (using a guest record whose `email_address` you control)
3. Reply to it from your inbox
4. Hit `/dev/guest-emails` — confirm an inbound row appeared with correct `in_reply_to` matching the outbound `message_id`
5. Confirm `body_stripped` contains only your reply text, not the quoted original
6. Trigger a test bounce: send to `bounce@simulator.postmarkapp.com` — confirm the guest record gets `email_bounced = true`
7. Check the Postmark dashboard activity log — all sends and receives visible

### Phase 7 — Clean Up Temporary Scaffolding (after Phase 6)
- Remove `TemporarySendTestEmailController` and its routes
- Remove the temporary views (`send_test_email.blade.php`)
- Remove the `/dev/guest-emails` debug route from `guest_email_dev.php`
- Remove the `require` from `routes/web.php`
- Confirm test suite still passes

### Post-Implementation Clean-Up Item 3 (last of all)
```bash
composer update
php artisan test
```

---

## Reference Documents

| Document | Path | What it covers |
|---|---|---|
| `EMAIL_PLUMBING.md` | `INBOUND_EMAIL/EMAIL_PLUMBING.md` | Mechanics: provider, parsing, correlation, package architecture |
| `EMAIL_PLUMBING_IMPLEMENTATION_PLAN.md` | `INBOUND_EMAIL/EMAIL_PLUMBING_IMPLEMENTATION_PLAN.md` | Phased build plan — current status |
| `INBOUND_EMAILS_FEATURES.md` | `INBOUND_EMAIL/INBOUND_EMAILS_FEATURES.md` | Deferred feature decisions for the features phase |
| `POSTMARK_SETUP.md` | `INBOUND_EMAIL/POSTMARK_SETUP.md` | Postmark + DNS setup (complete — reference only) |

---

## Key Files Added This Work

```
INBOUND_EMAIL/
    Contracts/InboundEmailProviderInterface.php
    ValueObjects/ParsedInboundEmail.php
    ValueObjects/BounceNotification.php

INBOUND_EMAIL_PROVIDERS/
    Postmark/
        PostmarkProvider.php
        Controllers/
            PostmarkInboundWebhookController.php
            PostmarkBounceWebhookController.php
        Routes/
            postmark_webhooks.php

MEDIA_PLATFORM/Podcasts/Guests/
    Enums/GuestEmailDirection.php
    Models/GuestEmail.php
    Mail/GuestEmailMailable.php
    Services/GuestEmailService.php
    Controllers/Dev/TemporarySendTestEmailController.php
    Routes/guest_email_dev.php

database/migrations/media_platform/podcasts/
    2026_05_28_000001_create_guest_emails_table.php
    2026_05_28_000002_add_bounce_columns_to_podcast_guests_table.php

views/media_platform/podcasts/guests/
    mail/guest_email.blade.php
    dev/send_test_email.blade.php

database/factories/Media_platform/Podcasts/Guests/
    GuestEmailFactory.php

tests/Feature/INBOUND_EMAIL_PROVIDERS/Postmark/
    PostmarkInboundWebhookControllerTest.php
    PostmarkBounceWebhookControllerTest.php

tests/Feature/MEDIA_PLATFORM/Podcasts/Guests/
    TemporarySendTestEmailControllerTest.php

INBOUND_EMAIL/
    INBOUND_EMAILS_FEATURES.md
    EMAIL_PLUMBING_IMPLEMENTATION_PLAN.md (updated)
```

### Modified files
- `MEDIA_PLATFORM/Podcasts/Guests/Models/PodcastGuest.php` — added `email_bounced`, `email_bounced_at` to `$fillable`, `$casts`, and `emails()` relationship
- `bootstrap/app.php` — added `/webhooks/postmark/inbound` and `/webhooks/postmark/bounce` to CSRF exceptions
- `config/services.php` — added `postmark_webhook` credentials block
- `routes/web.php` — added `require` for `postmark_webhooks.php` and `guest_email_dev.php`