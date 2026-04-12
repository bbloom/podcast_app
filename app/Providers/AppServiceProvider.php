<?php

namespace App\Providers;

use App\Models\User;
use MediaPlatform\Digest\ContentSources\Podcasts\Models\Podcast;
use MediaPlatform\Digest\ContentSources\TextBasedRssFeeds\Models\TextBasedRssFeed;
use MediaPlatform\Digest\ContentSources\Youtube\Models\YoutubeChannel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // -------------------------------------------------------------------------
        // Migration paths
        // Each feature folder contains its own migrations.
        // Laravel does not scan sub-folders automatically, so each path is
        // registered explicitly here.
        // -------------------------------------------------------------------------
        $this->loadMigrationsFrom([
            database_path('migrations/media_platform/configuration/language_models'),
            database_path('migrations/media_platform/digests/processing'),
            database_path('migrations/media_platform/digests/lists_and_feeds'),
            database_path('migrations/media_platform/tools/database_backup'),
            database_path('migrations/media_platform/podcast_studio/management'),
            database_path('migrations/media_platform/tools/phpserverlessproject_sponsors'),
            database_path('migrations/media_platform/api'),
        ]);

        // -------------------------------------------------------------------------
        // Polymorphic morph aliases
        //
        // By default, Eloquent stores the fully qualified class name in the
        // "sourceable_type" column of the "list_sources" table when using
        // polymorphic relationships. For example:
        //   MediaPlatform\Digest\ContentSources\Youtube\Models\YoutubeChannel
        //
        // This tightly couples the database value to the class namespace.
        // If a class is ever moved or renamed, the stored strings in the database
        // would become invalid, breaking the polymorphic relationship silently.
        //
        // Morph aliases decouple the stored string from the class name.
        // The database stores a short, stable alias (e.g. "youtube_channel")
        // instead of the full class name. The alias never needs to change,
        // even if the class is refactored.
        // -------------------------------------------------------------------------
        Relation::enforceMorphMap([
            'youtube_channel'     => YoutubeChannel::class,
            'text_based_rss_feed' => TextBasedRssFeed::class,
            'podcast'             => Podcast::class,
        ]);

        // -------------------------------------------------------------------------
        // There is an admin
        // -------------------------------------------------------------------------
        Gate::define('admin', function (User $user) {
            return $user->email === config('admin.admin_email');
        });

        // -------------------------------------------------------------------------
        // Register custom artisan commands
        // -------------------------------------------------------------------------
        $this->commands([
            \MediaPlatform\Tools\HealthChecks\Console\Commands\HealthCheckCommand::class,
            \MediaPlatform\Digest\Processing\Console\Commands\ProcessListsCommand::class,
            \MediaPlatform\Tools\DatabaseBackup\Commands\BackupDatabaseCommand::class,
            \MediaPlatform\PodcastStudio\PostProduction\GenerateRssFeed\Console\Commands\GenerateRssFeedCommand::class,
        ]);

        /**
         * Force HTTPS when running behind a reverse proxy in production.
         * Without this, Laravel generates HTTP URLs despite APP_URL being HTTPS,
         * because it only sees the internal HTTP connection from Caddy on port 8080.
         */
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}