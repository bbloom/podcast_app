<?php

// =============================================================================
// Seeder: Podcast_episodes2Seeder
//
// Seeds podcast episodes from the second SQL dump (IDs 20–29).
// IDs are preserved exactly — foreign keys in other tables depend on them.
// Fields not present in the new schema are omitted.
// user_id is set to 1 for all records.
// draft is set to null for all records.
// =============================================================================

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Podcast_episodes1ASeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Disable foreign key checks so we can insert with explicit IDs freely.
        DB::statement('SET session_replication_role = replica;');

        $episodes = [

            // ── ID 20 ─────────────────────────────────────────────────────────
            [
                'id'                                => 20,
                'podcast_show_id'                   => 3,
                'user_id'                           => 1,
                'status'                            => 'published',
                'title'                             => '#9 - Lambda PHP Runtimes: Internally Yours',
                'slug'                              => 'bob-bloom-show-ep9-lambda-php-runtimes-internally-yours',
                'scheduled_date'                    => '2022-11-01',
                'draft'                             => null,
                'raw_input_audio_filename'          => 'bobbloomshow9.wav',
                'itunes_title_tag'                  => '#9 - Lambda PHP Runtimes: Internally Yours',
                'itunes_enclosure_url'              => 'https://tbbs-audio.bobbloompodcasts.com/bobbloomshow9.mp3',
                'itunes_enclosure_length'           => '10010171',
                'itunes_enclosure_type'             => 'audio/mpeg',
                'itunes_guid'                       => 'JQ0qaciR-YRUk-JmYU-sATf-iruY9S0f5k48',
                'itunes_pubdate'                    => '2022-11-01 00:00:00',
                'itunes_description'                => "Diving down the Lambda PHP Runtime Rabbit Hole brought me square into something that I have successfully avoided in my two decades of enjoying PHP as my primary language of choice: PHP Internals. \r\n\r\nAnd not just avoided, but consciously and actively avoided. But, now, there is really no way to avoid getting involved in the PHP Internals, in some way, when it comes to doing your own PHP Runtimes for Lambda. And, now that I've broke my own barrier to looking at PHP Internals, I am the better dev for doing it. Even more so, I feel I am getting a more intimate understanding of Lambda as a result.",
                'itunes_duration'                   => '00:11:54',
                'itunes_link'                       => 'https://bobbloomshow.com/episode/bob-bloom-show-ep9-lambda-php-runtimes-internally-yours',
                'itunes_image'                      => null,
                'itunes_explicit'                   => false,
                'itunes_itunestitle_tag'            => 'Lambda PHP Runtimes: Internally Yours',
                'itunes_episode'                    => 9,
                'itunes_season'                     => 0,
                'itunes_episode_type'               => 'full',
                'itunes_block'                      => false,
                'itunes_summary'                    => "Diving down the Lambda PHP Runtime Rabbit Hole brought me square into something that I have successfully avoided in my two decades of enjoying PHP as my primary language of choice: PHP Internals. \r\n\r\nAnd not just avoided, but consciously and actively avoided. But, now, there is really no way to avoid getting involved in the PHP Internals, in some way, when it comes to doing your own PHP Runtimes for Lambda. And, now that I've broke my own barrier to looking at PHP Internals, I am the better dev for doing it. Even more so, I feel I am getting a more intimate understanding of Lambda as a result.",
                'itunes_subtitle'                   => 'Lambda PHP Runtimes: Internally Yours',
                'itunes_content_encoded'            => null,
                'rss_feed_enabled'                  => true,
                'website_content'                   => '<div>Diving down the Lambda PHP Runtime Rabbit Hole brought me square into something that I have successfully avoided in my two decades of enjoying PHP as my primary language of choice: PHP Internals. And not just avoided, but consciously and actively avoided. But, now, there is really no way to avoid getting involved in the PHP Internals, in some way, when it comes to doing your own PHP Runtimes for Lambda. And, now that I\'ve broke my own barrier to looking at PHP Internals, I am the better dev for doing it. Even more so, I feel I am getting a more intimate understanding of Lambda as a result.</div>',
                'website_excerpt'                   => 'Diving down the Lambda PHP Runtime Rabbit Hole brought me square into something that I have successfully avoided in my two decades of enjoying PHP as my primary language of choice: PHP Internals. And not just avoided, but consciously and actively',
                'website_meta_description'          => 'Diving down the Lambda PHP Runtime Rabbit Hole brought me square into something that I have successfully avoided in my two decades of enjoying PHP as my primary language of choice: PHP Internals. And not just avoided, but consciously and actively avoi',
                'website_episode_notes'             => '',
                'website_attribution'               => '<div>Beethoven\'s Symphony No. 1, Op.21:</div><ul><li><a href="https://creativecommons.org/licenses/by/4.0/legalcode">Creative Commons Attribution 4.0</a>&nbsp;</li><li><a href="https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)">https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)</a></li></ul><div><br>Beethoven\'s Symphony No.9, Op.125:</div><ul><li><a href="https://creativecommons.org/licenses/by-sa/4.0/legalcode">Creative Commons Attribution-NoDerivs 4.0&nbsp;</a></li><li><a href="https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)</a></li></ul>',
                'website_featured_image'            => null,
                'website_publish_on'                => '2022-11-01',
                'website_enabled'                   => true,
                'created_at'                        => '2022-10-19 19:14:03',
                'updated_at'                        => '2024-11-08 01:56:35',
            ],

            // ── ID 21 ─────────────────────────────────────────────────────────
            [
                'id'                                => 21,
                'podcast_show_id'                   => 2,
                'user_id'                           => 1,
                'status'                            => 'published',
                'title'                             => '#10 - Interview Ready',
                'slug'                              => 'lasalle-software-news-ep10-interview-ready',
                'scheduled_date'                    => '2022-12-01',
                'draft'                             => null,
                'raw_input_audio_filename'          => 'lasallesoftwarenews10.wav',
                'itunes_title_tag'                  => '#10 - Interview Ready',
                'itunes_enclosure_url'              => 'https://pub-7c252511bd8c494f95403396af344af7.r2.dev/lasallesoftwarenews10.mp3',
                'itunes_enclosure_length'           => '6634131',
                'itunes_enclosure_type'             => 'audio/mpeg',
                'itunes_guid'                       => '4X4ius1F-dhDD-AHTf-Thcs-K59AFwKUojlX',
                'itunes_pubdate'                    => '2022-12-01 00:00:00',
                'itunes_description'                => 'To help with guest management for my Interviews podcast, I decided to sign up for Hey.com in lieu of building custom email features. I created two new, small, private packages for guest statuses for my podcast admin.',
                'itunes_duration'                   => '00:07:54',
                'itunes_link'                       => 'https://lasallesoftwarenews.com/episode/lasalle-software-news-ep10-interview-ready',
                'itunes_image'                      => null,
                'itunes_explicit'                   => false,
                'itunes_itunestitle_tag'            => 'Interview Ready',
                'itunes_episode'                    => 10,
                'itunes_season'                     => 0,
                'itunes_episode_type'               => 'full',
                'itunes_block'                      => false,
                'itunes_summary'                    => 'To help with guest management for my Interviews podcast, I decided to sign up for Hey.com in lieu of building custom email features. I created two new, small, private packages for guest statuses for my podcast admin.',
                'itunes_subtitle'                   => 'Interview Ready',
                'itunes_content_encoded'            => null,
                'rss_feed_enabled'                  => true,
                'website_content'                   => 'To help with guest management for my Interviews podcast, I decided to sign up for Hey.com in lieu of building custom email features. I created two new, small, private packages for guest statuses for my podcast admin.',
                'website_excerpt'                   => 'To help with guest management for my Interviews podcast, I decided to sign up for Hey.com in lieu of building custom email features. I created two new, small, private packages for guest statuses for my podcast admin.',
                'website_meta_description'          => 'To help with guest management for my Interviews podcast, I decided to sign up for Hey.com in lieu of building custom email features. I created two new, small, private packages for guest statuses for my podcast admin.',
                'website_episode_notes'             => '',
                'website_attribution'               => '<div>Beethoven\'s Symphony No.3 in E Flat Major "Eroica", Op.55:</div><ul><li><a href="https://creativecommons.org/licenses/by/3.0/legalcode">Creative Commons Attribution 3.0</a>&nbsp;</li><li><a href="https://imslp.org/wiki/Symphony_No.3%2C_Op.55_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.3%2C_Op.55_(Beethoven%2C_Ludwig_van)</a></li></ul><div><br>Beethoven\'s Symphony No.9, Op.125:</div><ul><li><a href="https://creativecommons.org/licenses/by-sa/4.0/legalcode">Creative Commons Attribution-NoDerivs 4.0&nbsp;</a></li><li><a href="https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)</a></li></ul>',
                'website_featured_image'            => null,
                'website_publish_on'                => '2022-12-01',
                'website_enabled'                   => true,
                'created_at'                        => '2022-12-01 01:43:25',
                'updated_at'                        => '2022-12-01 01:47:51',
            ],

            // ── ID 23 ─────────────────────────────────────────────────────────
            // Note: ID 22 does not exist in the source data (intentional gap).
            [
                'id'                                => 23,
                'podcast_show_id'                   => 4,
                'user_id'                           => 1,
                'status'                            => 'published',
                'title'                             => '#2 - Help With Guest Management',
                'slug'                              => 'bob-bloom-interviews-show-ep2-help-with-guest-management',
                'scheduled_date'                    => '2022-12-01',
                'draft'                             => null,
                'raw_input_audio_filename'          => 'bobbloominterviews2.wav',
                'itunes_title_tag'                  => '#2 - Help With Guest Management',
                'itunes_enclosure_url'              => 'https://tbbi-audio.bobbloompodcasts.com/bobbloominterviews2.mp3',
                'itunes_enclosure_length'           => '5552701',
                'itunes_enclosure_type'             => 'audio/mpeg',
                'itunes_guid'                       => 'DQuyWSU0-UGPz-9Nbc-aZBU-cgC2KsARBgzn',
                'itunes_pubdate'                    => '2022-12-01 00:00:00',
                'itunes_description'                => 'I set up the help with guest management that I am looking for. I signed up for hey.com for email management that will help me correspond with interview guests. And, by adding guest status database tables to my podcast admin so I know where I am at with my guests. This is a big hump to get over, and now I am ready to do live, but dry run, interviews.',
                'itunes_duration'                   => '00:06:36',
                'itunes_link'                       => 'https://bobbloominterviews.com/episode/bob-bloom-interviews-show-ep2-help-with-guest-management',
                'itunes_image'                      => null,
                'itunes_explicit'                   => false,
                'itunes_itunestitle_tag'            => 'Help With Guest Management',
                'itunes_episode'                    => 2,
                'itunes_season'                     => 0,
                'itunes_episode_type'               => 'full',
                'itunes_block'                      => false,
                'itunes_summary'                    => 'I set up the help with guest management that I am looking for. I signed up for hey.com for email management that will help me correspond with interview guests. And, by adding guest status database tables to my podcast admin so I know where I am at with my guests. This is a big hump to get over, and now I am ready to do live, but dry run, interviews.',
                'itunes_subtitle'                   => 'Help With Guest Management',
                'itunes_content_encoded'            => null,
                'rss_feed_enabled'                  => true,
                'website_content'                   => 'I set up the help with guest management that I am looking for. I signed up for hey.com for email management that will help me correspond with interview guests. And, by adding guest status database tables to my podcast admin so I know where I am at with my guests. This is a big hump to get over, and now I am ready to do live, but dry run, interviews.',
                'website_excerpt'                   => 'I set up the help with guest management that I am looking for. I signed up for hey.com for email management that will help me correspond with interview guests. And, by adding guest status database tables to my podcast admin so I know where I am at wi',
                'website_meta_description'          => 'I set up the help with guest management that I am looking for. I signed up for hey.com for email management that will help me correspond with interview guests. And, by adding guest status database tables to my podcast admin so I know where I am at with my',
                'website_episode_notes'             => '',
                'website_attribution'               => '<div>Beethoven\'s Symphony No. 1, Op.21:</div><ul><li><a href="https://creativecommons.org/licenses/by/4.0/legalcode">Creative Commons Attribution 4.0</a>&nbsp;</li><li><a href="https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)">https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)</a></li></ul><div><br>Beethoven\'s Symphony No.9, Op.125:</div><ul><li><a href="https://creativecommons.org/licenses/by-sa/4.0/legalcode">Creative Commons Attribution-NoDerivs 4.0&nbsp;</a></li><li><a href="https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)</a></li></ul>',
                'website_featured_image'            => null,
                'website_publish_on'                => '2022-12-01',
                'website_enabled'                   => true,
                'created_at'                        => '2022-12-01 03:38:04',
                'updated_at'                        => '2024-11-08 01:41:13',
            ],

            // ── ID 24 ─────────────────────────────────────────────────────────
            [
                'id'                                => 24,
                'podcast_show_id'                   => 3,
                'user_id'                           => 1,
                'status'                            => 'published',
                'title'                             => '#10 - Lambda PHP Runtimes: Bootstrapping APIs',
                'slug'                              => 'bob-bloom-show-ep10-lambda-php-runtimes-bootstrapping-apis',
                'scheduled_date'                    => '2022-12-01',
                'draft'                             => null,
                'raw_input_audio_filename'          => 'bobbloomshow10.wav',
                'itunes_title_tag'                  => '#10 - Lambda PHP Runtimes: Bootstrapping APIs',
                'itunes_enclosure_url'              => 'https://tbbs-audio.bobbloompodcasts.com/bobbloomshow10.mp3',
                'itunes_enclosure_length'           => '9391389',
                'itunes_enclosure_type'             => 'audio/mpeg',
                'itunes_guid'                       => 'qeiasfyb-Jo04-xAev-HhIm-8pqfEhfCTOi1',
                'itunes_pubdate'                    => '2022-12-01 00:00:00',
                'itunes_description'                => "What is Lambda's Runtime API's \"bootstrap\" file?\r\n\r\nTo get a first-hand feel for what this vital file really is, I messed around with it. There is a lot of API action going on. And, surprisingly, there is access to the actual Lambda folder and file structure. There appears to be no actual bootstrap stuff going on, in the traditional server sense of the word.",
                'itunes_duration'                   => '00:11:11',
                'itunes_link'                       => 'https://bobbloomshow.com/episode/bob-bloom-show-ep10-lambda-php-runtimes-bootstrapping-apis',
                'itunes_image'                      => null,
                'itunes_explicit'                   => false,
                'itunes_itunestitle_tag'            => 'Lambda PHP Runtimes: Bootstrapping APIs',
                'itunes_episode'                    => 10,
                'itunes_season'                     => 0,
                'itunes_episode_type'               => 'full',
                'itunes_block'                      => false,
                'itunes_summary'                    => "What is Lambda's Runtime API's \"bootstrap\" file?\r\n\r\nTo get a first-hand feel for what this vital file really is, I messed around with it. There is a lot of API action going on. And, surprisingly, there is access to the actual Lambda folder and file structure. There appears to be no actual bootstrap stuff going on, in the traditional server sense of the word.",
                'itunes_subtitle'                   => 'Lambda PHP Runtimes: Bootstrapping APIs',
                'itunes_content_encoded'            => null,
                'rss_feed_enabled'                  => true,
                'website_content'                   => "What is Lambda's Runtime API's \"bootstrap\" file?\r\n\r\nTo get a first-hand feel for what this vital file really is, I messed around with it. There is a lot of API action going on. And, surprisingly, there is access to the actual Lambda folder and file structure. There appears to be no actual bootstrap stuff going on, in the traditional server sense of the word.",
                'website_excerpt'                   => 'What is Lambda\'s Runtime API\'s "bootstrap" file? To get a first-hand feel for what this vital file really is, I messed around with it. There is a lot of API action going on. And, surprisingly, there is access to the actual Lambda folder and file s',
                'website_meta_description'          => 'What is Lambda\'s Runtime API\'s "bootstrap" file? To get a first-hand feel for what this vital file really is, I messed around with it. There is a lot of API action going on. And, surprisingly, there is access to the actual Lambda folder and file struct',
                'website_episode_notes'             => '',
                'website_attribution'               => '<div>Beethoven\'s Symphony No. 1, Op.21:</div><ul><li><a href="https://creativecommons.org/licenses/by/4.0/legalcode">Creative Commons Attribution 4.0</a>&nbsp;</li><li><a href="https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)">https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)</a></li></ul><div><br>Beethoven\'s Symphony No.9, Op.125:</div><ul><li><a href="https://creativecommons.org/licenses/by-sa/4.0/legalcode">Creative Commons Attribution-NoDerivs 4.0&nbsp;</a></li><li><a href="https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)</a></li></ul>',
                'website_featured_image'            => null,
                'website_publish_on'                => '2022-12-01',
                'website_enabled'                   => true,
                'created_at'                        => '2022-12-02 00:58:36',
                'updated_at'                        => '2024-11-08 01:55:04',
            ],

            // ── ID 26 ─────────────────────────────────────────────────────────
            // Note: ID 25 does not exist in the source data (intentional gap).
            [
                'id'                                => 26,
                'podcast_show_id'                   => 2,
                'user_id'                           => 1,
                'status'                            => 'published',
                'title'                             => '#11 - Sponsorship Version Two',
                'slug'                              => 'lasalle-software-news-ep11-sponsorship-version-two',
                'scheduled_date'                    => '2023-01-01',
                'draft'                             => null,
                'raw_input_audio_filename'          => 'lasallesoftwarenews11.wav',
                'itunes_title_tag'                  => '#11 - Sponsorship Version Two',
                'itunes_enclosure_url'              => 'https://pub-7c252511bd8c494f95403396af344af7.r2.dev/lasallesoftwarenews11.mp3',
                'itunes_enclosure_length'           => '8721173',
                'itunes_enclosure_type'             => 'audio/mpeg',
                'itunes_guid'                       => '6TXtIrQh-bqI1-d5xr-k3RQ-GBsTEmCfTGht',
                'itunes_pubdate'                    => '2023-01-01 00:00:00',
                'itunes_description'                => 'I am changing the sponsorship I will seek to support my podcasts, and to support my ongoing PHP Serverless Project efforts.',
                'itunes_duration'                   => '00:09:54',
                'itunes_link'                       => 'https://lasallesoftwarenews.com/episode/lasalle-software-news-ep11-sponsorship-version-two',
                'itunes_image'                      => null,
                'itunes_explicit'                   => false,
                'itunes_itunestitle_tag'            => 'Sponsorship Version Two',
                'itunes_episode'                    => 11,
                'itunes_season'                     => 0,
                'itunes_episode_type'               => 'full',
                'itunes_block'                      => false,
                'itunes_summary'                    => 'I am changing the sponsorship I will seek to support my podcasts, and to support my ongoing PHP Serverless Project efforts.',
                'itunes_subtitle'                   => 'Sponsorship Version Two',
                'itunes_content_encoded'            => null,
                'rss_feed_enabled'                  => true,
                'website_content'                   => 'I am changing the sponsorship I will seek to support my podcasts, and to support my ongoing PHP Serverless Project efforts.',
                'website_excerpt'                   => 'I am changing the sponsorship I will seek to support my podcasts, and to support my ongoing PHP Serverless Project efforts.',
                'website_meta_description'          => 'I am changing the sponsorship I will seek to support my podcasts, and to support my ongoing PHP Serverless Project efforts.',
                'website_episode_notes'             => '',
                'website_attribution'               => '<div>Beethoven\'s Symphony No.3 in E Flat Major "Eroica", Op.55:</div><ul><li><a href="https://creativecommons.org/licenses/by/3.0/legalcode">Creative Commons Attribution 3.0</a>&nbsp;</li><li><a href="https://imslp.org/wiki/Symphony_No.3%2C_Op.55_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.3%2C_Op.55_(Beethoven%2C_Ludwig_van)</a></li></ul><div><br>Beethoven\'s Symphony No.9, Op.125:</div><ul><li><a href="https://creativecommons.org/licenses/by-sa/4.0/legalcode">Creative Commons Attribution-NoDerivs 4.0&nbsp;</a></li><li><a href="https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)</a></li></ul>',
                'website_featured_image'            => null,
                'website_publish_on'                => '2023-01-01',
                'website_enabled'                   => true,
                'created_at'                        => '2023-01-01 17:47:52',
                'updated_at'                        => '2023-01-01 17:47:59',
            ],

            // ── ID 28 ─────────────────────────────────────────────────────────
            // Note: ID 27 does not exist in the source data (intentional gap).
            [
                'id'                                => 28,
                'podcast_show_id'                   => 3,
                'user_id'                           => 1,
                'status'                            => 'published',
                'title'                             => '#11 - Smells Like A Duck, Tastes Like Chicken',
                'slug'                              => 'bob-bloom-show-ep11-smells-like-a-duck-but-tastes-like-chicken',
                'scheduled_date'                    => '2023-01-01',
                'draft'                             => null,
                'raw_input_audio_filename'          => 'bobbloomshow11.wav',
                'itunes_title_tag'                  => '#11 - Smells Like A Duck, Tastes Like Chicken',
                'itunes_enclosure_url'              => 'https://tbbs-audio.bobbloompodcasts.com/bobbloomshow11.mp3',
                'itunes_enclosure_length'           => '7443086',
                'itunes_enclosure_type'             => 'audio/mpeg',
                'itunes_guid'                       => 'WdjwYrZ8-vtOA-TpW0-zO2W-4I4aaXGQdqZP',
                'itunes_pubdate'                    => '2023-01-01 00:00:00',
                'itunes_description'                => 'Lambda is very accommodating to running PHP monolithic apps. The Lambda home page uses server terminology to describe what it is not. It is very logical for PHP devs to assume that Lambda is a server by another name. However, it is still not a server. With more serverless platforms being introduced into the marketplace, it is important to understand exactly what these technologies are, and to understand our own use cases, in order to best match our needs with a platform.',
                'itunes_duration'                   => '00:08:32',
                'itunes_link'                       => 'https://bobbloomshow.com/episode/bob-bloom-show-ep11-smells-like-a-duck-but-tastes-like-chicken',
                'itunes_image'                      => null,
                'itunes_explicit'                   => false,
                'itunes_itunestitle_tag'            => 'Smells Like A Duck, Tastes Like Chicken',
                'itunes_episode'                    => 11,
                'itunes_season'                     => 0,
                'itunes_episode_type'               => 'full',
                'itunes_block'                      => false,
                'itunes_summary'                    => 'Lambda is very accommodating to running PHP monolithic apps. The Lambda home page uses server terminology to describe what it is not. It is very logical for PHP devs to assume that Lambda is a server by another name. However, it is still not a server. With more serverless platforms being introduced into the marketplace, it is important to understand exactly what these technologies are, and to understand our own use cases, in order to best match our needs with a platform.',
                'itunes_subtitle'                   => 'Smells Like A Duck, Tastes Like Chicken',
                'itunes_content_encoded'            => '<div>Lambda is very accommodating to running PHP monolithic apps. The Lambda home page uses server terminology to describe what it is not. It is very logical for PHP devs to assume that Lambda is a server by another name. However, it is still not a server. With more serverless platforms being introduced into the marketplace, it is important to understand exactly what these technologies are, and to understand our own use cases, in order to best match our needs with a platform.</div>',
                'rss_feed_enabled'                  => true,
                'website_content'                   => '<div>Lambda is very accommodating to running PHP monolithic apps. The Lambda home page uses server terminology to describe what it is not. It is very logical for PHP devs to assume that Lambda is a server by another name. However, it is still not a server. With more serverless platforms being introduced into the marketplace, it is important to understand exactly what these technologies are, and to understand our own use cases, in order to best match our needs with a platform.</div>',
                'website_excerpt'                   => 'Lambda is very accommodating to running PHP monolithic apps. The Lambda home page uses server terminology to describe what it is not. It is very logical for PHP devs to assume that Lambda is a server by another name. However, it is still not a server',
                'website_meta_description'          => 'Lambda is very accommodating to running PHP monolithic apps. The Lambda home page uses server terminology to describe what it is not. It is very logical for PHP devs to assume that Lambda is a server by another name. However, it is still not a server. Wit',
                'website_episode_notes'             => '',
                'website_attribution'               => '<div>Beethoven\'s Symphony No. 1, Op.21:</div><ul><li><a href="https://creativecommons.org/licenses/by/4.0/legalcode">Creative Commons Attribution 4.0</a>&nbsp;</li><li><a href="https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)">https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)</a></li></ul><div><br>Beethoven\'s Symphony No.9, Op.125:</div><ul><li><a href="https://creativecommons.org/licenses/by-sa/4.0/legalcode">Creative Commons Attribution-NoDerivs 4.0&nbsp;</a></li><li><a href="https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)</a></li></ul>',
                'website_featured_image'            => null,
                'website_publish_on'                => '2023-01-01',
                'website_enabled'                   => true,
                'created_at'                        => '2023-01-02 01:29:48',
                'updated_at'                        => '2024-11-08 01:55:00',
            ],

            // ── ID 29 ─────────────────────────────────────────────────────────
            [
                'id'                                => 29,
                'podcast_show_id'                   => 4,
                'user_id'                           => 1,
                'status'                            => 'published',
                'title'                             => '#3 - Terms Of Directories',
                'slug'                              => 'bob-bloom-interviews-show-ep3-terms-of-directories',
                'scheduled_date'                    => '2023-01-01',
                'draft'                             => null,
                'raw_input_audio_filename'          => 'bobbloominterviews3.wav',
                'itunes_title_tag'                  => '#3 - Terms of Directories',
                'itunes_enclosure_url'              => 'https://tbbi-audio.bobbloompodcasts.com/bobbloominterviews3.mp3',
                'itunes_enclosure_length'           => '5482216',
                'itunes_enclosure_type'             => 'audio/mpeg',
                'itunes_guid'                       => 'jfu0xV9G-bcYn-Qk83-GrLp-njPHVRNhlsUA',
                'itunes_pubdate'                    => '2023-01-01 00:00:00',
                'itunes_description'                => 'Continuing my updates. I listed my podcasts on a handful of podcast directories. The terms of service were similar, and interesting.',
                'itunes_duration'                   => '00:06:10',
                'itunes_link'                       => 'https://bobbloominterviews.com/episode/bob-bloom-interviews-show-ep3-terms-of-directories',
                'itunes_image'                      => null,
                'itunes_explicit'                   => false,
                'itunes_itunestitle_tag'            => 'Terms of Directories',
                'itunes_episode'                    => 3,
                'itunes_season'                     => 0,
                'itunes_episode_type'               => 'full',
                'itunes_block'                      => false,
                'itunes_summary'                    => 'Continuing my updates. I listed my podcasts on a handful of podcast directories. The terms of service were similar, and interesting.',
                'itunes_subtitle'                   => 'Terms of Directories',
                'itunes_content_encoded'            => null,
                'rss_feed_enabled'                  => true,
                'website_content'                   => 'Continuing my updates. I listed my podcasts on a handful of podcast directories. The terms of service were similar, and interesting.',
                'website_excerpt'                   => 'Continuing my updates. I listed my podcasts on a handful of podcast directories. The terms of service were similar, and interesting.',
                'website_meta_description'          => 'Continuing my updates. I listed my podcasts on a handful of podcast directories. The terms of service were similar, and interesting.',
                'website_episode_notes'             => '',
                'website_attribution'               => '<div>Beethoven\'s Symphony No. 1, Op.21:</div><ul><li><a href="https://creativecommons.org/licenses/by/4.0/legalcode">Creative Commons Attribution 4.0</a>&nbsp;</li><li><a href="https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)">https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)</a></li></ul><div><br>Beethoven\'s Symphony No.9, Op.125:</div><ul><li><a href="https://creativecommons.org/licenses/by-sa/4.0/legalcode">Creative Commons Attribution-NoDerivs 4.0&nbsp;</a></li><li><a href="https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)</a></li></ul>',
                'website_featured_image'            => null,
                'website_publish_on'                => '2023-01-01',
                'website_enabled'                   => true,
                'created_at'                        => '2023-01-02 01:44:47',
                'updated_at'                        => '2024-11-08 01:41:12',
            ],

        ];

        // Insert each episode, skipping any that already exist (idempotent).
        foreach ($episodes as $episode) {
            DB::table('podcast_episodes')->updateOrInsert(
                ['id' => $episode['id']],
                $episode
            );
        }

        // Re-enable foreign key checks.
        DB::statement('SET session_replication_role = DEFAULT;');

        // Advance the PostgreSQL sequence past the highest inserted ID so that
        // future auto-increment inserts do not collide with the seeded IDs.
        DB::statement("SELECT setval('podcast_episodes_id_seq', (SELECT MAX(id) FROM podcast_episodes))");
    }
}