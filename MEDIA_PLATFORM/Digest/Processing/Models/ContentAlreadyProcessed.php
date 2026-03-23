<?php

namespace MediaPlatform\Digest\Processing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * ContentAlreadyProcessed — the processing bookmark model.
 *
 * One row per list_source. Stores the source_url of the most recently
 * processed item, acting as a stop signal for the next processing run.
 *
 * See app/Processing/README_PROCESSING_ASSUMPTIONS.md for the full design
 * rationale, bookmark rotation logic, and edge case documentation.
 */
class ContentAlreadyProcessed extends Model
{
    protected $table = 'content_already_processed';

    protected $fillable = [
        'list_source_id',
        'user_id',
        'source_url',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Bookmark lookup
    // =========================================================================

    /**
     * Return the current bookmark for a list_source, or null if none exists
     * (i.e. this is a first run).
     */
    public static function findBookmark(int $listSourceId): ?self
    {
        return self::where('list_source_id', $listSourceId)->first();
    }

    // =========================================================================
    // Bookmark rotation
    // =========================================================================

    /**
     * Rotate the bookmark to a new source_url.
     *
     * Performed as two separate operations — DELETE then INSERT — rather than
     * an UPDATE. This is intentional: we do not trust UPDATE to behave
     * atomically in all edge cases, and the explicit two-step makes the
     * intent obvious in the code and in the database logs.
     *
     * Called only after at least one item has been successfully processed
     * in the current run. Never called when nothing was processed.
     */
    public static function rotateBookmark(
        int    $listSourceId,
        int    $userId,
        string $sourceUrl,
    ): void {
        DB::transaction(function () use ($listSourceId, $userId, $sourceUrl) {
            self::where('list_source_id', $listSourceId)->delete();

            self::create([
                'list_source_id' => $listSourceId,
                'user_id'        => $userId,
                'source_url'     => $sourceUrl,
                'processed_at'   => now(),
            ]);
        });
    }
}