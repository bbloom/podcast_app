<?php

// =============================================================================
// Step4Controller — DEPRECATED
//
// This controller is no longer in use as of the RSS Pipeline Reorder.
// Its routes have been removed from generate_rss_feed.php. It is retained
// here only to make the gap in the step numbering self-explanatory.
//
// WHAT STEP 4 WAS:
//   Step 4 was the staging validation page. After Step 3 generated the RSS XML
//   and uploaded it to the podcast-work-in-progress S3 staging bucket, Step 4
//   displayed the public staging URL so the user could paste it into external
//   validators (Cast Feed Validator, Podbase, Podcastpage) before promoting to
//   the live bucket in Step 5.
//
// WHY IT WAS REMOVED:
//   Validating against the staging URL was unreliable because the episode's
//   website page did not yet exist — the static site build had not run.
//   Validators that follow <link> and <itunes:link> tags would report 404 errors
//   that were not real problems with the feed.
//
//   In the reordered pipeline, the website is published and the static site
//   build completes BEFORE RSS generation begins. Validation now happens after
//   Step 5 promotes the feed to the live S3 bucket, at which point all URLs in
//   the feed resolve correctly. This is handled by LiveValidationController.
//
// PIPELINE BEFORE REORDER:
//   Step 3 (generate + stage) → Step 4 (validate staging) → Step 5 (promote)
//
// PIPELINE AFTER REORDER:
//   Step 3 (generate + stage) → Step 5 (promote to live S3)
//            → Live Validation (validate live S3 URL) → Promote to R2
//
// DO NOT RESTORE THIS CONTROLLER without also restoring the routes in
// generate_rss_feed.php and reverting the RSS Pipeline Reorder changes.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/GenerateRssFeed/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers;

/**
 * @deprecated Removed as part of the RSS Pipeline Reorder.
 *             See class docblock above for full context.
 */
class Step4Controller
{
    // This class is intentionally empty.
    // See the file header comment for the full explanation.
}