<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the content_already_processed table.
 *
 * PURPOSE
 * ───────
 * This table acts as a "bookmark" — one row per list_source — pointing to the
 * most recently processed item from that source. It is the sole mechanism for
 * preventing duplicate processing across runs.
 *
 * It intentionally replaces the previous approach of deduplicating against the
 * summaries table. The summaries table is for digest delivery only; it is safe
 * to delete summaries after a digest is sent without affecting future processing.
 *
 * See app/Processing/README_PROCESSING_ASSUMPTIONS.md for the full design
 * rationale and edge case documentation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_already_processed', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('list_source_id')
                ->comment('The list_source this bookmark belongs to. One row per list_source.');

            $table->unsignedBigInteger('user_id')
                ->comment('The owner of the list. Stored for visual reference and auditing.');

            $table->string('source_url')
                ->comment('The URL of the most recently processed item from this source. Used as the stop signal on the next run.');

            $table->timestamp('processed_at')
                ->comment('When this item was processed. Used as a fallback stop signal when the bookmarked URL has disappeared from the feed.');

            $table->timestamps();

            $table->unique('list_source_id',  'content_already_processed_list_source_unique');
            $table->index('user_id',           'content_already_processed_user_id_index');

            $table->comment('Bookmark table — one row per list_source, tracking the most recently processed content item. Prevents duplicate processing across runs independent of the summaries table.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_already_processed');
    }
};