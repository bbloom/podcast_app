# Upload Recording to S3

## What this feature does

Allows a podcast episode's raw WAV recording to be uploaded directly from the browser to S3, without routing the file through the server. Once the upload is confirmed, the episode status is automatically advanced to the next stage of the post-production pipeline.

---

## Where it fits in the pipeline

```
Status: ready_to_upload_recording   ← this feature acts here
            ↓
Status: ready_for_auphonic          ← set automatically on success
```

An episode appears in this feature when its status is set to **Ready to Upload Recording** via the episode CRUD edit form.

---

## Flow

```
GET  /post-production/upload-recording                    index    — list episodes ready for upload
GET  /post-production/upload-recording/{episode}          show     — upload page for a specific episode
POST /post-production/upload-recording/{episode}/presign  store    — generate pre-signed S3 PUT URL
POST /post-production/upload-recording/{episode}/complete complete — confirm file exists, advance status
```

### Step by step

1. User sets the episode status to **Ready to Upload Recording** in the episode edit form.
2. User navigates to **Post-Production → Upload Recording**. The episode appears in the list.
3. User clicks **Upload** to open the episode's upload page.
4. User selects a `.wav` file. Alpine.js calls `store` to request a pre-signed S3 PUT URL.
5. The server generates the URL, stores the S3 key in the session, and returns the URL to the browser.
6. Alpine.js uploads the file directly to S3 using the pre-signed URL, showing upload progress.
7. On S3 success, Alpine.js submits the `complete` form.
8. The server reads the S3 key from the session, calls `HeadObject` to confirm the file landed, records the filename on the episode, advances the status to **Ready for Auphonic**, and redirects to the Post-Production dashboard.

---

## S3 storage

Handled by `S3_work_in_progress_audio` in `PostProduction/CloudStorage/`.

| | |
|---|---|
| **Bucket** | `podcast_work_in_progress` |
| **Key format** | `{show-folder}/raw_input_files/{filename}` |

| Show | Folder |
|---|---|
| The Bob Bloom Show | `bobbloomshow` |
| The Bob Bloom Interviews | `bobbloominterviews` |
| PHP Serverless News | `phpserverlessnews` |
| PHP Serverless Profiles | `phpserverlessprofiles` |
| PHP Serverless Project Updates | `phpserverlessprojectupdates` |

---

## Security

- The S3 pre-signed URL has a **15-minute expiry**. The file is uploaded directly from the browser — it never touches the server.
- The S3 object key is stored **server-side in the session** after `store` runs. It is never sent to or received from the browser. `complete` reads the key from the session only.
- If `complete` is called without a session key (i.e. the endpoint is hit directly without going through `store`), the request is rejected with an error and the user is redirected back to the upload page.
- A `HeadObject` call confirms the file actually exists in S3 before the episode status is advanced. If the file is not found, the status is not changed and the user is shown an error.

---

## File structure

```
UploadRecording/
├── Controllers/
│   └── UploadRecordingController.php
├── Exceptions/
│   └── UploadRecordingException.php
├── Routes/
│   └── upload_recording.php          — loaded via require in routes/web.php
└── Services/
    └── UploadRecordingService.php
```

Supporting classes used by this feature:

```
PostProduction/CloudStorage/
└── S3_work_in_progress_audio.php     — bucket name and folder path resolution

PostProduction/UploadRecording/Exceptions/
└── UploadRecordingException.php      — typed exception for S3 failures
```

---

## Error handling

All errors redirect the user with a flash message rather than throwing an unhandled exception.

| Situation | Behaviour |
|---|---|
| Episode belongs to another user | Redirect to index with error |
| Episode has wrong status | Redirect to index with error |
| S3 pre-signed URL generation fails | JSON error returned to Alpine |
| S3 PUT upload fails | Alpine shows inline error — `complete` is never called |
| Session key missing on `complete` | Redirect to show page with error |
| File not found in S3 on `complete` | Redirect to show page with error |

---

## S3 CORS configuration

Because the browser uploads directly to S3 (bypassing the server), the `podcast_work_in_progress` bucket must allow cross-origin PUT requests. Without this, the upload will fail with a network error in the browser.

Add the following CORS policy to the bucket:

**AWS Console → S3 → `podcast_work_in_progress` → Permissions → Cross-origin resource sharing (CORS) → Edit**

```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["PUT"],
        "AllowedOrigins": ["https://yourdomain.com"],
        "ExposeHeaders": []
    }
]
```

During local development, set `AllowedOrigins` to `["*"]`. Lock it down to your production domain before going live.

> **Note:** The browser-to-S3 upload will not work in local development environments that use self-signed SSL certificates (e.g. `*.test` domains). AWS rejects preflight requests from untrusted origins. This is expected — the feature works correctly in production where a valid SSL certificate is present.

---

## Tests

`tests/Feature/MEDIA_PLATFORM/PodcastStudio/PostProduction/UploadRecording/UploadRecordingControllerTest.php`

18 tests covering: index listing, status and ownership filtering, show page access, presign JSON response, session key storage, filename validation, S3 failure handling, status advancement, filename persistence, session key cleanup, missing session key guard, and wrong-owner protection on all four endpoints.