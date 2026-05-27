# POSTMARK_SETUP.md

**Path:** `INBOUND_EMAIL/POSTMARK_SETUP.md`

Step-by-step Postmark and DNS configuration for inbound and outbound email on `bobbloominterviews.com`.

Complete this entire document before touching any application code.

---

## Part 1 — Create Account and Server

1. Sign up at **postmarkapp.com** — start on the free trial (100 emails, all features including inbound)
2. After confirming your email, you land in the Postmark dashboard
3. A default server is created automatically — rename it: click the server name → **Settings** → rename to `podcast-app`
4. Note the server's **API token** (under the API Tokens tab) — this goes in your `.env` later

---

## Part 2 — Add the Sender Domain

1. In the Postmark dashboard: **Sender Signatures** → **Add Domain**
2. Enter `bobbloominterviews.com`
3. Postmark generates:
   - A **DKIM** CNAME record (two values: host and value)
   - A **Return-Path** CNAME record
4. Copy both records — you will add them in Part 3

---

## Part 3 — DNS Records on `bobbloominterviews.com`

Add all of the following in your DNS provider's control panel. The domain is completely clean, so everything is a fresh addition.

### DKIM (provided by Postmark in Part 2)
| Type | Host | Value |
|------|------|-------|
| CNAME | `20xxxxxx._domainkey` | `20xxxxxx.dkim.mxe10.net` |

*Paste the exact values from the Postmark dashboard — the token prefix will be a date-based string.*

### Return-Path (improves bounce handling and deliverability)
| Type | Host | Value |
|------|------|-------|
| CNAME | `pm-bounces` | `pm.mtasv.net` |

### SPF (authorises Postmark to send on behalf of the domain)
| Type | Host | Value |
|------|------|-------|
| TXT | `@` (root) | `v=spf1 include:spf.mtasv.net ~all` |

### DMARC
| Type | Host | Value |
|------|------|-------|
| TXT | `_dmarc` | `v=DMARC1; p=quarantine; rua=mailto:dmarc-reports@bobbloominterviews.com` |

> Start with `p=quarantine`. Tighten to `p=reject` after confirming clean delivery. The `rua` address receives aggregate reports — it does not need to be actively monitored.

### MX — Inbound (routes inbound email to Postmark)
| Type | Host | Priority | Value |
|------|------|----------|-------|
| MX | `@` (root) | `10` | `inbound.postmarkapp.com` |

> This is the only MX record on the domain. Since the domain is clean, there is nothing to replace.

---

## Part 4 — Verify the Domain in Postmark

1. Back in Postmark → **Sender Signatures** → click `bobbloominterviews.com`
2. Click **Verify DNS Records** — Postmark checks SPF, DKIM, and Return-Path
3. All three should show green within a few minutes of DNS propagating
4. DNS can take up to 48 hours, but is usually much faster — check back periodically

---

## Part 5 — Configure Inbound Processing

1. In the Postmark dashboard → **your server** → **Settings** → **Inbound**
2. Set the **inbound webhook URL** to your production webhook endpoint:
   `https://yourdomain.com/webhooks/postmark/inbound`
3. Note the **inbound email address** Postmark assigns — something like `abc123@inbound.postmarkapp.com`
   - This is Postmark's receiving address. You do not use this directly — the MX record in Part 3 routes `@bobbloominterviews.com` addresses to Postmark, which forwards to this webhook.
4. Optionally set a **webhook authentication token** — a shared secret Postmark sends in every POST request so your app can verify the request is genuinely from Postmark. Recommended. Store it in `.env`.

> The webhook URL must be live and returning HTTP 200 before you can fully test inbound. This is handled in Phase 4 of the implementation plan. You can configure the URL now and return to test it then.

---

## Part 6 — What Postmark Sends to the Webhook

Unlike raw SES, Postmark delivers a clean, pre-parsed JSON payload to your webhook. No MIME parsing required in the app. Key fields:

```json
{
  "From": "guest@example.com",
  "To": "anything@bobbloominterviews.com",
  "Subject": "Re: Interview Questions",
  "TextBody": "Full plain text including quoted history",
  "StrippedTextReply": "Only what the guest actually wrote — quoted history removed",
  "HtmlBody": "...",
  "Headers": [
    { "Name": "Message-ID", "Value": "<abc@mail.example.com>" },
    { "Name": "In-Reply-To", "Value": "<xyz@bobbloominterviews.com>" }
  ],
  "MessageID": "postmark-assigned-id"
}
```

`StrippedTextReply` is what the app stores as the reply body — Postmark handles the quoting-convention differences across Gmail, Outlook, Apple Mail, and Hey.com. No `willdurand/EmailReplyParser` needed.

---

## Part 7 — Bounce Webhooks

Postmark can notify your app when a bounce occurs, via a separate webhook.

1. **your server** → **Settings** → **Webhooks** → **Add webhook**
2. URL: `https://yourdomain.com/webhooks/postmark/bounce`
3. Check **Bounce** and **Spam Complaint** event types
4. Save

Your app will receive a POST when an email cannot be delivered — used to flag the guest's email address as invalid. Handled in Phase 5 of the implementation plan.

---

## Part 8 — Upgrade to Pro

When you are ready to go live beyond testing:

1. Postmark dashboard → **Billing** → upgrade to **Pro** ($16.50/mo)
2. Pro is required for inbound email processing on a paid plan
3. The free trial includes all features — complete the full end-to-end test before upgrading

---

## Part 9 — Verification Checklist

Work through this before starting Phase 2 of the implementation plan:

- [ ] Postmark account created, server renamed to `podcast-app`
- [ ] API token noted (goes in `.env` as `POSTMARK_TOKEN`)
- [ ] Domain `bobbloominterviews.com` added as sender signature
- [ ] DNS records added: DKIM, Return-Path, SPF, DMARC, MX
- [ ] Postmark shows domain as verified (green on all checks)
- [ ] Inbound webhook URL configured in Postmark (can be a placeholder until Phase 4)
- [ ] Bounce webhook URL configured in Postmark (can be a placeholder until Phase 5)
- [ ] Webhook authentication token set and noted (goes in `.env` as `POSTMARK_WEBHOOK_TOKEN`)

---

## Hey.com Note

Your own Hey inbox will put the first email from `@bobbloominterviews.com` into The Screener. Approve it once — after that, subsequent emails arrive normally. This is expected behaviour, not a deliverability problem.