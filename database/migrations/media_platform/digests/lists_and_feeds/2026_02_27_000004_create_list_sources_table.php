<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* ================================================================================================
   *
   * It is the pivot table that connects a list to its content sources.
   *  
   * A list can have many sources (YouTube channels, podcasts, RSS feeds). A source can belong to many lists. 
   * list_sources is the join between them, and it also carries the per-connection 
   * configuration — processing_mode, search_terms, enabled, suspended — because those 
   * settings belong to the relationship between a list and a source, not to the source itself.
   * 
   ================================================================================================ */

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('list_sources', function (Blueprint $table) {
            $table->id();

            $table->foreignId('list_id')
                  ->constrained('lists')
                  ->cascadeOnDelete()
                  ->comment('References lists.id');

            $table->unsignedBigInteger('sourceable_id')
                  ->comment('Polymorphic — the id of the source record, Eloquent managed, no foreign key constraint');

            $table->string('sourceable_type')
                  ->comment('Polymorphic — the fully qualified class name of the source e.g. App\Youtube\Models\YoutubeChannel');

            $table->boolean('enabled')
                  ->default(true)
                  ->comment('User controlled, manually enable or disable a source within a list');

            $table->boolean('suspended')
                  ->default(false)
                  ->comment('System controlled, automatically set to true when a source fails to process');

            $table->string('suspended_reason')
                  ->nullable()
                  ->comment('System populated, human readable reason for suspension e.g. Youtube API unreachable');

            $table->timestamp('suspended_at')
                  ->nullable()
                  ->comment('System populated, when the suspension occurred');

            $table->timestamps();

            $table->unique(['list_id', 'sourceable_id', 'sourceable_type'],
                           'list_sources_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('list_sources');
    }
};
