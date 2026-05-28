# INBOUND_EMAILS_FEATURES.md

**Path:** `INBOUND_EMAIL/INBOUND_EMAILS_FEATURES.md`

Deferred feature decisions and open questions for the guest email system. To be addressed after the plumbing is proven end-to-end (Phase 6).

---

## 1. Cold Inbound Emails from Unknown Guests

**Scenario:** A person emails `guests@bobbloominterviews.com` directly, without replying to an outbound email from the app. Their email address does not exist in `podcast_guests`.

**Current behaviour:** The webhook fires, the pipeline cannot match the sender to a guest record, and the email is silently discarded.

**Open questions:**
- Should cold inbound emails from unrecognised addresses be logged to a separate table or fallback store?
- Should they trigger a notification (email or dashboard alert) so nothing slips through?
- Should there be a UI for reviewing and actioning unmatched inbound messages?

---

## 2. Cold Inbound Emails from Known Guests

**Scenario:** A known guest (exists in `podcast_guests`) emails `guests@bobbloominterviews.com` directly, without replying to an outbound email from the app.

**Current behaviour:** Stored in `guest_emails` with `in_reply_to = null`. No thread correlation. Email is captured and associated with the correct guest.

**Open questions:**
- How should the UI surface unthreaded inbound messages — separate from conversation threads?
- Should the host be notified when a known guest initiates contact?