<?php

namespace MediaPlatform\PodcastStudio\Management\Models;

use Database\Factories\Media_platform\PodcastStudio\Management\PodcastGuestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PodcastGuest extends Model
{
    use HasFactory;

    // -------------------------------------------------------------------------
    // Table name — always explicit per conventions.
    // -------------------------------------------------------------------------
    protected $table = 'podcast_guests';

    // -------------------------------------------------------------------------
    // Mass-assignable columns.
    // -------------------------------------------------------------------------
    protected $fillable = [
        'full_name',
        'image_url',
        'image_thumbnail_url',
        'profile_full',
        'profile_short',
        'link_to_guest_website',
        'email_address',
        'internal_comment',
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
    protected static function newFactory(): PodcastGuestFactory
    {
        return PodcastGuestFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The podcast episodes this guest has appeared on.
     */
    public function episodes(): BelongsToMany
    {
        return $this->belongsToMany(PodcastEpisode::class, 'podcast_guest_episode');
    }
}