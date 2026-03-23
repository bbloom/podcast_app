<?php

namespace MediaPlatform\Digest\Processing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Summary extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'source_published_at'   => 'datetime',
            'included_in_digest_at' => 'datetime',
            'is_relevant'           => 'boolean',
            'included_in_digest'    => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function listSource(): BelongsTo
    {
        return $this->belongsTo(\MediaPlatform\Digest\ContentSources\Lists\Models\ListSource::class, 'list_source_id');
    }

    /**
     * Scope: summaries ready for digest (relevant, not yet included).
     */
    public function scopeForDigest($query, int $listSourceId)
    {
        return $query->where('list_source_id', $listSourceId)
            ->where('is_relevant', true)
            ->where('included_in_digest', false);
    }
}
