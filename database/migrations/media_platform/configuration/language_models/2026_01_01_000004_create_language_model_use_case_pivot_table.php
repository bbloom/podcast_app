<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('language_model_use_case', function (Blueprint $table) {
            $table->foreignId('language_model_id')->constrained('language_models')->cascadeOnDelete();
            $table->foreignId('use_case_id')->constrained('use_cases')->cascadeOnDelete();
            $table->primary(['language_model_id', 'use_case_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_model_use_case');
    }
};
