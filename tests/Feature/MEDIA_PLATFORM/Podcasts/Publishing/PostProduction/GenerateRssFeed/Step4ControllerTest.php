<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\GenerateRssFeed;

// =============================================================================
// Step4ControllerTest — DEPRECATED
//
// This test class is no longer in use as of the RSS Pipeline Reorder.
// It is retained here only to make the gap in the step numbering
// self-explanatory, mirroring Step4Controller.php which is similarly kept.
//
// WHAT STEP 4 TESTED:
//   These tests covered the staging validation page — the page shown after
//   Step 3 generated and uploaded the RSS XML to the work-in-progress S3
//   staging bucket. The page displayed the public staging URL and links to
//   external validators (Cast Feed Validator, Podbase, Podcastpage) so the
//   user could validate the feed before promoting it to the live bucket in
//   Step 5.
//
// WHY THESE TESTS WERE RETIRED:
//   Step 4 was removed as part of the RSS Pipeline Reorder because staging
//   validation was unreliable — the episode's website page did not yet exist
//   at that point in the pipeline, causing validators to report false 404
//   errors on <link> and <itunes:link> tags.
//
//   Validation now happens in LiveValidationController, after Step 5 has
//   promoted the feed to the live S3 bucket and the static site build has
//   completed. See LiveValidationControllerTest for the replacement coverage.
//
// DO NOT RESTORE THESE TESTS without also restoring Step4Controller.php
// and its routes in generate_rss_feed.php, and reverting the Pipeline Reorder.
//
// Path: tests/Feature/MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/GenerateRssFeed/
// =============================================================================

/**
 * @deprecated Retired as part of the RSS Pipeline Reorder.
 *             See class docblock above for full context.
 *             Replaced by: LiveValidationControllerTest
 */
class Step4ControllerTest
{
    // This class is intentionally empty.
    // See the file header comment for the full explanation.
}