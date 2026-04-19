<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lists', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('References users.id');

            $table->string('name')
                  ->comment('Human readable name e.g. Morning Tech Digest');

            $table->text('description')
                  ->nullable()
                  ->comment('Optional user notes about this list');

            $table->boolean('enabled')
                  ->default(true)
                  ->comment('User controlled, enables or disables this list from running');

            $table->enum('schedule_frequency', ['daily', 'weekly', 'monthly'])
                  ->comment('How often this list runs');

            $table->tinyInteger('schedule_day')
                  ->nullable()
                  ->comment('1-7 for weekly (Mon-Sun), 1-31 for monthly, null for daily');

            $table->time('schedule_time')
                  ->comment('Time of day to run this list e.g. 06:00:00');

            $table->string('timezone')
                  ->nullable()
                  ->comment('Overrides user timezone if set, otherwise inherits from users.timezone');

            $table->string('output_type')
                  ->comment('Delivery mechanism: webpage, email, or static_site. Validated by the PHP OutputType enum, not by a database constraint.');

            $table->foreignId('output_destination_id')
                  ->nullable()
                  ->constrained('output_destinations')
                  ->nullOnDelete()
                  ->comment('References output_destinations.id, null when output_type is email or static_site');

            $table->boolean('notify_by_email')
                  ->default(false)
                  ->comment('Sends a notification email when output_type is webpage or static_site and the digest is ready');

            $table->unsignedInteger('retention_count')
                  ->default(10)
                  ->comment('Number of published_digests records to retain per list. Only actively used when output_type is static_site. Old records are pruned after each new digest.');

            $table->timestamp('last_run_at')
                  ->nullable()
                  ->comment('When the scheduler last ran this list');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lists');
    }
};