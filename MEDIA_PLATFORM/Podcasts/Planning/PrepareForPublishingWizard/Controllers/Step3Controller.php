<?php

namespace MediaPlatform\Podcasts\Planning\PrepareForPublishingWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Planning\PrepareForPublishingWizard\Concerns\DerivesPublishedEpisodeFields;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class Step3Controller extends Controller
{
    use DerivesPublishedEpisodeFields;

    /**
     * Render the scary confirmation page.
     * Explicitly lists everything that will happen: episode created, guests
     * migrated, links migrated, planning record PERMANENTLY DELETED.
     */
    public function show(): View|RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index')
                ->with('error', 'Session expired. Please return to the episode and begin the wizard again.');
        }

        $episode->load(['show', 'guests', 'links']);

        return view('media_platform.podcasts.planning.prepare_for_publishing_wizard.step3', [
            'episode'     => $episode,
            'guestCount'  => $episode->guests->count(),
            'linkCount'   => $episode->links->count(),
        ]);
    }

    /**
     * Execute the hard handoff.
     *
     * Wrapped in a database transaction — if anything fails, no partial state
     * is left behind. All-or-nothing.
     *
     * Steps:
     *  1. Load planning episode and show from session.
     *  2. Run all population methods to derive published episode fields.
     *  3. Create the podcast_episodes_published record.
     *  4. Migrate guests from podcast_guest_episode_planning → podcast_guest_episode.
     *  5. Migrate links from podcast_link_episode_planning → podcast_link_episode.
     *  6. Hard-delete the planning record (no soft deletes).
     *  7. Clear the wizard session.
     *  8. Redirect to the published episode show page.
     */
    public function store(): RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index');
        }

        $episode->load(['show', 'guests', 'links']);
        $show = $episode->show;

        $published = DB::transaction(function () use ($episode, $show) {

            // -----------------------------------------------------------------
            // Create the published episode record.
            // All fields are derived from the planning record using the
            // population methods in DerivesPublishedEpisodeFields.
            // -----------------------------------------------------------------
            $published = PodcastEpisode::create([

                // Ownership
                'user_id'                  => auth()->id(),

                // General
                'podcast_show_id'          => $show->id,
                'title'                    => $this->get_title($episode),
                'slug'                     => $this->get_slug($episode, $show),
                'scheduled_date'           => $this->get_scheduled_date($episode),
                'draft'                    => null,
                'raw_input_audio_filename' => $this->get_raw_input_audio_filename($episode, $show),

                // Status
                'status'                   => $this->get_status(),

                // iTunes
                'itunes_title_tag'         => $this->get_itunes_title_tag($episode),
                'itunes_enclosure_url'     => $this->get_itunes_enclosure_url($episode, $show),
                'itunes_enclosure_length'  => null,
                'itunes_enclosure_type'    => $this->get_itunes_enclosure_type(),
                'itunes_guid'              => $this->get_itunes_guid(),
                'itunes_pubdate'           => $this->get_itunes_pubdate($episode),
                'itunes_description'       => $this->get_itunes_description($episode, $show),
                'itunes_duration'          => null,
                'itunes_link'              => $this->get_itunes_link($episode, $show),
                'itunes_image'             => null,
                'itunes_explicit'          => false,
                'itunes_itunestitle_tag'   => $this->get_itunes_itunestitle_tag($episode),
                'itunes_episode'           => $this->get_itunes_episode($episode),
                'itunes_season'            => $this->get_itunes_season(),
                'itunes_episode_type'      => $this->get_itunes_episode_type(),
                'itunes_block'             => false,
                'itunes_summary'           => $this->get_itunes_summary($episode, $show),
                'itunes_subtitle'          => $this->get_itunes_subtitle($episode),
                'itunes_content_encoded'   => $this->get_itunes_content_encoded($episode, $show),

                // RSS
                'rss_feed_enabled'         => false,

                // Website
                'website_content'          => $this->get_website_content($episode),
                'website_excerpt'          => $this->get_website_excerpt($episode),
                'website_meta_description' => $this->get_website_meta_description($episode),
                'website_episode_notes'    => null,
                'website_attribution'      => $this->get_website_attribution($show),
                'website_featured_image'   => null,
                'website_publish_on'       => $this->get_website_publish_on($episode),
                'website_enabled'          => false,
            ]);

            // -----------------------------------------------------------------
            // Migrate guests.
            // Copies all guest IDs from podcast_guest_episode_planning
            // to podcast_guest_episode on the new published record.
            // -----------------------------------------------------------------
            $guestIds = $episode->guests()->pluck('podcast_guests.id')->toArray();
            if (! empty($guestIds)) {
                $published->guests()->attach($guestIds);
            }

            // -----------------------------------------------------------------
            // Migrate links.
            // Copies all link IDs from podcast_link_episode_planning
            // to podcast_link_episode on the new published record.
            // -----------------------------------------------------------------
            $linkIds = $episode->links()->pluck('podcast_links.id')->toArray();
            if (! empty($linkIds)) {
                $published->links()->attach($linkIds);
            }

            // -----------------------------------------------------------------
            // Hard-delete the planning record.
            // No soft deletes on planning records — physically removed.
            // -----------------------------------------------------------------
            $episode->delete();

            return $published;
        });

        session()->forget('wizard.prepare_for_publishing.episode_id');

        return redirect()
            ->route('podcast_episodes.show', $published)
            ->with('success', "Episode #{$published->itunes_episode} — {$published->title} has been published and is now in the Post-Production pipeline.");
    }

    private function getEpisodeFromSession(): ?PodcastEpisodePlanning
    {
        $id = session('wizard.prepare_for_publishing.episode_id');
        if (! $id) return null;
        $episode = PodcastEpisodePlanning::find($id);
        if (! $episode || $episode->user_id !== auth()->id()) return null;
        return $episode;
    }
}