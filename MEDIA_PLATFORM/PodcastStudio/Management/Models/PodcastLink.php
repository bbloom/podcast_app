<?php

namespace MediaPlatform\PodcastStudio\Management\Models;

use Database\Factories\Media_platform\PodcastStudio\Management\PodcastLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PodcastLink extends Model
{
    use HasFactory;

    // -------------------------------------------------------------------------
    // Table name — always explicit per conventions.
    // -------------------------------------------------------------------------
    protected $table = 'podcast_links';

    // -------------------------------------------------------------------------
    // Mass-assignable columns.
    // -------------------------------------------------------------------------
    protected $fillable = [
        'title',
        'link',
        'description',
        'comments',
        'enabled',
    ];

    // -------------------------------------------------------------------------
    // Type casts.
    // -------------------------------------------------------------------------
    protected $casts = [
        'enabled' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Factory resolution — points to the non-standard factory path.
    // -------------------------------------------------------------------------
    protected static function newFactory(): PodcastLinkFactory
    {
        return PodcastLinkFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The podcast episodes this link is attached to.
     */
    public function episodes(): BelongsToMany
    {
        return $this->belongsToMany(PodcastEpisode::class, 'podcast_link_episode');
    }
}