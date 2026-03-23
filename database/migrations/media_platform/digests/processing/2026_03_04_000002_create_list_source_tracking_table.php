<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('list_source_tracking', function (Blueprint $table) {
            $table->id();

            $table->foreignId('list_source_id')
                ->unique()
                ->constrained('list_sources')
                ->cascadeOnDelete()
                ->comment('One-to-one link to the list_sources pivot row');

            $table->timestamp('last_fetched_at')
                ->nullable()
                ->comment('When we last checked this source for new content. Null on first run triggers the 2-day lookback');

            $table->timestamp('last_entry_published_at')
                ->nullable()
                ->comment('Published date of the newest content item we have processed');

            $table->timestamp('last_digest_published_at')
                ->nullable()
                ->comment('When this source last had content included in a published digest');

            $table->text('error_message')
                ->nullable()
                ->comment('Most recent error encountered during fetch or processing');

            $table->unsignedInteger('consecutive_failures')
                ->default(0)
                ->comment('Number of consecutive failed fetch attempts. Resets to 0 on success. Auto-suspends at 5');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('list_source_tracking');
    }
};
