<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\Digest\Publishing\Models\PublishedDigest;

/**
 * PublishedDigestsSeeder — seeds realistic published digest records for static site lists.
 *
 * Creates 5 published digests per static site list, spread across the last 5 days.
 * Each digest has a realistic payload structure with multiple source groups and items.
 *
 * Gated behind ADMIN_SEEDING_ENABLED — the gate lives in DatabaseSeeder,
 * not here (per conventions).
 */
class PublishedDigestsSeeder extends Seeder
{
    public function run(): void
    {
        $lists = ListModel::where('output_type', OutputType::StaticSite->value)->get();

        if ($lists->isEmpty()) {
            $this->command->info('PublishedDigestsSeeder: No static site lists found. Skipping.');
            return;
        }

        foreach ($lists as $list) {
            $this->seedDigestsForList($list);
        }

        $this->command->info('PublishedDigestsSeeder: Seeded published digests for ' . $lists->count() . ' static site list(s).');
    }

    private function seedDigestsForList(ListModel $list): void
    {
        for ($i = 0; $i < 5; $i++) {
            $date = Carbon::now()->subDays($i);
            $slug = strtolower(preg_replace('/[^a-z0-9.]+/i', '-', trim($list->name)));
            $slug = trim($slug, '-') . '-digest-' . $date->format('Y-m-d');

            $totalItems  = rand(3, 12);
            $sourceCount = rand(1, 4);

            PublishedDigest::create([
                'list_id'              => $list->id,
                'user_id'              => $list->user_id,
                'slug'                 => $slug,
                'digest_date'          => $date->toDateString(),
                'total_items'          => $totalItems,
                'source_count'         => $sourceCount,
                'payload'              => $this->buildPayload($totalItems, $sourceCount, $date),
                'deploy_hook_fired_at' => $date->copy()->addMinutes(2),
                'api_fetched_at'       => $i > 0 ? $date->copy()->addMinutes(5) : null, // latest one not yet fetched
            ]);
        }
    }

    private function buildPayload(int $totalItems, int $sourceCount, Carbon $date): array
    {
        $sourceTypes = ['youtube_channel', 'podcast', 'text_based_rss_feed'];
        $sourceNames = [
            'youtube_channel'     => ['Laracasts', 'Fireship', 'Traversy Media', 'The Primagen'],
            'podcast'             => ['Laravel News Podcast', 'Syntax FM', 'PHP Internals News'],
            'text_based_rss_feed' => ['Laravel News', 'PHP Weekly', 'Ars Technica', 'Hacker News'],
        ];

        $groups        = [];
        $itemsPerGroup = max(1, intdiv($totalItems, $sourceCount));

        for ($g = 0; $g < $sourceCount; $g++) {
            $type  = $sourceTypes[$g % count($sourceTypes)];
            $names = $sourceNames[$type];
            $name  = $names[$g % count($names)];

            $items = [];
            $count = ($g === $sourceCount - 1)
                ? $totalItems - ($itemsPerGroup * $g)
                : $itemsPerGroup;

            for ($j = 0; $j < $count; $j++) {
                $items[] = [
                    'source_url'          => 'https://example.com/' . $type . '/item-' . ($g * 10 + $j + 1),
                    'source_title'        => "Sample {$name} Item " . ($j + 1),
                    'source_description'  => "A sample item from {$name} published on {$date->format('M j, Y')}.",
                    'source_published_at' => $date->copy()->subHours(rand(1, 23))->toIso8601String(),
                    'summary_html'        => '<p>This is a seeded summary for testing. It covers topics related to '
                                             . strtolower($name) . ' and modern software development practices.</p>',
                ];
            }

            $groups[] = [
                'source_name' => $name,
                'source_type' => $type,
                'items'       => $items,
            ];
        }

        return $groups;
    }
}