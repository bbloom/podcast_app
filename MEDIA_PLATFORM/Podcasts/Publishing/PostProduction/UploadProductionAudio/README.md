# UploadProductionAudio

## Overview

Handles uploading the final processed MP3 to AWS S3 and Cloudflare R2 after Auphonic post-production is complete.

This feature is the third step in the post-production pipeline, following **AuphonicProcessing** (which downloads the processed MP3 to the app server) and preceding **RSS Feed Generation**.

---

## Location

```
MEDIA_PLATFORM/PodcastStudio/PostProduction/UploadProductionAudio/
├── Controllers/
│   ├── IndexController.php
│   ├── ShowController.php
│   ├── ManualUploadController.php
│   ├── UploadToStorageController.php
│   └── CleanUpController.php
└── Routes/
    └── upload_production_audio.php
```

**Views:** `views/media_platform/podcast_studio/post_production/upload_production_audio/`

**Tests:** `tests/Feature/MEDIA_PLATFORM/PodcastStudio/PostProduction/UploadProductionAudio/`

---

## Episode Status

This feature operates on episodes with status `ready_to_upload_production_file`.

On successful upload to S3 and R2, the status advances to `ready_to_generate_rss_feed`.

---

## The Two Paths

The production MP3 can arrive on the app server via two routes:

### Happy Path — Auphonic Download
The AuphonicProcessing clean-up step downloads the MP3 from Auphonic automatically and saves it to `storage_path('podcasts/{filename}')`. The episode status is then advanced to `ready_to_upload_production_file`. When the user arrives at this feature, the file is already on the server.

### Manual Upload Path — Auphonic Download Failed
If the Auphonic API download fails, the user must download the MP3 manually from the Auphonic web console and upload it from their local machine. `ManualUploadController` accepts the file via a standard browser form upload and saves it to `storage_path('podcasts/{filename}')` — the same location as the happy path.

Once the file is on the server, both paths converge and the rest of the pipeline is identical.

---

## Filename Convention

The production MP3 filename is always derived from the episode's `raw_input_audio_filename` field — the base name (stem) is preserved and the extension is changed from `.wav` to `.mp3`.

For example:
- `raw_input_audio_filename` = `my-episode-recording.wav`
- Production MP3 filename = `my-episode-recording.mp3`

On manual upload, the uploaded file's stem is validated against the expected stem. If it does not match, the upload is rejected with a message telling the user exactly which filename is expected.

---

## Controllers

### `IndexController`
Lists all episodes belonging to the authenticated user with status `ready_to_upload_production_file`, ordered by scheduled date ascending.

### `ShowController`
The decision page. Checks whether the production MP3 is already on the app server and presents the user with two options:

- **Yes — file is on the server** → proceed to `UploadToStorageController`
- **No — file is on my local machine** → proceed to `ManualUploadController`

Admin users additionally see a listing of all files currently in `storage_path('podcasts/')` — filename, size, and modified timestamp — as a convenience to help identify whether their file is present. This listing is not scoped per user (files on the server have no user association), so the Yes/No decision is always made manually.

### `ManualUploadController`
Accepts an MP3 file uploaded from the user's local machine via a standard browser form (`enctype="multipart/form-data"`). Validates that the uploaded filename stem matches the expected stem derived from `raw_input_audio_filename`. On success, saves the file to `storage_path('podcasts/')` and redirects to `UploadToStorageController`.

> **Note:** PHP's `upload_max_filesize`, `post_max_size`, `memory_limit`, and `max_execution_time` must be configured generously in `php.ini` to handle files up to 500 MB. The Health Check tool monitors these values and will raise Tier 3 alerts if they fall below the required thresholds.

### `UploadToStorageController`
The core of the pipeline. Performs the following steps:

1. Confirms the MP3 file exists in `storage_path('podcasts/')` — hard failure if missing.
2. Extracts duration (`itunes_duration`) and filesize (`itunes_enclosure_length`) using `james-heinrich/getid3`.
3. Uploads the file to the show's **AWS S3** production audio bucket — hard failure if this fails.
4. Uploads the file to the show's **Cloudflare R2** production audio bucket — soft failure (logged, pipeline continues).
5. Persists duration, filesize, and the S3 enclosure URL to the episode record.
6. Advances the episode status to `ready_to_generate_rss_feed`.

### `CleanUpController`
Deletes the production MP3 from local server storage (`storage_path('podcasts/')`) after it has been safely uploaded to S3 and R2. A dedicated confirmation page is shown before anything is deleted — consistent with the no-modals convention.

The file deletion is a soft failure — if the file is already gone, a log entry is written but the redirect still succeeds. Clean-up is available once the episode status is `ready_to_generate_rss_feed`.

---

## Metadata Extraction

Duration and filesize are extracted using `james-heinrich/getid3` (pure PHP — no FFmpeg dependency).

| Field | Source | Format |
|---|---|---|
| `itunes_duration` | `getID3::playtime_seconds` | `mm:ss` under 1 hour (e.g. `09:45`), `h:mm:ss` over 1 hour (e.g. `1:04:35`) |
| `itunes_enclosure_length` | `getID3::filesize` | Raw byte count as a string |
| `itunes_enclosure_url` | Constructed from S3 bucket + key | `https://{bucket}.s3.{region}.amazonaws.com/podcasts/{filename}` |

---

## Cloud Storage

Bucket names and R2 endpoints are resolved by:
- `CloudStorage/S3_production_audio.php` — AWS S3 bucket per show slug
- `CloudStorage/R2_production_audio.php` — Cloudflare R2 endpoint per show slug

Credentials are read from `config/podcast_post_production.php`, which reads from `.env`.

The R2 client uses the AWS S3-compatible API (`Aws\S3\S3Client`) with `region: auto` and the Cloudflare account endpoint.

---

## Security

- All controllers enforce ownership: `$episode->user_id !== auth()->id()` redirects with an error flash rather than returning a raw 403.
- All controllers enforce the expected episode status before proceeding.
- All routes require `auth` middleware.

---

## Dependencies

- `james-heinrich/getid3` — MP3 metadata extraction
- `aws/aws-sdk-php` — S3 and R2 uploads (already required by the project)