<?php

namespace MediaPlatform\PodcastStudio\PostProduction\GenerateRssFeed\Console\Commands;

use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PostProduction\GenerateRssFeed\Services\RssFeedGeneratorService;
use Illuminate\Console\Command;

// =============================================================================
// GenerateRssFeedCommand
//
// Artisan command to generate the RSS XML feed for a given podcast show.
//
// This command is a thin shell around RssFeedGeneratorService — all generation
// logic lives in the service, not here.
//
// Usage:
//   php artisan podcast:generate-rss --show=1
//   php artisan podcast:generate-rss --show=bob-bloom-show
//
// The --show option accepts either the show's numeric ID or its slug.
// The generated XML is written to storage/app/podcast/rss/<filename>.xml and
// its contents are echoed to the terminal for immediate inspection.
// =============================================================================

class GenerateRssFeedCommand extends Command
{
    protected $signature = 'podcast:generate-rss
                            {--show= : The podcast show ID or slug to generate a feed for}';

    protected $description = 'Generate the RSS XML feed for a given podcast show.';

    /**
     * Execute the command.
     * Returns Command::SUCCESS or Command::FAILURE for use in scripts/CI.
     */
    public function handle(RssFeedGeneratorService $generator): int
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  LaSalle Software — Podcast RSS Feed Generator');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // ---------------------------------------------------------------------
        // Resolve the podcast show.
        // Accepts either a numeric ID or a slug via --show.
        // If --show is omitted, list all shows and ask interactively.
        // ---------------------------------------------------------------------
        $show = $this->resolveShow();

        if (! $show) {
            // resolveShow() already output the error.
            return Command::FAILURE;
        }

        $this->info("  Show : {$show->title}");
        $this->info("  ID   : {$show->id}");
        $this->info("  Slug : {$show->slug}");
        $this->newLine();

        // ---------------------------------------------------------------------
        // Generate the XML via the service.
        // ---------------------------------------------------------------------
        $this->line('Generating RSS XML...');

        $result = $generator->generate($show);

        if (! $result->ok()) {
            $this->error($result->error());
            return Command::FAILURE;
        }

        $xml = $result->xml();

        // ---------------------------------------------------------------------
        // Write the XML file to local storage.
        // Path: storage/app/podcasts/rss/<filename>
        // The directory is created if it does not exist.
        // ---------------------------------------------------------------------
        $directory = storage_path('app/podcasts/rss');
        $filename  = $generator->getFileName($show);
        $filepath  = $directory . '/' . $filename;

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filepath, $xml);

        $this->info("File written: {$filepath}");
        $this->newLine();

        $this->newLine();
        $this->info('Done. Validate at https://www.castfeedvalidator.com');
        $this->newLine();

        return Command::SUCCESS;
    }


    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Resolve the PodcastShow from --show option (ID or slug) or interactively.
     * Returns null and outputs an error message if the show cannot be found.
     */
    private function resolveShow(): ?PodcastShow
    {
        $showOption = $this->option('show');

        // --show was provided — resolve by ID or slug.
        if ($showOption !== null) {

            $show = is_numeric($showOption)
                ? PodcastShow::find((int) $showOption)
                : PodcastShow::where('slug', $showOption)->first();

            if (! $show) {
                $this->error("No podcast show found for: {$showOption}");
                return null;
            }

            return $show;
        }

        // --show was omitted — list all shows and ask interactively.
        $shows = PodcastShow::orderBy('title')->get(['id', 'title', 'slug']);

        if ($shows->isEmpty()) {
            $this->error('No podcast shows found in the database.');
            return null;
        }

        $this->newLine();
        $this->line('Available podcast shows:');
        $this->newLine();

        foreach ($shows as $s) {
            $this->line("  [{$s->id}]  {$s->title}  ({$s->slug})");
        }

        $this->newLine();

        $chosen = $this->ask('Enter the show ID or slug');

        $show = is_numeric($chosen)
            ? PodcastShow::find((int) $chosen)
            : PodcastShow::where('slug', $chosen)->first();

        if (! $show) {
            $this->error("No podcast show found for: {$chosen}");
            return null;
        }

        return $show;
    }
}