<?php

namespace MediaPlatform\Digest\ContentSources\Lists\Models;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListSource;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ListSource extends Model
{
    protected $table = 'list_sources';

    protected $fillable = [
        'list_id',
        'sourceable_id',
        'sourceable_type',
        'enabled',
        'suspended',
        'suspended_reason',
        'suspended_at',
        'processing_mode',
        'search_terms',
    ];

    protected $casts = [
        'enabled'      => 'boolean',
        'suspended'    => 'boolean',
        'suspended_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The list this source belongs to.
     */
    public function list(): BelongsTo
    {
        return $this->belongsTo(ListModel::class, 'list_id');
    }

    /**
     * The polymorphic source — resolves to a YoutubeChannel, TextBasedRssFeed, or Podcast.
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }
}
