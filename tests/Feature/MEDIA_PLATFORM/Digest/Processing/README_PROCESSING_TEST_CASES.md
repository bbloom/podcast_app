# Processing Pipeline — Test Case Reference

This document enumerates every test case for the content processing pipeline.
It covers all three processor classes:

- `YoutubeContentProcessor`
- `PodcastContentProcessor`
- `TextBasedRssContentProcessor`

Each processor has two simulation test files:
- `simulate_[source]_first_run_processing.php`
- `simulate_[source]_regular_run_processing.php`

And one unit test file covering modes, failures, and data integrity:
- `YoutubeContentProcessorTest.php`
- `PodcastContentProcessorTest.php`
- `TextBasedRssContentProcessorTest.php`

---

## Feed Simulation Setup

All simulation tests use a **50-item feed** with items spaced **1 day apart**,
newest item first. The lookback window is set to **7 days** in all simulation
tests, so:

- Items 1–7 fall within the lookback window
- Items 8–50 are older than the lookback window

Regular run simulations prepend **5 new items** to the feed to simulate a week
of new content arriving since the last run.

Feed builders live in `tests/Support/` and are shared across all test files:
- `FakeYoutubePlaylistBuilder`
- `FakePodcastFeedBuilder`
- `FakeTextBasedRssFeedBuilder`

---

## Test Cases

### FIRST RUN

| # | Test Case |
|---|-----------|
| 1 | Correctly routes to `firstRunProcessing()` when no bookmark exists |
| 2 | Processes only items within the lookback window (7 of 50) |
| 3 | Skips items older than the lookback window (43 of 50) |
| 4 | Inserts bookmark pointing to the newest processed item (item 1 in the feed) |
| 5 | Inserts NO bookmark when all items are older than the lookback window |
| 6 | Processes items with no `published_at` date (should not be skipped by lookback) |
| 7 | Stats are correct — `fetched`, `processed`, `skipped` counts match expectations |

---

### REGULAR RUN — same feed, nothing new (the "second run")

| # | Test Case |
|---|-----------|
| 8  | Correctly routes to `regularRunProcessing()` when a bookmark exists |
| 9  | Stops immediately at bookmark URL — processes zero items |
| 10 | Bookmark is unchanged after a zero-result run |
| 11 | Stats show 0 processed, 0 skipped, 0 errors |

---

### REGULAR RUN — same feed, nothing new, run again (the "third run")

| # | Test Case |
|---|-----------|
| 12 | Same as 9–11 — verifies idempotency, second zero-result run behaves identically to first |

---

### REGULAR RUN — feed with new items prepended

| # | Test Case |
|---|-----------|
| 13 | Processes exactly the N new items prepended since the bookmark |
| 14 | Stops exactly at the bookmark URL |
| 15 | Does not process the bookmark item itself |
| 16 | Does not process anything after the bookmark |
| 17 | Rotates bookmark to the newest of the N new items |
| 18 | Stats show exactly N processed |

---

### REGULAR RUN — bookmark URL has disappeared from feed

| # | Test Case |
|---|-----------|
| 19 | Stops when an item's `published_at` is older than bookmark's `processed_at` |
| 20 | Processes new items before the fallback stop point |
| 21 | Rotates bookmark correctly after fallback stop |

---

### REGULAR RUN — items with no published_at

| # | Test Case |
|---|-----------|
| 22 | Item with no `published_at` is processed (no date = not skipped) |
| 23 | Bookmark still rotated correctly when a no-date item is the newest |

---

### EDGE CASES

| # | Test Case |
|---|-----------|
| 24 | Feed fetch failure — no bookmark written, tracking records failure |
| 25 | Feed with zero items — no bookmark written, no error |
| 26 | All 50 items are new (no bookmark match found in entire feed) — all processed, bookmark set to item 1 |
| 27 | Single item feed — first run processes it, regular run stops immediately |
| 28 | Two list_sources pointing to same feed — bookmarks are independent per list_source |
| 29 | Feed with duplicate URLs — second occurrence treated as new item (bookmark only stops on exact match of first occurrence) |

---

### FULL END-TO-END CHAIN

This is the most important scenario. It directly reproduces the bug that was
discovered during manual UI testing (second run processing all previously
skipped items).

| # | Test Case |
|---|-----------|
| 30 | Run 1: first run on 50-item feed → processes 7, skips 43, bookmark set |
| 31 | Run 2: regular run, same feed → processes 0, bookmark unchanged ✓ |
| 32 | Run 3: regular run, same feed again → processes 0, bookmark unchanged ✓ |
| 33 | Run 4: regular run, feed with 5 new items prepended → processes 5, rotates bookmark ✓ |
| 34 | Run 5: regular run, same feed as run 4 → processes 0, bookmark unchanged ✓ |

---

## Why These Tests Exist

The bug that prompted this test suite: on the second processing run, all items
that were skipped during the first run (due to the lookback window) were being
processed and included in the digest.

**Root cause:** the first/regular run logic was interleaved in a single loop,
making it difficult to reason about and impossible to test each path in
isolation. The fix was to separate `firstRunProcessing()` and
`regularRunProcessing()` into distinct methods, each with their own bookmark
handling, and to cover every scenario with explicit simulation tests rather
than discovering regressions through manual UI testing.

**The rule going forward:** if you can discover a processing error by running
the UI manually, that scenario needs a simulation test. The UI should never be
the first place a processing bug is found.