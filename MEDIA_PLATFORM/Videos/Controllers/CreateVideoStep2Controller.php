<?php

namespace MediaPlatform\Videos\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use MediaPlatform\Videos\Enums\VideoStatus;
use MediaPlatform\Videos\Models\Video;

/**
 * Wizard Step 2 — auto-populate remaining fields and persist the video.
 *
 * There is no user-facing form for Step 2. The store() method is called
 * directly after Step 1 completes. Each field has its own population method
 * so you can customise the logic after scaffolding.
 */
class CreateVideoStep2Controller extends Controller
{
    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    /**
     * Auto-populate fields, persist the video, clear the session, and
     * redirect to the show view.
     */
    public function store(): RedirectResponse
    {
        if (! session()->has('wizard.create_video.title')) {
            return redirect()->route('videos.create.step1');
        }

        $video = Video::create([
            'user_id'             => $this->get_user_id(),
            'title'               => $this->get_title(),
            'description'         => $this->get_description(),
            'scheduled_date'      => $this->get_scheduled_date(),
            'slug'                => $this->get_slug(),
            'status'              => $this->get_status(),
            'youtube_title'       => $this->get_youtube_title(),
            'youtube_description' => $this->get_youtube_description(),
            'youtube_chapters'    => $this->get_youtube_chapters(),
            'youtube_url'         => $this->get_youtube_url(),
        ]);

        // Clear wizard session data.
        session()->forget('wizard.create_video');

        return redirect()
            ->route('videos.show', $video)
            ->with('success', 'Video created successfully.');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  POPULATION METHODS                                                    ║
    // ║                                                                        ║
    // ║  Each method populates a single field. Edit these to customise the     ║
    // ║  auto-population logic for Step 2.                                     ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_user_id()                                                         │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Return the authenticated user's ID.
     */
    public function get_user_id(): int
    {
        return auth()->id();
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_title()                                                           │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Return the title from the wizard session.
     */
    public function get_title(): string
    {
        return trim(session('wizard.create_video.title'));
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_description()                                                     │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Return the description from the wizard session.
     */
    public function get_description(): string
    {
        return trim(session('wizard.create_video.description'));
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_scheduled_date()                                                  │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Return the scheduled date from the wizard session.
     */
    public function get_scheduled_date(): ?string
    {
        return session('wizard.create_video.scheduled_date');
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_formatted_scheduled_date()                                        │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Return the scheduled date formatted as "Month dd, YYYY".
     */
    public function get_formatted_scheduled_date(): string
    {
        $date = $this->get_scheduled_date();

        return $date
            ? Carbon::parse($date)->format('F d, Y')
            : '';
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_slug()                                                            │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Generate a URL-friendly slug from the video title.
     */
    public function get_slug(): string
    {
        return Str::slug($this->get_title());
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_status()                                                          │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Return the default status for a new video.
     */
    public function get_status(): string
    {
        return VideoStatus::not_published_to_youtube->value;
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_youtube_title()                                                   │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Auto-populate the YouTube title as an uppercase version of the video title.
     */
    public function get_youtube_title(): string
    {
        $title           = strtoupper( trim($this->get_title()) );
        $formatted_date  = strtoupper($this->get_formatted_scheduled_date());
        return $title . ', ' . $formatted_date;
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_youtube_description()                                             │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Auto-populate the YouTube description with a standard template.
     */
    public function get_youtube_description(): string
    {
        $title       = $this->get_youtube_title();
        $description = $this->get_description();

        $text_youtube = <<<TEXTYOUTUBE
$description 

0:00 - Welcome
0:11 - Opening
2:44 - Closing


---
Thank you to my amazing PHP Serverless Project sponsors:
✓ Luke Galea: https://ideaforge.org
✓ Tolga Ercan: https://ca.linkedin.com/in/tolga-ercan


---
Links:
• Commentary podcast:  https://BobBloomShow.com
• Interviews podcast:  https://BobBloomInterviews.com
• News podcast:  https://PHPServerlessNews.com
• Profiles podcast:  https://PHPServerlessProfiles.com
• Project Update podcast: https://PHPServerlessProjectUpdates.com


---
🎵 https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)
🎵 https://imslp.org/wiki/Symphony_No.3%2C_Op.55_(Beethoven%2C_Ludwig_van)


---
DISCLAIMER: 
• I make no efforts to update info found to be inaccurate, incomplete, out-of-date, or erroneous.
• This video is intended to convey general information only. 
• My info may be inaccurate, incomplete, out-of-date, or erroneous.
• I make no efforts to update info found to be inaccurate, incomplete, out-of-date, or erroneous.
TEXTYOUTUBE;

        return $text_youtube;
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_youtube_chapters()                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Auto-populate YouTube chapter markers.
     */
    public function get_youtube_chapters(): string
    {
        $text = <<<TEXT
0:00 - Welcome
0:11 - Opening
2:44 - Closing
TEXT;

        return $text;
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_youtube_url()                                                     │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Auto-populate the YouTube URL.
     */
    public function get_youtube_url(): ?string
    {
        // Typically null at creation — populated after uploading to YouTube.
        return "**** PLEASE ENTER THE FULL YOUTUBES URL HERE WHEN YOU UPLOAD THIS VIDEO! *********";
    }
}