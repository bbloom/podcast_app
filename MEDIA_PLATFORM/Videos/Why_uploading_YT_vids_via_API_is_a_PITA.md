Yes, it's absolutely possible — YouTube's Data API v3 has a `videos.insert` endpoint specifically for this. You can upload a video file and set all the metadata (title, description, chapters, privacy status, etc.) in a single API call. But you're right that it's not entirely straightforward. Here's why:

**What works well:**

The YouTube Data API lets you upload videos, manage playlists and subscriptions, update channel settings, and more. The API method you use is `videos.insert`, which supports media uploads up to 256 GB. Google provides official PHP client libraries, so you'd use `google/apiclient` via Composer.

**The parts that aren't straightforward:**

1. **OAuth 2.0 is mandatory** — you need to create a Google Cloud project, enable the YouTube Data API v3, set up OAuth 2.0 (no service accounts work for uploads), and get a refresh token. Service accounts don't work for YouTube uploads because the video has to go to a specific YouTube channel, which requires the channel owner to authorize once via the OAuth consent flow. After that, you store the refresh token and reuse it.

2. **Quota limits** — projects have a default quota allocation of 10,000 units per day. Each `videos.insert` call costs 1,600 units, meaning you can upload about 6 videos per day with the default quota. For your use case (preparing individual videos for YouTube), this is plenty.

3. **Google's audit process** — if you want quota beyond the default allocation, you must first complete an audit to show your project complies with the YouTube API Services Terms of Service. For a personal tool uploading your own videos, the default 10,000 units/day is more than enough, so you likely won't need this.

**For your app specifically**, the integration would look like: add a "Publish to YouTube" button on the video SHOW page → it reads the video's metadata fields (youtube_title, youtube_description, etc.), uploads the video file via `videos.insert`, captures the returned YouTube URL, saves it to the `youtube_url` field, and updates the status to `published_to_youtube`.

The one-time setup (Google Cloud project, OAuth consent screen, initial authorization) is the annoying part. After that, the actual upload code is fairly concise. Want me to look into what this would take to wire up in your Laravel app?