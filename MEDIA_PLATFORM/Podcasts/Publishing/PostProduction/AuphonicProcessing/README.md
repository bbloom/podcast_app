# AuphonicProcessing

Handles the Auphonic audio post-production pipeline step. A raw WAV recording is submitted to Auphonic for processing, monitored via webhook, reviewed, and then cleaned up once the user is satisfied with the result.

---

## Pipeline Position

```
Upload Recording to S3
        ↓
Submit to Auphonic   ← this feature
        ↓
Upload Production File to S3 & R2
```

---

## Episode Statuses (this feature)

| Status | Meaning |
|---|---|
| `ready_for_auphonic` | Recording uploaded to S3, ready to submit |
| `processing_at_auphonic` | Production submitted, waiting for webhook |
| `auphonic_complete` | Webhook received, MP3 ready for review |
| `ready_to_upload_production_file` | Clean-up complete, pipeline advances |

---

## Controllers

### `IndexController`
Lists all episodes in `ready_for_auphonic` status. Entry point for the pipeline step.

### `SubmitController`
**`show()`** — Displays the episode detail page. Before rendering, performs an S3 file check by listing objects in the episode's work-in-progress folder and comparing against `raw_input_audio_filename`. Four possible outcomes:

| S3 Status | Meaning | UI |
|---|---|---|
| `match` | Expected file confirmed | Submit button shown |
| `mismatch` | Wrong file in folder | Warning + Re-upload button + AWS console link |
| `multiple` | More than one file found | Warning + AWS console link |
| `empty` | No files found | Warning + Re-upload button + AWS console link |

**`submit()`** — POSTs to Auphonic API (create + start in one request). Stores the returned production UUID on the episode and advances status to `processing_at_auphonic`.

### `ReplaceRecordingController`
Resets episode status to `ready_to_upload_recording` and redirects to the upload flow when the wrong file was uploaded. Does **not** delete any S3 files — the user cleans up manually via the AWS console link.

### `WebhookController`
Receives the HTTP POST callback from Auphonic. Advances status to `auphonic_complete` on success (status code 3). Leaves the episode in `processing_at_auphonic` on error (status code 2) for manual re-submission. Always returns HTTP 200 to prevent Auphonic retries. Excluded from CSRF verification in `bootstrap/app.php`.

### `WebhookStatusController`
Lightweight JSON endpoint polled by Alpine.js on the processing page every 5 seconds. Returns `{ status, complete }`. When `complete` is true, the front-end shows the "Ready!" button.

### `CompleteController`
Displays the "Auphonic Complete" screen. Offers three options: review in Auphonic console, proceed to clean-up, or re-submit.

### `ResubmitController`
**`confirm()`** — Confirmation page before the destructive re-submit. Cancel link is status-aware: returns to the complete page if `auphonic_complete`, or the processing page if `processing_at_auphonic`.

**`resubmit()`** — Deletes the existing Auphonic production, clears the UUID, creates and starts a new production, and resets status to `processing_at_auphonic`.

### `CleanUpController`
**`confirm()`** — Confirmation page showing exactly what will be deleted (S3 file, Auphonic production) before anything runs.

**`destroy()`** — Runs the clean-up sequence in order:
1. **Download MP3 from Auphonic** — hard failure gate. If this fails, nothing is deleted and the user is redirected back to the confirm page with an error. Two endpoints are tried: `/engine/` first, `/api/` as fallback.
2. Delete raw WAV from work-in-progress S3 bucket — soft failure (warning collected).
3. Delete Auphonic production via API — soft failure (warning collected).
4. Clear `auphonic_production_uuid` on the episode — always runs.
5. Advance status to `ready_to_upload_production_file` — always runs.

Downloaded MP3 is saved to `storage_path('podcasts/')`. The directory is created automatically if it does not exist.

---

## Services

### `AuphonicService`
All Auphonic API communication. Key methods:

| Method | Description |
|---|---|
| `submitProduction($episode)` | Creates and starts a new Auphonic production |
| `deleteProduction($uuid)` | Deletes a production by UUID |
| `downloadMp3($episode)` | Downloads the processed MP3 with `/engine/` → `/api/` fallback |
| `buildMp3Filename($episode)` | Derives MP3 filename from `raw_input_audio_filename` |
| `buildDownloadUrl($episode)` | Returns the primary `/engine/` download URL |
| `deleteS3Recording($episode)` | Deletes the raw WAV from the work-in-progress S3 bucket |
| `buildAuphonicConsoleUrl($uuid)` | Returns the Auphonic web console URL for a production |

Authentication uses a Bearer token from `config('podcast_post_production.auphonic.api_key')`.

### `S3_work_in_progress_audio`
Owns bucket name, folder path resolution, file listing, and AWS console URL generation for the work-in-progress S3 bucket.

| Method | Description |
|---|---|
| `getBucket()` | Returns `podcast-work-in-progress` |
| `getFolderPath($slug)` | Returns the show's folder prefix |
| `listFiles($slug)` | Lists basenames of all files in the show's folder |
| `buildConsoleUrl($slug)` | Returns a deep-linked AWS S3 console URL for the folder |

---

## Routes

All routes are in `AuphonicProcessing/Routes/auphonic_processing.php` and loaded via `routes/web.php`.

| Method | URI | Name | Controller |
|---|---|---|---|
| POST | `/post-production/auphonic/webhook` | `…webhook` | `WebhookController` |
| GET | `/post-production/auphonic` | `…index` | `IndexController` |
| GET | `/post-production/auphonic/{episode}` | `…show` | `SubmitController@show` |
| POST | `/post-production/auphonic/{episode}/submit` | `…submit` | `SubmitController@submit` |
| GET | `/post-production/auphonic/{episode}/complete` | `…complete` | `CompleteController` |
| GET | `/post-production/auphonic/{episode}/resubmit` | `…resubmit_confirm` | `ResubmitController@confirm` |
| POST | `/post-production/auphonic/{episode}/resubmit` | `…resubmit` | `ResubmitController@resubmit` |
| GET | `/post-production/auphonic/{episode}/webhook-status` | `…webhook_status` | `WebhookStatusController` |
| POST | `/post-production/auphonic/{episode}/replace-recording` | `…replace_recording` | `ReplaceRecordingController` |
| GET | `/post-production/auphonic/{episode}/cleanup` | `…cleanup_confirm` | `CleanUpController@confirm` |
| POST | `/post-production/auphonic/{episode}/cleanup` | `…cleanup_destroy` | `CleanUpController@destroy` |

The webhook route must be defined **before** the `{podcastEpisode}` wildcard routes. It is excluded from CSRF verification in `bootstrap/app.php`.

---

## Views

All views are in `views/media_platform/podcasts/post_production/auphonic_processing/`.

| File | Rendered by |
|---|---|
| `index.blade.php` | `IndexController` |
| `show.blade.php` | `SubmitController@show` |
| `processing.blade.php` | `SubmitController@submit`, `ResubmitController@resubmit`, `WebhookStatusController` |
| `complete.blade.php` | `CompleteController` |
| `resubmit_confirm.blade.php` | `ResubmitController@confirm` |
| `cleanup_confirm.blade.php` | `CleanUpController@confirm` |

---

## Tests

All test files are in `tests/Feature/MEDIA_PLATFORM/Podcasts/PostProduction/AuphonicProcessing/`.

| File | Coverage |
|---|---|
| `IndexControllerTest` | Index listing, filtering, auth |
| `SubmitControllerTest` | S3 check states, submit happy path, error paths, ownership |
| `ReplaceRecordingControllerTest` | Status reset, filename preservation, ownership, auth |
| `WebhookControllerTest` | Done/error/unknown status codes, idempotency, no-auth |
| `WebhookStatusControllerTest` | JSON response, complete flag, ownership |
| `CompleteControllerTest` | Status guard, episode display, ownership, auth |
| `ResubmitControllerTest` | Confirm page, cancel link routing, resubmit sequence, error paths |
| `CleanUpControllerTest` | Confirm page, download hard failure, soft failures, status advancement |
| `AuphonicServiceTest` | `downloadMp3()` happy paths, fallback, both-fail, directory creation |

---

## Key Design Decisions

**Download before delete** — The MP3 download is the only hard failure point in clean-up. If it fails, nothing is deleted and the user can retry safely. S3 and Auphonic deletions are soft failures — the pipeline always advances even if they fail.

**Two download endpoints** — Auphonic has historically served downloads from both `/engine/` and `/api/` paths with varying availability. Both are tried in order before failing.

**Constructor injection over `new`** — `AuphonicService` in `CleanUpController` and `S3_work_in_progress_audio` in `SubmitController` are injected via the constructor (not method injection or direct `new`) so Laravel's container resolves them and mocks bind correctly in tests.

**`raw_input_audio_filename` is immutable** — Set once during episode creation in `PreProduction/CreateEpisode/Step3Controller` and never modified. The S3 check compares whatever is in the folder against this value as the source of truth.

**Status-aware cancel links** — The resubmit confirmation page derives its cancel destination from the episode's current status rather than a session variable or referrer header, keeping the episode status as the single source of truth for navigation.