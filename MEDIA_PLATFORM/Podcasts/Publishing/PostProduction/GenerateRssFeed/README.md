# Generate RSS Feed

Generates the RSS XML feed for a podcast show, validates it, uploads it to a
staging bucket for external validation, then promotes it to the live S3 and
Cloudflare R2 buckets.

This is Step 4 of the post-production pipeline, following `UploadProductionAudio`.

---

## Episode Status

| On entry | On completion |
|---|---|
| `ready_to_generate_rss_feed` | `ready_to_publish` |

---

## Architecture

### Services

**`RssFeedGeneratorService`**
The sole owner of XML generation logic. Accepts a `PodcastShow` model and
returns a `GenerateRssFeedResult` value object. Queries all episodes where
`rss_feed_enabled = true` and `itunes_pubdate` is in the past, ordered
most-recent-first. Builds the feed using PHP's native `DOMDocument` — no
external dependencies. Ported and fixed from the `ls-podcastrssfeedbackend-pkg`
package. Key fixes applied during port:
- `boolToYesNo()` now returns lowercase `yes`/`no` (Apple spec requires lowercase)
- `itunes:image` item-level tag now uses `href` attribute (not text content)
- Unused namespace declarations removed (`cc`, `creativeCommons`, `dc`, `rdf`, `slash`, `media`)

**`RssFeedValidatorService`**
Runs pre-generation validation before the XML is built. Existence checks only —
is the field populated? No format validation or business rule re-implementation.
Returns a `RssFeedValidatorResult` value object containing failures, warnings,
and an `r2DownloadFailed` flag. The R2 download check verifies `itunes_enclosure_length`
and `itunes_duration` against the actual production MP3 via getID3. Skipped if
the session flag `wizard.generate_rss_feed.enclosure_manually_verified_{id}` is set.

**`GenerateRssFeedResult`**
Immutable value object returned by the generator. Callers check `$result->ok()`
before calling `$result->xml()`. Calling `xml()` on a failed result throws a
`LogicException`.

**`RssFeedValidatorResult`**
Immutable value object returned by the validator. Contains `failures()`,
`warnings()`, `r2DownloadFailed()`, and `ok()` (true only when failures is empty).

---

### Wizard Steps

**Step 1 — Review Episode**
Displays the episode details with a link to the episode show page
(`target="_blank"`) for a final manual review. Shows `itunes_enclosure_length`
and `itunes_duration` prominently. No status change.

**Step 2 — Validate**
Runs `RssFeedValidatorService`. If all checks pass, redirects directly to
Step 3 without rendering. If failures exist, displays them with a link to the
episode edit form. If R2 download fails, surfaces inline input fields for
manual confirmation of `itunes_enclosure_length` and `itunes_duration` — submitting
that form sets a session flag and re-runs validation. Pubdate in the past
triggers a warning (non-blocking).

**Step 3 — Generate & Stage**
Calls `RssFeedGeneratorService::generate()`. Writes the XML to
`storage/app/podcasts/rss/{filename}`. Uploads to the
`podcast-work-in-progress` S3 bucket under the `rss/` folder (already
public — no ACL set). Stores the staging URL in the wizard session for Step 4.

**Step 4 — External Validation**
Displays the staging URL for copy/paste into external validators:
- [Cast Feed Validator](https://www.castfeedvalidator.com) — primary
- [Podbase](https://podba.se/validate/) — validates against Apple, Spotify, Google
- [Podcastpage Feed Validator](https://podcastpage.io/tool/podcast-feed-validator)

Two buttons: **Validation Passed** → Step 5, **Something Failed** → episode
show page (wizard session cleared, episode retains `ready_to_generate_rss_feed`
status for re-entry).

**Step 5 — Promote to Live**
Reads the XML from local storage. Uploads to the live S3 RSS bucket
(hard failure). Uploads to the live Cloudflare R2 RSS bucket (soft failure —
logged, pipeline continues). Deletes the staging file from S3. Deletes the
local file. Advances episode status to `ready_to_publish`. Clears all wizard
session keys.

---

### Artisan Command

```bash
# Interactive — lists shows and prompts for selection
php artisan podcast:generate-rss

# Direct by show ID
php artisan podcast:generate-rss --show=1

# Direct by show slug
php artisan podcast:generate-rss --show=bob-bloom-show
```

The command is a thin wrapper around `RssFeedGeneratorService`. It writes the
generated XML to `storage/app/podcasts/rss/{filename}` and outputs the path.
No status changes, no uploads — generation only.

---

### Storage

| Location | Purpose |
|---|---|
| `storage/app/podcasts/rss/{filename}` | Local XML file (temporary) |
| `podcast-work-in-progress` S3 bucket, `rss/` folder | Staging — for external validation |
| Per-show S3 RSS bucket, `rss/` folder | Live — polled by podcast directories |
| Per-show Cloudflare R2 RSS bucket | Live CDN — zero egress cost |

Bucket and endpoint mappings are defined in:
- `CloudStorage/S3_rss.php` — live S3 buckets + staging bucket methods
- `CloudStorage/R2_rss.php` — live R2 endpoints

---

### Session Keys

| Key | Set by | Cleared by |
|---|---|---|
| `wizard.generate_rss_feed.podcast_episode_id` | Step1Controller | Step4Controller (failed), Step5Controller |
| `wizard.generate_rss_feed.staging_url` | Step3Controller | Step4Controller (failed), Step5Controller |
| `wizard.generate_rss_feed.rss_filename` | Step3Controller | Step4Controller (failed), Step5Controller |
| `wizard.generate_rss_feed.rss_s3_key` | Step3Controller | Step4Controller (failed), Step5Controller |
| `wizard.generate_rss_feed.enclosure_manually_verified_{id}` | Step2Controller | Step4Controller (failed), Step5Controller |

---

### Routes

Defined in `MEDIA_PLATFORM/Podcasts/PostProduction/Routes/generate_rss_feed.php`,
required by `routes/web.php`.

| Method | URI | Name |
|---|---|---|
| GET | `/post-production/generate-rss-feed` | `post_production.generate_rss_feed.index` |
| GET | `/post-production/generate-rss-feed/{episode}/step1` | `post_production.generate_rss_feed.step1` |
| POST | `/post-production/generate-rss-feed/{episode}/step1` | `post_production.generate_rss_feed.step1.store` |
| GET | `/post-production/generate-rss-feed/{episode}/step2` | `post_production.generate_rss_feed.step2` |
| POST | `/post-production/generate-rss-feed/{episode}/step2` | `post_production.generate_rss_feed.step2.store` |
| GET | `/post-production/generate-rss-feed/{episode}/step3` | `post_production.generate_rss_feed.step3` |
| GET | `/post-production/generate-rss-feed/{episode}/step4` | `post_production.generate_rss_feed.step4` |
| POST | `/post-production/generate-rss-feed/{episode}/step4/failed` | `post_production.generate_rss_feed.step4.failed` |
| POST | `/post-production/generate-rss-feed/{episode}/step5` | `post_production.generate_rss_feed.step5` |

---

### XML Feed

The generated XML follows the RSS 2.0 spec with iTunes, content, googleplay,
and Podcast Index namespace extensions. Validated against
[Cast Feed Validator](https://www.castfeedvalidator.com) with 32 live episodes
before release.

Namespaces declared:

| Prefix | URI | Purpose |
|---|---|---|
| `atom` | `http://www.w3.org/2005/Atom` | `atom:link` self-reference |
| `content` | `http://purl.org/rss/1.0/modules/content/` | `content:encoded` tags |
| `itunes` | `http://www.itunes.com/dtds/podcast-1.0.dtd` | Apple Podcasts tags |
| `googleplay` | `http://www.google.com/schemas/play-podcasts/1.0` | Spotify/legacy compatibility |
| `podcast` | `http://podcastindex.org/namespace/1.0` | Podcast Index (future: transcripts, chapters) |