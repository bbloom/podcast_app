<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_alerts', function (Blueprint $table) {
            $table->id();

            $table->unsignedTinyInteger('tier')
                ->comment('1 = self-correcting (no email), 2 = degraded mode (email, auto-resolves), 3 = human intervention required (email, manual resolve)');

            $table->string('category')
                ->comment('Subsystem: gemini, youtube, podcast, text_based_rss, sftp, infrastructure, queue');

            $table->string('title')
                ->comment('Short description: e.g. Gemini model deprecated');

            $table->text('message')
                ->comment('Details and recommended action for the admin');

            $table->boolean('is_resolved')
                ->default(false)
                ->comment('False until cleared. Auto-clears for Tier 1/2, requires manual resolve for Tier 3');

            $table->timestamp('resolved_at')
                ->nullable()
                ->comment('When the alert was resolved, either automatically or by the admin');

            $table->timestamp('notified_at')
                ->nullable()
                ->comment('When the notification email was sent. Prevents duplicate emails');

            $table->timestamps();

            $table->index(['is_resolved', 'tier'], 'admin_alerts_unresolved');
            $table->index(['category', 'is_resolved'], 'admin_alerts_gate_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_alerts');
    }
};
