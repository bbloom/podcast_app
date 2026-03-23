<?php

namespace MediaPlatform\Digest\Processing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListSourceTracking extends Model
{
    protected $table = 'list_source_tracking';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'last_fetched_at'          => 'datetime',
            'last_entry_published_at'  => 'datetime',
            'last_digest_published_at' => 'datetime',
        ];
    }

    public function listSource(): BelongsTo
    {
        return $this->belongsTo(\MediaPlatform\Digest\ContentSources\Lists\Models\ListSource::class, 'list_source_id');
    }

    /**
     * Get or create a tracking record for a list_source.
     */
    public static function findOrCreateFor(int $listSourceId): self
    {
        return self::firstOrCreate(
            ['list_source_id' => $listSourceId],
            ['consecutive_failures' => 0],
        );
    }

    /**
     * Record a successful fetch.
     */
    public function recordSuccess(): void
    {
        $this->update([
            'last_fetched_at'      => now(),
            'error_message'        => null,
            'consecutive_failures' => 0,
        ]);
    }

    /**
     * Record a failed fetch.
     */
    public function recordFailure(string $message): void
    {
        $this->update([
            'last_fetched_at'      => now(),
            'error_message'        => $message,
            'consecutive_failures' => $this->consecutive_failures + 1,
        ]);
    }

    /**
     * Has this source exceeded the failure threshold?
     */
    public function shouldSuspend(): bool
    {
        return $this->consecutive_failures >= 5;
    }
}
