# Publish on Website

The final step of the post-production pipeline. Makes the episode visible on
the website and marks it as published.

---

## Episode Status

| On entry | On completion |
|---|---|
| `ready_to_publish` | `published` |

---

## What Happens on Confirmation

- `website_enabled` is set to `true` — the episode becomes visible on the website
- `status` advances to `published`
- `website_publish_on` is **not changed** — it was set at episode creation time and controls when the website displays the episode

---

## Architecture

### Controllers

**`IndexController`**
Lists all episodes belonging to the authenticated user with status
`ready_to_publish`, ordered by scheduled date ascending so the most imminent
episode appears first.

**`ShowController`**
Displays the confirmation page for a specific episode. Shows episode details
and a `target="_blank"` link to the episode show page so the user can do a
final review of all fields before confirming. Status guard: `ready_to_publish`
only.

**`PublishController`**
Handles the confirmation POST. Sets `website_enabled = true`, advances status
to `published`, and redirects to the index with a success flash message.
`website_publish_on` is left unchanged.

---

### Routes

Defined in `MEDIA_PLATFORM/Podcasts/PostProduction/Routes/publish_on_website.php`,
required by `routes/web.php`. Accessible from the **Pipeline** section of
the Post-Production Dashboard.

| Method | URI | Name |
|---|---|---|
| GET | `/post-production/publish-on-website` | `post_production.publish_on_website.index` |
| GET | `/post-production/publish-on-website/{episode}` | `post_production.publish_on_website.show` |
| POST | `/post-production/publish-on-website/{episode}` | `post_production.publish_on_website.publish` |

---

### Notes

- No session state is used — this is a simple two-page confirm flow with no wizard steps
- No external service calls — this is a database-only operation
- The episode's website content, excerpt, meta description, featured image, and attribution are all set during pre-production and are not modified here