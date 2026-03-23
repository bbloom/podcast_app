
Wanted to put this somewhere..

## Search "Tiers"

Here's what I actually mean in plain terms, using the TextBasedRssContentProcessor as the reference:
When in search mode, the processor tries three progressively more expensive checks before giving up:

(1) Does the search term appear in the episode title? Pure PHP string matching — free, instant.

(2) Does the search term appear in the show notes/description? Also pure PHP string matching — free, instant.

(3) Send the show notes to the LLM and ask it to decide if it's relevant. This is the expensive path — it costs an LLM call, so we only do it if the two free checks both came up empty.

If any of those checks pass, the episode is considered relevant and we generate a summary. If check 3 returns NOT_RELEVANT, we store the row with is_relevant = false and no summary HT