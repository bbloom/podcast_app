<?php

/**
 * Debug route for manually running YoutubeContentProcessor against a
 * specific list_source. No auth required. Remove when done debugging.
 *
 * Usage:
 *   GET /debug/process-youtube?list_source_id=1
 *
 * The list_source_id is the ID from the list_sources table — find it
 * by looking at the list_sources table for the list_id you want to test.
 * You can also override processing_mode on the fly via query string:
 *   GET /debug/process-youtube?list_source_id=1&mode=summary
 *   GET /debug/process-youtube?list_source_id=1&mode=search&terms=bitcoin
 */

use MediaPlatform\Digest\Processing\Youtube\Services\YoutubeContentProcessor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/debug/process-youtube', function (YoutubeContentProcessor $processor) {

    $listSourceId = request('list_source_id');

    if (! $listSourceId) {
        return response()->json(['error' => 'Pass ?list_source_id=X in the query string.'], 400);
    }

    // Load the list_source row
    $listSource = DB::table('list_sources')->find((int) $listSourceId);

    if (! $listSource) {
        return response()->json(['error' => "list_source {$listSourceId} not found."], 404);
    }

    // Optionally override processing_mode and search_terms for this run
    // without touching the database — useful for quick mode testing.
    if (request('mode')) {
        $listSource->processing_mode = request('mode');
    }
    if (request('terms')) {
        $listSource->search_terms = request('terms');
    }

    // Dump what we're about to run so it's visible in the response
    $before = [
        'list_source_id'  => $listSource->id,
        'list_id'         => $listSource->list_id,
        'sourceable_type' => $listSource->sourceable_type,
        'sourceable_id'   => $listSource->sourceable_id,
        'processing_mode' => $listSource->processing_mode,
        'search_terms'    => $listSource->search_terms,
        'enabled'         => $listSource->enabled,
        'suspended'       => $listSource->suspended,
    ];

    // Run the processor
    $stats = $processor->process($listSource);

    // Grab the summaries written during this run for inspection
    $summaries = DB::table('summaries')
        ->where('list_source_id', $listSource->id)
        ->orderByDesc('created_at')
        ->limit(10)
        ->get(['id', 'source_title', 'source_url', 'processing_mode', 'is_relevant', 'included_in_digest', 'summary_html', 'created_at']);

    return response()->json([
        'list_source' => $before,
        'stats'       => $stats,
        'summaries'   => $summaries,
    ], 200, [], JSON_PRETTY_PRINT);
})
->middleware(['auth']);