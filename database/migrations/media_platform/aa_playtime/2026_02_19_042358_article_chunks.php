<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('article_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('article_url')->index();
            $table->string('article_title');
            $table->text('chunk_text');
            $table->integer('chunk_index');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE article_chunks ADD COLUMN embedding halfvec(3072)');
        DB::statement('CREATE INDEX ON article_chunks USING hnsw (embedding halfvec_cosine_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('article_chunks');
    }
};