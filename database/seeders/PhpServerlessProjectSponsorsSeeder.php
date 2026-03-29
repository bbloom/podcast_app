<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PhpServerlessProjectSponsorsSeeder extends Seeder
{
    /**
     * Seed the phpserverlessproject_sponsors table with real sponsor data.
     */
    public function run(): void
    {
        $sponsors = [

            [
                'full_name'               => 'Ken Dryden',
                'image_url'               => 'https://i.ebayimg.com/images/g/qT0AAOSweOFlLguN/s-l1200.jpg',
                'image_thumbnail_url'     => 'https://i.ebayimg.com/images/g/qT0AAOSweOFlLguN/s-l1200.jpg',
                'profile_full'            => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
                'profile_short'           => 'Lorem ipsum odor amet, consectetuer adipiscing elit. Diam risus molestie donec augue fames semper facilisi maximus. Ante urna finibus condimentum curabitur tortor auctor neque sodales eu.',
                'link_to_sponsor_website' => 'https://www.nhl.com/canadiens/',
                'email_address'           => 'goal@mtl.nhl',
                'umbrella_sponsor'        => true,
                'basecamp_sponsor'        => false,
                'restream_sponsor'        => false,
                'former_sponsor'          => false,
                'internal_comment'        => 'Lorem ipsum odor amet, consectetuer adipiscing elit. Enim urna accumsan libero elit nascetur, lacinia nostra felis ligula.',
                'enabled'                 => true,
            ],

            [
                'full_name'               => 'Phil Esposito',
                'image_url'               => 'https://cdn.shopify.com/s/files/1/0062/7808/6703/products/TonyEspositoandPhilEsposito1972SummitSeriesDualAutographed8x10Photo.jpg?v=1652473737',
                'image_thumbnail_url'     => 'https://cdn.shopify.com/s/files/1/0062/7808/6703/products/TonyEspositoandPhilEsposito1972SummitSeriesDualAutographed8x10Photo.jpg?v=1652473737',
                'profile_full'            => 'Lorem ipsum odor amet, consectetuer adipiscing elit. Ad tellus est dictum proin odio auctor auctor facilisi. Dapibus felis amet gravida viverra imperdiet dolor a. Tempor metus sit feugiat vehicula vel ornare quam. Blandit ridiculus sollicitudin etiam elit hendrerit vivamus mattis eu praesent. Curabitur vitae nec arcu ut eros adipiscing metus.',
                'profile_short'           => 'Lorem ipsum odor amet, consectetuer adipiscing elit. Pretium dolor integer non aenean tristique rhoncus praesent. Eget ridiculus curae taciti magnis ultricies. Tortor sagittis a dui lobortis diam volutpat. Imperdiet primis mollis morbi congue ultrici',
                'link_to_sponsor_website' => 'https://www.thecanadianencyclopedia.ca/en/article/1972-canada-soviet-hockey-series',
                'email_address'           => 'summit@hockey.nhl',
                'umbrella_sponsor'        => false,
                'basecamp_sponsor'        => true,
                'restream_sponsor'        => false,
                'former_sponsor'          => false,
                'internal_comment'        => 'Lorem ipsum odor amet, consectetuer adipiscing elit. Pretium dolor integer non aenean tristique rhoncus praesent.',
                'enabled'                 => false,
            ],

            [
                'full_name'               => 'Bobby Orr',
                'image_url'               => 'https://www.nhltraderumor.com/wp-content/uploads/2017/05/bobby-orr-1970-stanley-cup-winning-goal.jpg',
                'image_thumbnail_url'     => 'https://www.nhltraderumor.com/wp-content/uploads/2017/05/bobby-orr-1970-stanley-cup-winning-goal.jpg',
                'profile_full'            => 'Lorem ipsum odor amet, consectetuer adipiscing elit. Ad tellus est dictum proin odio auctor auctor facilisi. Dapibus felis amet gravida viverra imperdiet dolor a. Tempor metus sit feugiat vehicula vel ornare quam. Blandit ridiculus sollicitudin etiam elit hendrerit vivamus mattis eu praesent. Curabitur vitae nec arcu ut eros adipiscing metus.',
                'profile_short'           => 'Lorem ipsum odor amet, consectetuer adipiscing elit. Ad tellus est dictum proin odio auctor auctor facilisi. Dapibus felis amet gravida viverra imperdiet dolor a. Tempor metus sit feugiat vehicula vel ornare quam. Blandit ridiculus sollicitudin etiam',
                'link_to_sponsor_website' => null,
                'email_address'           => 'orr@nhl.com',
                'umbrella_sponsor'        => false,
                'basecamp_sponsor'        => false,
                'restream_sponsor'        => true,
                'former_sponsor'          => false,
                'internal_comment'        => null,
                'enabled'                 => false,
            ],

            [
                'full_name'               => 'Vladislav Tretiak',
                'image_url'               => 'https://media.gettyimages.com/id/81340971/photo/1972-summit-series-soviet-union-v-canada.jpg?s=1024x1024&w=gi&k=20&c=63OY2Pc6Mamir6yG8cVCjvOvvuQDLyFwHeMu9UEeboU=',
                'image_thumbnail_url'     => 'https://media.gettyimages.com/id/81340971/photo/1972-summit-series-soviet-union-v-canada.jpg?s=1024x1024&w=gi&k=20&c=63OY2Pc6Mamir6yG8cVCjvOvvuQDLyFwHeMu9UEeboU=',
                'profile_full'            => 'Lorem ipsum odor amet, consectetuer adipiscing elit. Ad tellus est dictum proin odio auctor auctor facilisi. Dapibus felis amet gravida viverra imperdiet dolor a. Tempor metus sit feugiat vehicula vel ornare quam. Blandit ridiculus sollicitudin etiam elit hendrerit vivamus mattis eu praesent. Curabitur vitae nec arcu ut eros adipiscing metus.',
                'profile_short'           => 'Lorem ipsum odor amet, consectetuer adipiscing elit. Ad tellus est dictum proin odio auctor auctor facilisi. Dapibus felis amet gravida viverra imperdiet dolor a. Tempor metus sit feugiat vehicula vel ornare quam. Blandit ridiculus sollicitudin etiam',
                'link_to_sponsor_website' => 'https://en.wikipedia.org/wiki/Vladislav_Tretiak',
                'email_address'           => 'vlad@summit.com',
                'umbrella_sponsor'        => true,
                'basecamp_sponsor'        => false,
                'restream_sponsor'        => false,
                'former_sponsor'          => false,
                'internal_comment'        => null,
                'enabled'                 => false,
            ],

            [
                'full_name'               => 'Guy LaFleur',
                'image_url'               => 'https://images.squarespace-cdn.com/content/v1/5fe0d37c5278c73004b88f08/1611267985689-4JFWWQQN3Q3DCZ286P31/Guy+Lafleur+copy.jpg',
                'image_thumbnail_url'     => 'https://images.squarespace-cdn.com/content/v1/5fe0d37c5278c73004b88f08/1611267985689-4JFWWQQN3Q3DCZ286P31/Guy+Lafleur+copy.jpg',
                'profile_full'            => 'Lorem ipsum odor amet, consectetuer adipiscing elit. Viverra mollis natoque efficitur velit fusce proin mi. Rhoncus mus rhoncus id maecenas leo efficitur. Nec himenaeos faucibus mauris litora nisl fermentum viverra phasellus. Velit inceptos adipiscing et nisl nascetur ullamcorper ultrices per turpis. Feugiat diam aliquam hac ad condimentum vulputate. Morbi habitasse non interdum id elementum nisl proin. Porttitor donec dolor, a vitae cubilia inceptos at aliquam? Blandit viverra dui faucibus, torquent condimentum pharetra.',
                'profile_short'           => 'Lorem ipsum odor amet, consectetuer adipiscing elit. Viverra mollis natoque efficitur velit fusce proin mi. Rhoncus mus rhoncus id maecenas leo efficitur. Nec himenaeos faucibus mauris litora nisl fermentum viverra phasellus. Velit inceptos adipiscin',
                'link_to_sponsor_website' => null,
                'email_address'           => 'guy@mtl.ca',
                'umbrella_sponsor'        => true,
                'basecamp_sponsor'        => false,
                'restream_sponsor'        => false,
                'former_sponsor'          => false,
                'internal_comment'        => null,
                'enabled'                 => false,
            ],

            [
                'full_name'               => 'Luke Galea',
                'image_url'               => 'https://sponsors-images.bobbloompodcasts.com/luke_galea_1270x1800.jpg',
                'image_thumbnail_url'     => 'https://sponsors-images.bobbloompodcasts.com/luke_galea_1270x1800.jpg',
                'profile_full'            => "Luke Galea is a veteran technology leader who began coding professionally during the dot-com boom. While Ruby, Elixir, and Erlang are his tools of choice, PHP has remained a constant thread throughout his two-decade career spanning healthcare, dating, nutrition coaching, education, and workforce management.\n\nHis journey includes scaling high volume consumer facing sites HotOrNot and Ashley Madison where PHP powered the core infrastructure. Even when working with other technologies, Luke has consistently leveraged PHP for marketing technology, community forums, and developer resources.\n\nA committed community builder, he founded Toronto's Erlang user group and actively supports the Toronto Elixir and GTA PHP communities.\n\nCurrently focused on leadership coaching and technology advisory, Luke loves solving hard problems with smart people. Call him if you want to riff on something awesome.",
                'profile_short'           => 'Luke Galea is a veteran technology leader who began coding professionally during the dot-com boom. While Ruby, Elixir, and Erlang are his tools of choice, PHP has remained a constant thread throughout his two-decade career spanning healthcare...',
                'link_to_sponsor_website' => 'https://bit.ly/LukeGalea',
                'email_address'           => 'luke@ideaforge.org',
                'umbrella_sponsor'        => false,
                'basecamp_sponsor'        => false,
                'restream_sponsor'        => true,
                'former_sponsor'          => false,
                'internal_comment'        => 'The LinkedIn link (www.linkedin.com/in/luke-galea) requires a login. The shortcut (bit.ly/LukeGalea) does not.',
                'enabled'                 => true,
            ],

            [
                'full_name'               => 'Tolga Ercan',
                'image_url'               => 'https://sponsors-images.bobbloompodcasts.com/tolga_ercan_345x418.jpg',
                'image_thumbnail_url'     => 'https://sponsors-images.bobbloompodcasts.com/tolga_ercan_345x418.jpg',
                'profile_full'            => "Tolga Ercan is an accomplished technology executive with over two decades of experience leading high-performing engineering organizations across SaaS, fintech, and consumer technology sectors. He currently serves as Director of Engineering at Vetster, a veterinary telehealth platform redefining access to pet care through innovative digital solutions.\n\nTolga has built a career scaling engineering teams, driving cloud migration strategies, and delivering resilient, scalable systems. His leadership experience includes senior roles at Instagram, Edmunds, and early-stage startups, where he has consistently championed technical excellence, operational efficiency, and cultural growth. He has also leveraged technologies such as Laravel to build and scale modern, customer-facing applications in startup environments, applying best practices in software architecture and agile development.\n\nAs a supporter of the open source community, Tolga believes in the importance of open innovation and actively backs initiatives that advance software transparency, interoperability, and access. He is committed to fostering the future of technology through mentorship, organizational leadership, and community engagement.",
                'profile_short'           => null,
                'link_to_sponsor_website' => 'https://ca.linkedin.com/in/tolga-ercan',
                'email_address'           => 'tolga@vetster.com',
                'umbrella_sponsor'        => false,
                'basecamp_sponsor'        => true,
                'restream_sponsor'        => false,
                'former_sponsor'          => false,
                'internal_comment'        => 'Tolga Ercan has a long history of work experience, starting in 1998 as a Consultant at Aguila Consulting Group.',
                'enabled'                 => true,
            ],

        ];

        foreach ($sponsors as $sponsor) {
            DB::table('phpserverlessproject_sponsors')->insertOrIgnore(array_merge($sponsor, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}