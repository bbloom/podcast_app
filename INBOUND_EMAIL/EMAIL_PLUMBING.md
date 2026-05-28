# EMAIL_PLUMBING.md

**Path:** `INBOUND_EMAIL/EMAIL_PLUMBING.md`

Technical mechanics reference for inbound and outbound email infrastructure. Covers plumbing only — value-added features are in a separate document.

---

## Provider

**Postmark** handles both sending and receiving for `bobbloominterviews.com`.

- Account: postmarkapp.com — on the Pro plan
- Server name: `podcast-app`
- Domain `bobbloominterviews.com` is verified (DKIM, SPF, Return-Path all green)
- DNS setup documented in `INBOUND_EMAIL/POSTMARK_SETUP.md`

---

## Sending Mechanics

- Laravel Mailable → Postmark API → guest's inbox
- Guest sees a normal email from `@bobbloominterviews.com`
- The app is completely invisible to the guest
- Laravel's built-in Postmark mail driver handles outbound (`MAIL_MAILER=postmark`)

### DNS required on `bobbloominterviews.com`
- **SPF** (TXT record) — authorises Postmark to send on behalf of the domain
- **DKIM** (CNAME) — cryptographic signature proving email wasn't tampered with
- **Return-Path** (CNAME) — improves bounce handling and deliverability
- **DMARC** (TXT record) — policy for what receivers do if SPF/DKIM fails
- All records are live and verified. See `POSTMARK_SETUP.md` for exact values.

---

## Inbound Mechanics

For guest replies to arrive in the app (not just in a personal inbox):

- **MX record** on `bobbloominterviews.com` — points to `inbound.postmarkapp.com`
- Postmark receives the reply, fully parses it, and HTTP POSTs structured JSON to a webhook in the app
- The app's webhook endpoint receives the pre-parsed payload and stores/routes it

### What Postmark delivers to the webhook

Unlike raw email processing, Postmark delivers a clean, pre-parsed JSON payload. Key fields:

```json
{
  "From": "guest@example.com",
  "Subject": "Re: Interview Questions",
  "TextBody": "Full plain text including quoted history",
  "StrippedTextReply": "Only what the guest actually wrote",
  "HtmlBody": "...",
  "Headers": [
    { "Name": "Message-ID", "Value": "<abc@mail.example.com>" },
    { "Name": "In-Reply-To", "Value": "<xyz@bobbloominterviews.com>" }
  ]
}
```

`StrippedTextReply` is what the app stores as the reply body — Postmark handles quoting-convention differences across Gmail, Outlook, Apple Mail, and Hey.com. No PHP parsing packages needed.

### Inbound pipeline (conceptual)
1. Guest replies to an email from `@bobbloominterviews.com`
2. MX record routes the reply to Postmark
3. Postmark parses the email and HTTP POSTs JSON to the app's inbound webhook
4. `PostmarkProvider` verifies the webhook credentials and extracts fields
5. App stores result, correlates to guest and episode via `Message-ID` / `In-Reply-To`

---

## Correlating Inbound Replies

### Guest identification
- Match the incoming `From` email address against `podcast_guests.email_address`
- No tricks needed — the guest's email is already in the database

### Thread/episode correlation — Message-ID approach
- Every sent email has a `Message-ID` header assigned by the sending server
- When a guest replies, their email client sets `In-Reply-To` to that `Message-ID`
- Store the `Message-ID` of every sent email in the database, along with guest ID and episode context
- On inbound: read `In-Reply-To`, look up the matching sent email record, derive guest and episode from that record
- **No IDs exposed in email headers** — correlation is done entirely via the standard `Message-ID` / `In-Reply-To` mechanism, which is invisible to the guest and universal across all email clients

### Custom X-headers (supplementary)
- Custom headers (e.g. `X-Episode-Number: 47`) can be added to outgoing emails
- Completely invisible to the guest
- Preserved in replies by most email clients, surfaced by Postmark's inbound parser
- Useful as a fallback or supplement, but **do not put database ID numbers in headers**
- Episode number is a safe, stable, human-meaningful value if a header is needed

---

## Episode Association & The Planning→Published Handoff

Emails will be associated with one of:
- A `podcast_episodes_planning` record (during planning phase)
- A `podcast_episodes_published` record (after handoff)
- Neither (general guest correspondence not tied to a specific episode)

### The handoff problem
`PrepareForPublishingWizard` Step 3 hard-deletes the planning record after creating the published record. Emails attached to the planning record must survive this deletion and be re-associated with the new published record.

- Guests and links are migrated at handoff (already handled in the wizard)
- Emails will need the same treatment — migrate at Step 3, same as guests and links
- Episode number is likely the stable identifier that exists in both tables and survives the transition

### Open questions
- Does `podcast_episodes_planning` have an episode number column? Does `podcast_episodes_published`?
- Is the episode number the right stable key to carry through the handoff, or is there another?

---

## Guest Experience Principles

- Guest receives and replies to a completely normal-looking email
- No unusual reply-to addresses
- No "reply above this line" markers in the body
- No subject line decoration
- No registration, no portal, no friction of any kind
- The professionalism shows through the *content and timing* of the emails, not through any visible infrastructure

### Hey.com note
The first email sent from `@bobbloominterviews.com` to a Hey.com inbox will land in The Screener. The recipient must approve it once — after that, subsequent emails arrive normally. This is expected Hey behaviour, not a deliverability problem.

---

## Two Internal Packages

### `INBOUND_EMAIL/` — provider-agnostic core
PSR-4: `"InboundEmail\\": "INBOUND_EMAIL/"`

Contains:
- **Contracts** — `InboundEmailProviderInterface` (the shape every provider must implement)
- **Value objects** — `ParsedInboundEmail`, `BounceNotification` (used by both core and providers)
- **Bounce handling logic** — acts on a normalised `BounceNotification`; flags guest record; never knows which provider it came from

**No dependency on `INBOUND_EMAIL_PROVIDERS`.** The core defines the contract; it never imports an implementation.

### `INBOUND_EMAIL_PROVIDERS/` — provider-specific adapters
PSR-4: `"InboundEmailProviders\\": "INBOUND_EMAIL_PROVIDERS/"`

Depends on `INBOUND_EMAIL` (imports its interface and value objects). Contains one concrete adapter per provider, each implementing `InboundEmailProviderInterface`.

Each adapter is responsible for:
- Receiving the raw HTTP POST from the provider
- Verifying the provider's webhook credentials
- Unpacking the provider's payload format
- Determining message type (inbound email / bounce notification)
- Returning a normalised value object (`ParsedInboundEmail` or `BounceNotification`) to the core

Current: `PostmarkProvider`. Future: other providers. Switching provider = swapping one adapter. Core logic untouched.

### Dependency direction
`INBOUND_EMAIL_PROVIDERS` → depends on → `INBOUND_EMAIL` → depends on → nothing (domain-level)

### Note on outbound
Laravel already abstracts outbound sending via its mail drivers (`config/mail.php`). Both packages are for **inbound only**.

### No AWS SDK dependency
Unlike the original SES design, Postmark requires no AWS SDK. The one Composer dependency is `wildbit/postmark-php` (the official Postmark PHP SDK), used for sending only. Inbound emails and bounce notifications arrive as plain HTTP POST requests from Postmark.

---

## Webhook Authentication

Postmark sends webhook credentials via HTTP Basic Authentication embedded in the webhook URL. The app verifies these on every inbound POST.

Credentials are stored in `.env`:
```
POSTMARK_WEBHOOK_USER=pmhook
POSTMARK_WEBHOOK_PASSWORD=<password>
```

Webhook URLs registered in Postmark include the credentials:
```
https://pmhook:<password>@yourdomain.com/webhooks/postmark/inbound
https://pmhook:<password>@yourdomain.com/webhooks/postmark/bounce
```

The `PostmarkProvider` extracts and compares the `Authorization` header on every request. Requests that fail verification are rejected with HTTP 403.

---

## Remaining To-Dos

### Data model
- Store both `body_stripped` (StrippedTextReply) AND `body_full` (TextBody) — if Postmark's stripping misfires on an unusual client, the original is there to fall back on
- Data model for `guest_emails` table (columns, indexes, FK to `podcast_guests`)
- How to handle attachments in replies (if at all)

### Outbound threading headers
- When replying to a guest from the app, set `In-Reply-To` and `References` headers pointing to their last `Message-ID`
- This keeps the conversation grouped as a single thread in the guest's email client
- Without it, each outbound email appears as a new disconnected message on their end

### Deferred (features phase)
- General vs episode-scoped email distinction in the UI
- Milestone email templates (content, triggers)
- Guest status ENUM

---

## Completed

- ✅ Phase 0 housekeeping — Gemini renamed, Pest tests converted to PHPUnit, PSR-4 entries added
- ✅ Postmark account and domain setup — `bobbloominterviews.com` verified, DNS live, webhooks configured
- ✅ Full test suite passing — 1,586 tests, 3,694 assertions