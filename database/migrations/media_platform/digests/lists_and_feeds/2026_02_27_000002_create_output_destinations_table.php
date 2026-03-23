<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('output_destinations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('References users.id');

            $table->string('name')
                  ->comment('Human readable name e.g. My Personal Website');

            $table->enum('type', ['sftp', 'api'])
                  ->comment('Destination type, sftp or api');

            $table->string('host')
                  ->comment('Destination server host e.g. sftp.mysite.com');

            $table->unsignedSmallInteger('port')
                  ->default(22)
                  ->comment('SFTP port, default 22');

            $table->string('username')
                  ->comment('SFTP username');

            $table->enum('auth_type', ['password', 'ssh_key'])
                  ->comment('Authentication type, password or ssh_key');

            $table->string('password')
                  ->nullable()
                  ->comment('Encrypted, used when auth_type is password');

            $table->text('private_key')
                  ->nullable()
                  ->comment('Encrypted, used when auth_type is ssh_key');

            $table->string('passphrase')
                  ->nullable()
                  ->comment('Encrypted, optional SSH key passphrase');

            $table->string('path')
                  ->nullable()
                  ->comment('Remote path on destination server e.g. /public_html/digests');

            $table->string('base_url')
                  ->nullable()
                  ->comment('Public facing URL e.g. https://mysite.com/digests, used in notification emails');

            $table->boolean('enabled')
                  ->default(true)
                  ->comment('User controlled');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('output_destinations');
    }
};
