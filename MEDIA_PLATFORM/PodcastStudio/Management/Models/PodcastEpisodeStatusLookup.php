<?php

namespace MediaPlatform\PodcastStudio\Management\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Database\Factories\Media_platform\PodcastStudio\Management\PodcastEpisodeStatusLookupFactory;

class PodcastEpisodeStatusLookup extends Model
{
    use HasFactory;

    // -------------------------------------------------------------------------
    // Table name — always explicit per conventions.
    // -------------------------------------------------------------------------
    protected $table = 'podcast_episode_status_lookup';

    // -------------------------------------------------------------------------
    // Mass-assignable columns.
    // -------------------------------------------------------------------------
    protected $fillable = [
        'title',
        'description',
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
    protected static function newFactory(): PodcastEpisodeStatusLookupFactory
    {
        return PodcastEpisodeStatusLookupFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * A status lookup record can be assigned to many podcast episodes.
     */
    public function episodes(): HasMany
    {
        return $this->hasMany(PodcastEpisode::class, 'podcast_episode_status_lookup_id');
    }
}