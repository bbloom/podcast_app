# Legacy Video Methods

These methods are from the original application. Kept here for reference in case
they are useful in the future.

```php
private function getYoutubeEmbedUrl($request)
{
    return 'https://www.youtube.com/embed/NEED-THE-YOUTUBE-VIDEOs-ID';
}


private function getAmazonS3Url($request, $slug)
{
    //   https://videos-production-lasalle.s3.ca-central-1.amazonaws.com/php-serverless-video-series/2022nov11_update.mov

    $base            = 'https://videos-production-lasalle.s3.ca-central-1.amazonaws.com';
    $video_show      = $this->getVideoShowByID($request->input('video_show_id'));
    $video_show_slug = $video_show->slug;

    return $base . '/' . $video_show_slug . '/' . $slug . '.mp4';
}


private function getWebsiteContent($request)
{
    return $this->getDescription($request);
}


private function getWebsiteExcerpt($request)
{
    return $this->getWebsiteMetaDescription($request);
}


private function getWebsiteMetaDescription($request)
{
    $description = trim($request->input('description'));

    return substr($description, 0, 255);
}


private function getWebsiteEpisodeNotes($request)
{
    return NULL;
}


private function getWebsiteAttribution($request)
{
    $attributions = '<ul><li><a href="https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)">https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)</a></li><li><a href="https://imslp.org/wiki/Symphony_No.3%2C_Op.55_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.3%2C_Op.55_(Beethoven%2C_Ludwig_van)</a></li></ul>';

    return $attributions;
}


private function getWebsiteFeaturedImage($request)
{
    $base = 'https://videos-production-lasalle.bobbloompodcasts.com/featured_images/';

    if ($request->input('video_show_id') == 1) {
        $subprefix = 'php_serverless_project/';
    } else {
        $subprefix = 'local-toronto-php-meet-ups/';
    }

    $slug = $this->getSlug($request);

    return $base . $subprefix . $slug . '.png';
}


private function getWebsitePublishOn($request)
{
    return $this->getScheduled_date($request);
}


private function getWebsiteEnabled($request)
{
    return 0;
}


private function getPreviewInFrontend($request)
{
    return 0;
}


private function getUuid($request)
{
    return 'Make in Create Video Episode wizard';
}
```


And, this one too:

```
private function getYoutubeDescription($request, $slug)
    {
        $installed_domain_id    = $this->getTHEInstalledDomainId($request);
        $installed_domain_title = $this->getInstalledDomainTitleById($installed_domain_id);
        $title                  = $this->getYoutubeTitle($request);
        $description            = $this->getDescription($request);

        $text_meetups = <<<TEXT
        $title 


        Every so often, I get in front of the camera with my notes, to talk to you about what is happening with our local Toronto PHP Groups. 

        In this video: $description 
        
        Our Local Toronto PHP Groups:
        • https://github.com/local-toronto-php-groups
        • Greater Toronto Area PHP meet-up group: https://gtaphp.org
        • Laravel Toronto: https://laraveltoronto.ca
        • York Region PHP meet-up group: http://yorkregionphp.ca

        Our Amazing Corporate Hosts:
        ✓ https://7shifts.com 
        ✓ https://getMaple.ca
        ✓ https://ytz.com
        ✓ https://vetster.com
         
        A special thank you to 7shifts for sponsoring our monthly meetup.com subscription!

        This video's web page: 
        • https://$installed_domain_title/video/$slug

        Music Credits:
        🎵 https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)
        🎵 https://imslp.org/wiki/Symphony_No.3%2C_Op.55_(Beethoven%2C_Ludwig_van) 

        TEXT;



        $text_phpserverlessproject = <<<TEXTPROJECT
        $title


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
        • This video's web page: https://phpserverlessproject.com/video/php-serverless-project-update-march-29-2025
        • Project site:  https://PHPServerlessProject.com
        • Commentary podcast:  https://BobBloomShow.com
        • Interviews podcast:  https://BobBloomInterviews.com
        • News podcast:  https://PHPServerlessNews.com
        • Profiles podcast:  https://PHPServerlessProfiles.com


        ---
        🎵 https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)
        🎵 https://imslp.org/wiki/Symphony_No.3%2C_Op.55_(Beethoven%2C_Ludwig_van)
        

        ---
        DISCLAIMER: 
        • AWS is pay-per-use and is made to scale. Be careful!
        • It is your responsibility to understand your specific billing circumstances.
        • Throttling, thresh-hold notifications, discounts, free tiers, and other mechanisms are not perfect shields against surprising stomach-churning AWS invoices.
        • My info may be inaccurate, incomplete, out-of-date, or erroneous.
        • I make no efforts to update info found to be inaccurate,  incomplete, out-of-date, or erroneous.
        • This video is intended to convey general information only. 
        • Some of the software I go over in my videos gives you unfettered access to setting things up in AWS. Take your time going through the commands. Focus on what you are doing! Mistakes can be costly.

        TEXTPROJECT;

        if (strtolower($installed_domain_title) == 'bobbloom.me') {
            $text = $text_meetups;
        } else {
            $text = $text_phpserverlessproject;
        }

        return $text;
    }

    private function getYoutubeChapters($request)
    {
        $text = <<<TEXT
        0:00 - Welcome[[br]]
        0:11 - Opening[[br]]
        2:44 - Closing[[br]]
        TEXT;

        return $text;
    }
```