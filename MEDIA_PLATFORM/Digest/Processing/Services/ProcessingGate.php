<?php

namespace MediaPlatform\Digest\Processing\Services;

use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use Illuminate\Support\Facades\Log;

class ProcessingGate
{
    /**
     * Category mappings: which alert categories block which subsystem.
     */
    private array $subsystemCategories = [
        'youtube'        => ['youtube', 'gemini', 'infrastructure', 'queue'],
        'podcast'        => ['podcast', 'gemini', 'infrastructure', 'queue'],
        'text_based_rss' => ['text_based_rss', 'gemini', 'infrastructure', 'queue'],
        'publish'        => ['sftp', 'infrastructure'],
    ];

    /**
     * Can the given subsystem proceed with processing?
     */
    public function canProcess(string $subsystem): bool
    {
        $categories = $this->subsystemCategories[$subsystem] ?? [];

        if (empty($categories)) {
            Log::warning("ProcessingGate: Unknown subsystem '{$subsystem}', allowing by default.");
            return true;
        }

        $blockers = AdminAlert::where('is_resolved', false)
            ->where('tier', 3)
            ->whereIn('category', $categories)
            ->get();

        if ($blockers->isNotEmpty()) {
            $titles = $blockers->pluck('title')->implode(', ');
            Log::warning("ProcessingGate: '{$subsystem}' blocked by Tier 3 alerts: {$titles}");
            return false;
        }

        return true;
    }

    /**
     * Can digest publishing proceed?
     */
    public function canPublish(): bool
    {
        return $this->canProcess('publish');
    }

    /**
     * Map a sourceable_type to a subsystem name for gate checks.
     */
    public function subsystemForSourceType(string $sourceableType): string
    {
        return match ($sourceableType) {
            'youtube_channel'      => 'youtube',
            'podcast'              => 'podcast',
            'text_based_rss_feed'  => 'text_based_rss',
            default => 'unknown',
        };
    }
}
