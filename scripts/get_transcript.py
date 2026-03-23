import sys
import json
import yt_dlp
from youtube_transcript_api import YouTubeTranscriptApi, TranscriptsDisabled, NoTranscriptFound

def get_metadata(video_id):
    url = f"https://www.youtube.com/watch?v={video_id}"
    ydl_opts = {
        'quiet': True,
        'skip_download': True,
    }
    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
        info = ydl.extract_info(url, download=False)
        return {
            'title':        info.get('title'),
            'channel':      info.get('uploader'),
            'published_at': info.get('upload_date'),  # Format: YYYYMMDD
        }

def get_transcript(video_id):
    ytt_api = YouTubeTranscriptApi()
    fetched = ytt_api.fetch(video_id)
    return " ".join([entry['text'] for entry in fetched.to_raw_data()])

def main(video_id):
    try:
        metadata = get_metadata(video_id)
    except Exception as e:
        print(json.dumps({"error": f"Metadata error: {str(e)}"}))
        sys.exit(1)

    try:
        transcript = get_transcript(video_id)
    except TranscriptsDisabled:
        transcript = "ERROR: Transcripts are disabled for this video."
    except NoTranscriptFound:
        transcript = "ERROR: No transcript found for this video."
    except Exception as e:
        transcript = f"ERROR: {str(e)}"

    result = {
        'title':        metadata['title'],
        'channel':      metadata['channel'],
        'published_at': metadata['published_at'],
        'transcript':   transcript,
    }
    print(json.dumps(result))

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No Video ID provided."}))
        sys.exit(1)

    main(sys.argv[1])