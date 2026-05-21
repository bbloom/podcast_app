<?php

namespace MediaPlatform\Podcasts\Publishing\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\Podcasts\Publishing\Requests\UpdatePodcastEpisodeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PodcastEpisodeUpdateController extends Controller
{
    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  EDIT                                                                  ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Show the form for editing a podcast episode.
     */
    public function edit(PodcastEpisode $podcast_episode): View|RedirectResponse
    {
        if ($podcast_episode->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        $shows    = PodcastShow::where('user_id', auth()->id())->orderBy('title')->get();
        $statuses = PodcastEpisodeStatus::cases();

        return view(
            'media_platform.podcasts.publishing.episodes.edit',
            compact('shows', 'statuses') + ['episode' => $podcast_episode]
        );
    }


    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  UPDATE                                                                ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Persist updates to a podcast episode.
     *
     * Every field is handled explicitly via its own population method,
     * mirroring Step3Controller's CREATE structure. This makes it visually
     * traceable which fields are updated and how.
     *
     * Fields NOT handled here (set elsewhere in the pipeline, never editable):
     *   - user_id                  → set at creation, immutable
     *   - auphonic_production_uuid → set by the Auphonic post-production step
     */
    public function update(UpdatePodcastEpisodeRequest $request, PodcastEpisode $podcast_episode): RedirectResponse
    {
        if ($podcast_episode->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // Ownership: ensure the selected show belongs to this user.
        $show = PodcastShow::find($request->validated()['podcast_show_id']);

        if (! $show || $show->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes.edit', $podcast_episode)
                ->with('error', 'The selected podcast show could not be found.');
        }

        $podcast_episode->update([

            // -----------------------------------------------------------------
            // General
            // -----------------------------------------------------------------
            'podcast_show_id'          => $this->get_podcast_show_id($request),
            'title'                    => $this->get_title($request),
            'slug'                     => $this->get_slug($request, $podcast_episode),
            'scheduled_date'           => $this->get_scheduled_date($request),
            'draft'                    => $this->get_draft($request),
            'raw_input_audio_filename' => $this->get_raw_input_audio_filename($request),

            // -----------------------------------------------------------------
            // Status
            // -----------------------------------------------------------------
            'status'                   => $this->get_status($request),

            // -----------------------------------------------------------------
            // iTunes
            // -----------------------------------------------------------------
            'itunes_title_tag'         => $this->get_itunes_title_tag($request),
            'itunes_enclosure_url'     => $this->get_itunes_enclosure_url($request),
            'itunes_enclosure_length'  => $this->get_itunes_enclosure_length($request),
            'itunes_enclosure_type'    => $this->get_itunes_enclosure_type($request),
            'itunes_guid'              => $this->get_itunes_guid($request),
            'itunes_pubdate'           => $this->get_itunes_pubdate($request),
            'itunes_description'       => $this->get_itunes_description($request),
            'itunes_duration'          => $this->get_itunes_duration($request),
            'itunes_link'              => $this->get_itunes_link($request),
            'itunes_image'             => $this->get_itunes_image($request),
            'itunes_explicit'          => $this->get_itunes_explicit($request),
            'itunes_itunestitle_tag'   => $this->get_itunes_itunestitle_tag($request),
            'itunes_episode'           => $this->get_itunes_episode($request),
            'itunes_season'            => $this->get_itunes_season($request),
            'itunes_episode_type'      => $this->get_itunes_episode_type($request),
            'itunes_block'             => $this->get_itunes_block($request),
            'itunes_summary'           => $this->get_itunes_summary($request),
            'itunes_subtitle'          => $this->get_itunes_subtitle($request),
            'itunes_content_encoded'   => $this->get_itunes_content_encoded($request),

            // -----------------------------------------------------------------
            // RSS
            // -----------------------------------------------------------------
            'rss_feed_enabled'         => $this->get_rss_feed_enabled($request),

            // -----------------------------------------------------------------
            // Website
            // -----------------------------------------------------------------
            'website_content'          => $this->get_website_content($request),
            'website_excerpt'          => $this->get_website_excerpt($request),
            'website_meta_description' => $this->get_website_meta_description($request),
            'website_episode_notes'    => $this->get_website_episode_notes($request),
            'website_attribution'      => $this->get_website_attribution($request),
            'website_featured_image'   => $this->get_website_featured_image($request),
            'website_publish_on'       => $this->get_website_publish_on($request),
            'website_enabled'          => $this->get_website_enabled($request),
        ]);

        return redirect()
            ->route('podcast_episodes.show', $podcast_episode)
            ->with('success', 'Podcast episode updated successfully.');
    }


    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  POPULATION METHODS                                                    ║
    // ║                                                                        ║
    // ║  Each field has its own method, mirroring Step3Controller.              ║
    // ║  In UPDATE, most methods are pass-throughs: save what the user typed.   ║
    // ║  Booleans use $request->boolean() because unchecked HTML checkboxes     ║
    // ║  are absent from the request entirely.                                 ║
    // ╚════════════════════════════════════════════════════════════════════════╝


    // -----------------------------------------------------------------
    // GENERAL
    // -----------------------------------------------------------------


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_podcast_show_id()                                                 │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_podcast_show_id(UpdatePodcastEpisodeRequest $request): int
    {
        return (int) $request->validated()['podcast_show_id'];
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_title()                                                           │
    // │                                                                        │
    // │  In CREATE, the wizard prepends the "#N - " prefix.                    │
    // │  In UPDATE, save exactly what the user typed. If they changed the       │
    // │  episode number, they are responsible for updating the prefix too.      │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_title(UpdatePodcastEpisodeRequest $request): string
    {
        return $request->validated()['title'];
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_slug()                                                            │
    // │                                                                        │
    // │  In CREATE, the wizard constructs the slug from the show slug,          │
    // │  episode number, and washed title.                                      │
    // │  In UPDATE, save what the user typed. If the slug field was left        │
    // │  unchanged in the form, preserve the existing value.                    │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_slug(UpdatePodcastEpisodeRequest $request, PodcastEpisode $episode): string
    {
        return $request->validated()['slug'] ?? $episode->slug;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_scheduled_date()                                                  │
    // │                                                                        │
    // │  In CREATE, defaults to today if not provided.                          │
    // │  In UPDATE, save what the user entered.                                 │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_scheduled_date(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['scheduled_date'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_draft()                                                           │
    // │                                                                        │
    // │  In CREATE, set to null.                                               │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_draft(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['draft'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_raw_input_audio_filename()                                        │
    // │                                                                        │
    // │  In CREATE, constructed from normalised show title + episode number.    │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_raw_input_audio_filename(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['raw_input_audio_filename'] ?? null;
    }


    // -----------------------------------------------------------------
    // STATUS
    // -----------------------------------------------------------------


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_status()                                                          │
    // │                                                                        │
    // │  In CREATE, always PodcastEpisodeStatus::ready_to_upload_recording.                       │
    // │  In UPDATE, save the user's selection.                                  │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_status(UpdatePodcastEpisodeRequest $request): string
    {
        return $request->validated()['status'];
    }


    // -----------------------------------------------------------------
    // ITUNES
    // -----------------------------------------------------------------


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_title_tag()                                                │
    // │                                                                        │
    // │  In CREATE, mirrors the full title.                                     │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_title_tag(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_title_tag'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_enclosure_url()                                            │
    // │                                                                        │
    // │  In CREATE, constructed from show's storage URL + normalised title +    │
    // │  episode number.                                                       │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_enclosure_url(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_enclosure_url'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_enclosure_length()                                         │
    // │                                                                        │
    // │  In CREATE, set to null (populated during post-production).             │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_enclosure_length(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_enclosure_length'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_enclosure_type()                                           │
    // │                                                                        │
    // │  In CREATE, always "audio/mpeg".                                        │
    // │  In UPDATE, save the user's selection.                                  │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_enclosure_type(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_enclosure_type'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_guid()                                                     │
    // │                                                                        │
    // │  In CREATE, a random UUID-like string is generated.                     │
    // │  In UPDATE, save what the user typed. Changing the GUID will cause      │
    // │  podcast apps to treat this as a new episode — handle with care.        │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_guid(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_guid'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_pubdate()                                                  │
    // │                                                                        │
    // │  In CREATE, derived from scheduled_date + " 00:00:00".                  │
    // │  In UPDATE, save what the user entered.                                 │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_pubdate(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_pubdate'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_description()                                              │
    // │                                                                        │
    // │  In CREATE, constructed from website_content + appended links.          │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_description(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_description'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_duration()                                                 │
    // │                                                                        │
    // │  In CREATE, set to null (populated during post-production).             │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_duration(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_duration'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_link()                                                     │
    // │                                                                        │
    // │  In CREATE, constructed from show's itunes_link + "/episode/" + slug.   │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_link(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_link'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_image()                                                    │
    // │                                                                        │
    // │  In CREATE, set to null.                                               │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_image(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_image'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_explicit()                                                 │
    // │                                                                        │
    // │  In CREATE, always false.                                              │
    // │  In UPDATE, use $request->boolean() because unchecked checkboxes are    │
    // │  absent from HTTP requests.                                            │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_explicit(UpdatePodcastEpisodeRequest $request): bool
    {
        return $request->boolean('itunes_explicit');
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_itunestitle_tag()                                          │
    // │                                                                        │
    // │  In CREATE, derived from title with the "#N - " prefix stripped.        │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_itunestitle_tag(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_itunestitle_tag'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_episode()                                                  │
    // │                                                                        │
    // │  In CREATE, from form input.                                           │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_episode(UpdatePodcastEpisodeRequest $request): ?int
    {
        $value = $request->validated()['itunes_episode'] ?? null;

        return $value !== null ? (int) $value : null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_season()                                                   │
    // │                                                                        │
    // │  In CREATE, always 0.                                                  │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_season(UpdatePodcastEpisodeRequest $request): ?int
    {
        $value = $request->validated()['itunes_season'] ?? null;

        return $value !== null ? (int) $value : null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_episode_type()                                             │
    // │                                                                        │
    // │  In CREATE, always "full".                                             │
    // │  In UPDATE, save the user's selection.                                  │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_episode_type(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_episode_type'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_block()                                                    │
    // │                                                                        │
    // │  In CREATE, always false.                                              │
    // │  In UPDATE, use $request->boolean() because unchecked checkboxes are    │
    // │  absent from HTTP requests.                                            │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_block(UpdatePodcastEpisodeRequest $request): bool
    {
        return $request->boolean('itunes_block');
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_summary()                                                  │
    // │                                                                        │
    // │  In CREATE, mirrors itunes_description.                                │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_summary(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_summary'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_subtitle()                                                 │
    // │                                                                        │
    // │  In CREATE, derived from title with the "#N - " prefix stripped.        │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_subtitle(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_subtitle'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_content_encoded()                                          │
    // │                                                                        │
    // │  In CREATE, constructed from website_content + appended HTML links.     │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_content_encoded(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['itunes_content_encoded'] ?? null;
    }


    // -----------------------------------------------------------------
    // RSS
    // -----------------------------------------------------------------


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_rss_feed_enabled()                                                │
    // │                                                                        │
    // │  In CREATE, always false.                                              │
    // │  In UPDATE, use $request->boolean() because unchecked checkboxes are    │
    // │  absent from HTTP requests.                                            │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_rss_feed_enabled(UpdatePodcastEpisodeRequest $request): bool
    {
        return $request->boolean('rss_feed_enabled');
    }


    // -----------------------------------------------------------------
    // WEBSITE
    // -----------------------------------------------------------------


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_content()                                                 │
    // │                                                                        │
    // │  In CREATE, sanitised (html_entity_decode, strip_tags with allowlist).  │
    // │  In UPDATE, save what the user typed. The content was already           │
    // │  sanitised at creation and any edits are intentional.                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_content(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['website_content'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_excerpt()                                                 │
    // │                                                                        │
    // │  In CREATE, derived from website_content (first 255 chars, stripped).   │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_excerpt(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['website_excerpt'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_meta_description()                                        │
    // │                                                                        │
    // │  In CREATE, mirrors website_excerpt.                                    │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_meta_description(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['website_meta_description'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_episode_notes()                                           │
    // │                                                                        │
    // │  In CREATE, set to null.                                               │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_episode_notes(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['website_episode_notes'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_attribution()                                             │
    // │                                                                        │
    // │  In CREATE, determined by show title (Beethoven symphony credits).      │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_attribution(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['website_attribution'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_featured_image()                                          │
    // │                                                                        │
    // │  In CREATE, set to null.                                               │
    // │  In UPDATE, save what the user typed.                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_featured_image(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['website_featured_image'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_publish_on()                                              │
    // │                                                                        │
    // │  In CREATE, mirrors scheduled_date.                                     │
    // │  In UPDATE, save what the user entered.                                 │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_publish_on(UpdatePodcastEpisodeRequest $request): ?string
    {
        return $request->validated()['website_publish_on'] ?? null;
    }


    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_enabled()                                                 │
    // │                                                                        │
    // │  In CREATE, always false.                                              │
    // │  In UPDATE, use $request->boolean() because unchecked checkboxes are    │
    // │  absent from HTTP requests.                                            │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_enabled(UpdatePodcastEpisodeRequest $request): bool
    {
        return $request->boolean('website_enabled');
    }
}