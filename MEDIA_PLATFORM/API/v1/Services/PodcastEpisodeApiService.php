<?php

namespace MediaPlatform\API\v1\Services;

use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastGuest;
use MediaPlatform\Tools\PhpServerlessProjectSponsors\Models\PhpServerlessProjectSponsor;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;

class PodcastEpisodeApiService
{
    /**
     * Assemble the full API payload:
     *   - The podcast show itself (with footer links)
     *   - Published episodes for the requested show, each with their guests and links
     *   - All enabled guests (for dedicated guest pages)
     *   - All enabled sponsors
     */
    public function getPayload(string $podcastShowSlug): array
    {
        $show = PodcastShow::where('slug', $podcastShowSlug)
            ->with('footerLinks')
            ->first();

        return [
            'show'     => $show,
            'episodes' => $this->getEpisodes($show),
            'guests'   => $this->getGuests(),
            'sponsors' => $this->getSponsors(),
        ];
    }

    // -------------------------------------------------------------------------
    // Episodes
    // -------------------------------------------------------------------------

    /**
     * Fetch published episodes for the given show, eager-loading guests and links.
     * "Published" means website_enabled = true and website_publish_on is
     * in the past. Ordered newest first.
     */
    private function getEpisodes(?PodcastShow $show): \Illuminate\Support\Collection
    {
        // Return an empty collection if the show does not exist —
        // consistent with the original behaviour of whereHas() returning nothing.
        if (! $show) {
            return collect();
        }

        return PodcastEpisode::eligibleForPublishOnWebsite($show)
            ->with(['guests', 'links'])
            ->get();
    }

    // -------------------------------------------------------------------------
    // Guests
    // -------------------------------------------------------------------------

    /**
     * Fetch all enabled guests for the top-level guests array.
     * These are used by Astro to build dedicated guest pages.
     */
    private function getGuests(): \Illuminate\Support\Collection
    {
        return PodcastGuest::where('enabled', true)
            ->orderBy('full_name', 'asc')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Sponsors
    // -------------------------------------------------------------------------

    /**
     * Fetch all enabled sponsors, ordered by name.
     */
    private function getSponsors(): \Illuminate\Support\Collection
    {
        return PhpServerlessProjectSponsor::where('enabled', true)
            ->orderBy('full_name', 'asc')
            ->get();
    }


    // -------------------------------------------------------------------------
    // Original Bob Bloom Show episodes
    // -------------------------------------------------------------------------

    /**
     * Fetch all enabled sponsors, ordered by name.
     */
    private function getArchivedBobBloomShow()
    {
        $location_url = 'https://pub-5c1f41e529d24593aef338cfbdd850b2.r2.dev/';

        return [
 
            ['episode_number' => 1,  'date' => 'February 18, 2010',    'duration' => '29m 37s',     'filename' => 'tbbs1.mp3',     'title' => 'Tienda Talk With Rafael Diaz-Tushman',],
            ['episode_number' => 2,  'date' => 'February 25, 2010',    'duration' => '29m 36s',     'filename' => 'tbbs2.mp3',     'title' => 'Joomla Jabber And Talking Tienda With Alan Langford',],
            ['episode_number' => 3,  'date' => 'March 04, 2010',       'duration' => '29m 09s',     'filename' => 'tbbs3.mp3',     'title' => 'Reviewing Tienda Alpha With Joe Joomla Sonne',],
            ['episode_number' => 4,  'date' => 'March 11, 2010',       'duration' => '24m 43s',     'filename' => 'tbbs4.mp3',     'title' => 'Coming In For A Landing Page With Derek Heck',],
            ['episode_number' => 5,  'date' => 'March 25, 2010',       'duration' => '28m 50s',     'filename' => 'tbbs5.mp3',     'title' => 'Interview With Viktoria Osipenko',],
            ['episode_number' => 6,  'date' => 'April 01, 2010',       'duration' => '35m 50s',     'filename' => 'tbbs6.mp3',     'title' => 'Social ECommerce Joomla Sites With Alice Grevet',],
            ['episode_number' => 7,  'date' => 'April 08, 2010',       'duration' => '29m 55s',     'filename' => 'tbbs7.mp3',     'title' => 'De-Mystifying Joomla-the-Project With Ian MacLennan',],
            ['episode_number' => 8,  'date' => 'April 15, 2010',       'duration' => '59m 12s',     'filename' => 'tbbs8.mp3',     'title' => 'Framing Extensions With Nooku, With Johan Jansenns',],
            ['episode_number' => 9,  'date' => 'April 22, 2010',       'duration' => '30m 03s',     'filename' => 'tbbs9.mp3',     'title' => 'The Anahita Social Engine With Rastin Mehr And Ash ',],
            ['episode_number' => 10, 'date' => 'April 30, 2010',       'duration' => '30m 05s',     'filename' => 'tbb10s.mp3',    'title' => 'A Helping Of Zendesk With Adria Richards',],
            ['episode_number' => 11, 'date' => 'May 06, 2010',         'duration' => '30m 01s',     'filename' => 'tbbs11.mp3',    'title' => 'Guest Round Table: Are We Entering The Golden Age Of Joomla ECommerce?',],
            ['episode_number' => 12, 'date' => 'May 13, 2010',         'duration' => '28m 20s',     'filename' => 'tbbs12.mp3',    'title' => 'GIT On With Source Code Repositories With Andy Singleton',],
            ['episode_number' => 13, 'date' => 'May 20, 2010',         'duration' => '30m 46s',     'filename' => 'tbbs13.mp3',    'title' => 'Your Template For Success With Andy Miller',],
            ['episode_number' => 14, 'date' => 'May 27, 2010',         'duration' => '30m 09s',     'filename' => 'tbbs14.mp3',    'title' => 'Bed, Migration, And Beyond',],
            ['episode_number' => 15, 'date' => 'June 10, 2010',        'duration' => '28m 26s',     'filename' => 'tbbs15.mp3',    'title' => 'Nooku Ninja With Stian Didriksen',],
            ['episode_number' => 16, 'date' => 'June 17, 2010',        'duration' => '59m 36s',     'filename' => 'tbbs16.mp3',    'title' => 'Tienda, Nooku FW, Anahita Face My Round Table',],
            ['episode_number' => 17, 'date' => 'July 08, 2010',        'duration' => '29m 55s',     'filename' => 'tbbs17.mp3',    'title' => 'Round Table Takes Change To Task',],
            ['episode_number' => 18, 'date' => 'July 22, 2010',        'duration' => '41m 14s',     'filename' => 'tbbs18.mp3',    'title' => 'Following Alice’s Adventures In The Joomla Wonderland',],
            ['episode_number' => 19, 'date' => 'August 05, 2010',      'duration' => '30m 31s',     'filename' => 'tbbs19.mp3',    'title' => 'Joomla Security And Infrastructure Essentials With Alan Langford',],
            ['episode_number' => 20, 'date' => 'August 12, 2010',      'duration' => '30m 09s',     'filename' => 'tbbs20.mp3',    'title' => 'Template Frameworks: Favoured Friend Of Joomla Ecommerce Sites?',],
            ['episode_number' => 21, 'date' => 'September 16, 2010',   'duration' => '26m 59s',     'filename' => 'tbbs21.mp3',    'title' => 'Emergence Of Nooku Framework Based Joomla ECommerce',],
            ['episode_number' => 22, 'date' => 'September 23, 2010',   'duration' => '36m 36s',     'filename' => 'tbbs22.mp3',    'title' => 'Emergence Of Nooku Framework Based Joomla ECommerce',],
            ['episode_number' => 23, 'date' => 'October 07, 2010',     'duration' => '34m 23s',     'filename' => 'tbbs23.mp3',    'title' => 'The Grateful JED: Does It Rock Or Roll?',],
            ['episode_number' => 24, 'date' => 'October 13, 2010',     'duration' => '38m 26s',     'filename' => 'tbbs24.mp3',    'title' => 'The Grateful JED: Does It Rock Or Roll? The Rejoinder',],
            ['episode_number' => 25, 'date' => 'October 14, 2010',     'duration' => '35m 15s',     'filename' => 'tbbs25.mp3',    'title' => 'Magento And Joomla, Part One, With Ray Bogman',],
            ['episode_number' => 26, 'date' => 'October 21, 2010',     'duration' => '35m 15s',     'filename' => 'tbbs26.mp3',    'title' => 'Magento And Joomla, Part Two, With Ray Bogman',],
            ['episode_number' => 27, 'date' => 'October 28, 2010',     'duration' => '12m 11s',     'filename' => 'tbbs27.mp3',    'title' => 'Fork Or Distro: The Joomla Tines Are A-Changin',],
            ['episode_number' => 28, 'date' => 'November 11, 2010',    'duration' => '29m 17s',     'filename' => 'tbbs28.mp3',    'title' => 'Healthy Dose Of Vertical Marketing With Faisal Qureshi',],
            ['episode_number' => 29, 'date' => 'November 18, 2010',    'duration' => '28m 35s',     'filename' => 'tbbs29.mp3',    'title' => 'Joomla Jabber With Joe “Joomla” Sonne',],
            ['episode_number' => 30, 'date' => 'March 10, 2010',       'duration' => '35m 09s',     'filename' => 'tbbs30.mp3',    'title' => 'Splaining About Going For-Profit',],
            ['episode_number' => 31, 'date' => 'February 17, 2010',    'duration' => '12m 43s',     'filename' => 'tbbs31.mp3',    'title' => 'I Have Some Splaining To Do: Three Ascendent Joomla Technologies',],
            ['episode_number' => 32, 'date' => 'April 28, 2010',       'duration' => '12m 27s',     'filename' => 'tbbs32.mp3',    'title' => 'I’m Specializing In Tienda-Nooku',],
            ['episode_number' => 33, 'date' => 'May 26, 2010',         'duration' => '25m 35s',     'filename' => 'tbbs33.mp3',    'title' => 'Getting To Know Chill Creations’ David De Boer',],
            ['episode_number' => 34, 'date' => 'May 26, 2010',         'duration' => '40m 56s',     'filename' => 'tbbs34.mp3',    'title' => 'David De Boer: Choices and Decisions – Joomla Development',],
            ['episode_number' => 35, 'date' => 'June 03, 2010',        'duration' => '51m 20s',     'filename' => 'tbbs35.mp3',    'title' => 'Torkil Johnsen: Nooku Framework Development Set-up',],
            ['episode_number' => 36, 'date' => 'September 12, 2010',   'duration' => '22m 23s',     'filename' => 'tbbs36.mp3',    'title' => 'Clubbing At Club Commerce',],
            ['episode_number' => 37, 'date' => 'September 15, 2010',   'duration' => '26m 32s',     'filename' => 'tbbs37.mp3',    'title' => 'Socializing With Anahita, Part 1 Of 2',],
            ['episode_number' => 38, 'date' => 'September 15, 2010',   'duration' => '27m 10s',     'filename' => 'tbbs38.mp3',    'title' => 'Socializing With Anahita, Part 2 Of 2',],
            ['episode_number' => 39, 'date' => 'October 06, 2010',     'duration' => '16m 45s',     'filename' => 'tbbs39.mp3',    'title' => 'Why I Came Home To Joomla and Tienda',],
            ['episode_number' => 40, 'date' => 'November 17, 2010',    'duration' => '12m 20s',     'filename' => 'tbbs40.mp3',    'title' => 'Why I Am Not Using The Nooku Framework',],
            ['episode_number' => 41, 'date' => 'December 01, 2010',    'duration' => '21m 01s',     'filename' => 'tbbs41.mp3',    'title' => 'Power, Hackerpreneur, Tienda, Tech Talk, Nooku',],
            ['episode_number' => 42, 'date' => 'December 08, 2010',    'duration' => '13m 06s',     'filename' => 'tbbs42.mp3',    'title' => 'Virtual Guest Alan Langford On Joomla Distro, Content Pattern Plugin, And Transcripts',],
            ['episode_number' => 43, 'date' => 'December 15, 2010',    'duration' => '28m 34s',     'filename' => 'tbbs43.mp3',    'title' => 'Hackerprenomics With Rastin And Ash',],
            ['episode_number' => 44, 'date' => 'January 05, 2012',     'duration' => '17m 28s',     'filename' => 'tbbs44.mp3',    'title' => 'The Birth Of LaSalleMart',],
            ['episode_number' => 45, 'date' => 'January 12, 2012',     'duration' => '22m 26s',     'filename' => 'tbbs45.mp3',    'title' => 'Squaring Off With Jeremy Wilken, Part 1 Of 2',],
            ['episode_number' => 46, 'date' => 'January 19, 2012',     'duration' => '29m 40s',     'filename' => 'tbbs46.mp3',    'title' => 'Squaring Off With Jeremy Wilken, Part 2 Of 2',],
            ['episode_number' => 47, 'date' => 'February 23, 2012',    'duration' => '31m 23s',     'filename' => 'tbbs47.mp3',    'title' => 'Alan Langford Tables Paid Extension',],
            ['episode_number' => 48, 'date' => 'March 08, 2012',       'duration' => '30m 24s',     'filename' => 'tbbs48.mp3',    'title' => 'Tickets, Framework, And Testing With Nicholas Dionysopoulos',],
            ['episode_number' => 49, 'date' => 'March 21, 2012',       'duration' => '25m 45s',     'filename' => 'tbbs49.mp3',    'title' => 'Throwing The Book At Joe LeBlanc, Part 1 Of 2',],
            ['episode_number' => 50, 'date' => 'March 22, 2012',       'duration' => '24m 49s',     'filename' => 'tbbs50.mp3',    'title' => 'Throwing The Book At Joe LeBlanc, Part 2 Of 2',],
            ['episode_number' => 51, 'date' => 'April 24, 2012',       'duration' => '34m 50s',     'filename' => 'tbbs51.mp3',    'title' => 'Learning From Ash And Rastin, Part 1 Of 2',],
            ['episode_number' => 52, 'date' => 'April 25, 2012',       'duration' => '19m 46s',     'filename' => 'tbbs52.mp3',    'title' => 'Learning From Ash And Rastin, Part 2 Of 2',],
           // ['episode_number' => '52A', 'date' => '',   'duration' => 'm s',     'filename' => 'tbbs52a.mp3',    'title' => '',],
            ['episode_number' => 53, 'date' => 'November 10, 2015',    'duration' => '10m 59',      'filename' => 'tbbs53.mp3',    'title' => 'I Want You In Web Applications',],
            ['episode_number' => 54, 'date' => 'December 15, 2015',    'duration' => '12m 01s',     'filename' => 'tbbs54.mp3',    'title' => 'Family Of Clients',],
            ['episode_number' => 55, 'date' => 'February 18, 2016',    'duration' => '17m 57s',     'filename' => 'tbbs55.mp3',    'title' => 'Endearing Enduring Clients',],
            ['episode_number' => 56, 'date' => 'March 15, 2016',       'duration' => '9m 14s',      'filename' => 'tbbs56.mp3',    'title' => 'What The Laravel Framework Means To Me',],
            ['episode_number' => 57, 'date' => 'May 12, 2016',         'duration' => '5m 18s',      'filename' => 'tbbs57.mp3',    'title' => 'On Hiatus',],           

        ];
    }
}