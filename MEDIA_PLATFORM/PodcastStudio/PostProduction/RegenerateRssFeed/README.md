# Regenerate RSS Feed

Rebuilds and republishes the RSS feed for any podcast show on demand.

This is a **show-level maintenance operation** — it is independent of any individual
episode's pipeline status. Use it whenever the live feed needs to be refreshed
outside the normal episode pipeline, for example:

- A future-dated episode's publish date has arrived and you want to include it
- A field was corrected on an existing episode and the feed needs to reflect the change
- Any other reason to rebuild the feed without running a new episode through the full pipeline

No episode status changes occur at any point in this flow.

---

## Architecture

### Controllers

**`IndexController`**
Lists all podcast shows belonging to the authenticated user, ordered
alphabetically. Any show can be regenerated at any time — there is no status
gate.

**`StageController`**
Generates the RSS XML for the selected show via `RssFeedGeneratorService`,
writes it to local storage, and uploads it to the `podcast-work-in-progress`
S3 staging bucket under the `rss/` folder. Renders the staging page with the
public URL and links to external validators. Stores the staging URL, filename,
S3 key, and show ID in the session for `PromoteController`.

If the generator returns no eligible episodes (none with `rss_feed_enabled = true`
and a past `itunes_pubdate`), redirects to the index with a clear error.

**`PromoteController`**
Reads the XML from local storage, uploads to the live S3 RSS bucket (hard
failure), uploads to the live Cloudflare R2 RSS bucket (soft failure — logged,
pipeline continues), deletes the staging S3 file, deletes the local file, and
clears all session keys.

---

### Services Used

All generation logic is handled by the shared `RssFeedGeneratorService` from
the `GenerateRssFeed` feature. No validation service is used — fields are
assumed correct at this point. External validation is done manually via the
staging URL before promoting to live.

---

### Storage

| Location | Purpose |
|---|---|
| `storage/app/podcasts/rss/{filename}` | Local XML file (temporary) |
| `podcast-work-in-progress` S3 bucket, `rss/` folder | Staging — for external validation |
| Per-show S3 RSS bucket, `rss/` folder | Live — polled by podcast directories |
| Per-show Cloudflare R2 RSS bucket | Live CDN — zero egress cost |

---

### Session Keys

| Key | Set by | Cleared by |
|---|---|---|
| `regenerate_rss_feed.staging_url` | StageController | PromoteController |
| `regenerate_rss_feed.rss_filename` | StageController | PromoteController |
| `regenerate_rss_feed.rss_s3_key` | StageController | PromoteController |
| `regenerate_rss_feed.show_id` | StageController | PromoteController |

---

### Routes

Defined in `MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/regenerate_rss_feed.php`,
required by `routes/web.php`. Accessible from the **Maintenance** section of
the Post-Production Dashboard.

| Method | URI | Name |
|---|---|---|
| GET | `/post-production/regenerate-rss-feed` | `post_production.regenerate_rss_feed.index` |
| GET | `/post-production/regenerate-rss-feed/{show}/stage` | `post_production.regenerate_rss_feed.stage` |
| POST | `/post-production/regenerate-rss-feed/{show}/promote` | `post_production.regenerate_rss_feed.promote` |

---

### External Validators

The staging page links to:

- [Cast Feed Validator](https://www.castfeedvalidator.com) — primary validator for Apple Podcasts
- [Podbase](https://podba.se/validate/) — validates against Apple, Spotify, and Google
- [Podcastpage Feed Validator](https://podcastpage.io/tool/podcast-feed-validator) — useful secondary check

The staging URL is publicly accessible via the `podcast-work-in-progress` S3
bucket. Podcast directories do not know this URL — it is safe to validate here
before promoting.