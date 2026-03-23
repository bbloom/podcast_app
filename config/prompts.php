<?php

/**
 * config/prompts.php
 *
 * All LLM prompt templates used by the content-processing pipeline.
 *
 * WHY THIS FILE EXISTS
 * ────────────────────
 * Prompts were previously hardcoded inside the processor classes
 * (YoutubeContentProcessor, TextBasedRssContentProcessor, and the upcoming
 * PodcastContentProcessor). Moving them here gives you a single place to
 * find, read, and edit every prompt in the application without touching
 * service-layer code.
 *
 * IMPORTANT — COUPLING BETWEEN PROMPTS AND CODE
 * ──────────────────────────────────────────────
 * These prompts are NOT free-form strings that can be edited arbitrarily.
 * Several of them are tightly coupled to the PHP code that calls them:
 *
 *   1. VARIABLE INTERPOLATION
 *      The processor classes build the final prompt by concatenating config
 *      values with runtime variables (e.g. $title, $text, $searchTerms).
 *      The interpolation is done in PHP, NOT inside this file. If you rename
 *      or restructure the way variables are spliced in, you must update the
 *      corresponding processor method too.
 *
 *   2. THE "NOT_RELEVANT" SENTINEL
 *      The search prompts (youtube_search, article_search, podcast_search)
 *      instruct the model to reply with exactly "NOT_RELEVANT" when content
 *      does not match the search terms. The processor code checks for this
 *      exact string with:
 *
 *          if ($result === 'NOT_RELEVANT') { ... }
 *
 *      If you change the sentinel word or phrase in the prompt, you MUST also
 *      update the matching string comparison in:
 *        - YoutubeContentProcessor::searchAndSummarise()
 *        - TextBasedRssContentProcessor::searchAndSummarise()
 *        - PodcastContentProcessor::searchAndSummarise()
 *
 *   3. OUTPUT FORMAT EXPECTATIONS
 *      All summary prompts ask for HTML output. The returned HTML is stored
 *      directly in summaries.summary_html and rendered in digest views.
 *      If you change the output format (e.g. ask for Markdown instead), the
 *      digest rendering layer will need to be updated to match.
 *
 * EDITING GUIDELINES
 * ──────────────────
 * - You may safely rephrase, expand, or refine the instructions within each
 *   prompt without touching PHP code, as long as you preserve:
 *     a) The variable splice points (they follow each prompt string in PHP)
 *     b) The NOT_RELEVANT sentinel in search prompts
 *     c) The HTML output format instruction
 *
 * - Each key is namespaced by source type (youtube, article, podcast) and
 *   then by mode (summary, search). This makes it easy to tune prompts
 *   independently per source type, even though the summary and search
 *   structures are intentionally similar across all three.
 *
 * - If you ever add a new processing mode or source type, add its prompt(s)
 *   here and load them via config('prompts.your_key') in the processor.
 *
 * WHAT IS NOT HERE
 * ────────────────
 * The AA_Playtime sandbox prompts are intentionally excluded — that
 * directory is for throwaway experiments and is not part of the pipeline.
 */

return [

    // =========================================================================
    // YOUTUBE
    // =========================================================================

    /*
     * Used by: YoutubeContentProcessor::summarise()
     *
     * Spliced with at call site:
     *   - {title}      → the video title from the YouTube API snippet
     *   - {transcript} → the plain-text transcript from get_transcript.py
     *
     * Coupling note: the transcript is appended AFTER this string in PHP,
     * so this prompt must end with the label "Transcript:\n" (or similar)
     * to provide context for what follows. See YoutubeContentProcessor.
     */
    'youtube_summary' =>
        "Below is a transcript of a YouTube video titled \"{title}\". " .
        "Summarize the key points for a general audience. " .
        "Format your response as a brief 2-3 sentence overview followed by a bullet point list of the main takeaways. " .
        "Ignore any sponsor messages, ads, or repetitive filler. " .
        "Use HTML formatting.\n\n" .
        "Transcript:\n",

    /*
     * Used by: YoutubeContentProcessor::searchAndSummarise()
     *
     * Spliced with at call site:
     *   - {search_terms} → the user's comma-separated search terms
     *   - {title}        → the video title
     *   - {transcript}   → the plain-text transcript
     *
     * !! SENTINEL COUPLING !!
     * This prompt instructs the model to reply with exactly "NOT_RELEVANT"
     * when the content does not match. The processor checks:
     *     if ($result === 'NOT_RELEVANT')
     * Changing the sentinel here REQUIRES updating that check in
     * YoutubeContentProcessor::searchAndSummarise().
     */
    'youtube_search' =>
        "You are a content relevance filter. A user is searching for content about: {search_terms}\n\n" .
        "Below is a transcript of a YouTube video titled \"{title}\". " .
        "If this content is relevant to the search terms, provide a 2-3 sentence overview " .
        "followed by bullet points of the key takeaways in HTML format. " .
        "If the content is NOT relevant to the search terms, respond with exactly: NOT_RELEVANT\n\n" .
        "Transcript:\n",


    // =========================================================================
    // TEXT-BASED RSS (articles, blog posts, news)
    // =========================================================================

    /*
     * Used by: TextBasedRssContentProcessor::summarise()
     *
     * Spliced with at call site:
     *   - {title} → the article title from the RSS feed entry
     *   - {text}  → plain text extracted by ArticleExtractorService (Readability),
     *               falling back to feed content/description if extraction fails
     *
     * The "boilerplate" instruction is important here — Readability does its
     * best to strip navigation and ads, but some inevitably slips through.
     */
    'article_summary' =>
        "Below is the text of a news article or blog post titled \"{title}\". " .
        "Summarise the key points for a general audience. " .
        "Format your response as a brief 2–3 sentence overview followed by a bullet point list of the main takeaways. " .
        "Ignore any cookie notices, subscription prompts, navigation text, or other page boilerplate. " .
        "Use HTML formatting.\n\n" .
        "Article text:\n",

    /*
     * Used by: TextBasedRssContentProcessor::searchAndSummarise()
     *
     * Spliced with at call site:
     *   - {search_terms} → the user's comma-separated search terms
     *   - {title}        → the article title
     *   - {text}         → article text (same source as article_summary above)
     *
     * !! SENTINEL COUPLING !!
     * See the youtube_search note above — same NOT_RELEVANT contract applies.
     * The sentinel check lives in TextBasedRssContentProcessor::searchAndSummarise().
     */
    'article_search' =>
        "You are a content relevance filter. A user is searching for content about: {search_terms}\n\n" .
        "Below is the text of an article titled \"{title}\". " .
        "If this content is relevant to the search terms, provide a 2–3 sentence overview " .
        "followed by bullet points of the key takeaways in HTML format. " .
        "If the content is NOT relevant to the search terms, respond with exactly: NOT_RELEVANT\n\n" .
        "Article text:\n",


    // =========================================================================
    // PODCAST
    // =========================================================================

    /*
     * Used by: PodcastContentProcessor::summarise()
     *
     * Spliced with at call site:
     *   - {title}     → the episode title from the RSS <item>
     *   - {show_notes} → the episode description / show notes from the feed
     *                   (<description> or <itunes:summary>)
     *
     * Important difference from YouTube and article prompts: podcasts have NO
     * transcript available in this pipeline. We summarise from show notes only.
     * The prompt is worded accordingly — "show notes" rather than "transcript"
     * or "article text" — so the model understands the nature of the input.
     * Show notes are typically written by the podcast producer and may be
     * incomplete, promotional, or structured as bullet lists themselves.
     */
    'podcast_summary' =>
        "Below are the show notes for a podcast episode titled \"{title}\". " .
        "Summarise the key points for a general audience. " .
        "Format your response as a brief 2–3 sentence overview followed by a bullet point list of the main takeaways. " .
        "The input is show notes written by the podcast producer, not a full transcript — " .
        "summarise only what is described; do not speculate about content that may have been discussed but is not mentioned. " .
        "Use HTML formatting.\n\n" .
        "Show notes:\n",

    /*
     * Used by: PodcastContentProcessor::searchAndSummarise()
     *
     * Spliced with at call site:
     *   - {search_terms} → the user's comma-separated search terms
     *   - {title}        → the episode title
     *   - {show_notes}   → the episode description / show notes
     *
     * !! SENTINEL COUPLING !!
     * Same NOT_RELEVANT contract as youtube_search and article_search.
     * The sentinel check will live in PodcastContentProcessor::searchAndSummarise().
     *
     * Note: unlike the YouTube search prompt, there is no full transcript to
     * fall back on. If the show notes are thin, the LLM may struggle to make
     * a confident relevance determination. This is a known limitation.
     */
    'podcast_search' =>
        "You are a content relevance filter. A user is searching for content about: {search_terms}\n\n" .
        "Below are the show notes for a podcast episode titled \"{title}\". " .
        "If this content is relevant to the search terms, provide a 2–3 sentence overview " .
        "followed by bullet points of the key takeaways in HTML format. " .
        "If the content is NOT relevant to the search terms, respond with exactly: NOT_RELEVANT\n\n" .
        "Show notes:\n",

];