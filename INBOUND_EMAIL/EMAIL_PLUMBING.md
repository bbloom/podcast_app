# EMAIL_PLUMBING.md

**Path:** `INBOUND_EMAIL/EMAIL_PLUMBING.md`

Technical mechanics reference for inbound and outbound email infrastructure. Covers plumbing only — value-added features are in a separate document.

---

## Sending Mechanics

- Laravel Mailable → mail provider → guest's inbox
- Guest sees a normal email from `@bobbloominterviews.com`
- The app is completely invisible to the guest

### DNS required on `bobbloominterviews.com`
- **SPF** (TXT record) — authorises the mail provider to send on behalf of the domain
- **DKIM** (TXT or CNAME) — cryptographic signature proving email wasn't tampered with
- **DMARC** (TXT record) — policy for what receivers do if SPF/DKIM fails
- These are for *outbound*. Already familiar territory from SES setup on other domains.

---

## Inbound Mechanics

For guest replies to arrive in the app (not just in a personal inbox):

- **MX record** on `bobbloominterviews.com` — points to the mail provider's inbound servers
- Provider receives the reply, parses it, and HTTP POSTs structured JSON to a webhook in the app
- The app's webhook endpoint receives the payload and stores/routes it

### Inbound approach — DIY on SES
- SES receives the email and delivers it via SNS to a webhook endpoint in the Laravel app
- The app parses the raw email itself using PHP packages — no Postmark, no third-party parsing service
- This keeps full control, zero extra subscription, and is customisable to exact needs

### Parsing stack — two separate concerns

**1. MIME parsing** — cracking the raw RFC 2822 email into fields
- Package: `zbateson/mail-mime-parser`
- Pure PHP, no PECL extension required, well-maintained
- Extracts: From, To, Subject, plain text body, HTML body, headers, attachments

**2. Stripping quoted reply** — extracting only what the guest actually wrote
- Package: `willdurand/EmailReplyParser` — PHP port of GitHub's open-sourced reply parser
- Handles quoting conventions of Gmail, Outlook, Apple Mail, and others
- Returns only the guest's new content, discarding the `> On Monday Bob wrote...` chain
- This is a separate problem from MIME parsing — two packages, each with one responsibility

### Inbound pipeline (conceptual)
1. SES receives email → delivers via SNS to webhook in Laravel app
2. Webhook receives raw email content
3. `mail-mime-parser` parses into discrete fields
4. `EmailReplyParser` strips quoted history from plain text body
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

---

## Two Internal Packages

### `INBOUND_EMAIL/` — provider-agnostic core
PSR-4: `"InboundEmail\\": "INBOUND_EMAIL/"`

Contains:
- **Contracts** — `InboundEmailProviderInterface` (the shape every provider must implement)
- **Value objects** — `RawEmail`, `BounceNotification`, etc. (used by both core and providers)
- **MIME parsing** — `zbateson/mail-mime-parser`
- **Reply stripping** — `willdurand/EmailReplyParser`
- **Bounce handling logic** — acts on a normalised `BounceNotification`; flags guest record; never knows which provider it came from

**No dependency on `INBOUND_EMAIL_PROVIDERS`.** The core defines the contract; it never imports an implementation.

### `INBOUND_EMAIL_PROVIDERS/` — provider-specific adapters
PSR-4: `"InboundEmailProviders\\": "INBOUND_EMAIL_PROVIDERS/"`

Depends on `INBOUND_EMAIL` (imports its interface and value objects). Contains one concrete adapter per provider, each implementing `InboundEmailProviderInterface`.

Each adapter is responsible for:
- Receiving the raw HTTP POST from the provider
- Verifying the provider's cryptographic signature
- Unpacking the provider's envelope format
- Determining message type (inbound email / bounce notification / subscription confirmation)
- Returning a normalised value object (`RawEmail` or `BounceNotification`) to the core

Current: `SesProvider`. Future: `CloudflareProvider`, etc. Switching provider = swapping one adapter. Core logic untouched.

### Dependency direction
`INBOUND_EMAIL_PROVIDERS` → depends on → `INBOUND_EMAIL` → depends on → nothing (domain-level)

### Note on outbound
Laravel already abstracts outbound sending via its mail drivers (`config/mail.php`). Both packages are for **inbound only**.

### Note on the AWS SDK
`aws/aws-sdk-php` handles outbound API calls to SES. Inbound emails and bounce notifications arrive as plain SNS HTTP POST requests. The SDK is not involved in receiving them — `SesProvider` handles that directly.

---

## To-Do — Do First

### Convert lingering Pest tests to PHPUnit
Started with Pest, hit problems, switched to PHPUnit class-based tests — but Pest tests still exist in the codebase. These should be identified and converted before building new features, so the test suite is consistent and nothing is silently not running.

Steps:
1. Find all Pest-style test files (`it()`, `test()`, `describe()` — no class wrapper)
2. Convert each to a PHPUnit class-based test following the pattern in `YoutubeChannelWizardControllerTest`
3. Confirm full suite runs clean
Do this before any new feature work so the full test suite runs clean against consistent conventions.

Steps:
1. `git mv Gemini/ GEMINI/`
2. Update `composer.json` PSR-4: `"Gemini\\Laravel\\": "GEMINI/"`
3. `composer dump-autoload`
4. Run full test suite — no code changes should be needed (namespace string `Gemini\Laravel\` is unchanged)

---

### Bounce handling
- When SES cannot deliver an email, it sends a bounce notification via SNS
- The app must receive and handle bounce notifications — at minimum, flag `podcast_guests.email_address` as invalid on the guest record
- Without this, a bad email address fails silently

### Webhook security
- SNS signs its POST requests cryptographically
- The inbound webhook handler must verify the SNS signature before trusting the payload
- Not complex, but must be present

### Outbound threading headers
- When replying to a guest from the app, set `In-Reply-To` and `References` headers pointing to their last `Message-ID`
- This keeps the conversation grouped as a single thread in the guest's email client
- Without it, each outbound email appears as a new disconnected message on their end

- **Data model**: store both the stripped reply body AND the full raw body — if stripping misfires on an unusual email client, the original is there to fall back on. Decide column names and types when designing the table.
- Data model for stored emails (table structure, fields, indexes)
- How to handle attachments in replies (if at all)
- General vs episode-scoped email distinction in the UI
- Milestone email templates (content, triggers) — deferred until mechanics are settled
### SNS dead-letter queue
- If the webhook is down or throws an exception and SNS exhausts its retry attempts, the notification is lost
- An SQS dead-letter queue on the SNS topic catches failed deliveries for manual recovery
- Not deferred — build it as part of the initial SES/SNS setup