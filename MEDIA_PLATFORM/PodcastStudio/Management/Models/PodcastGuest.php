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
        'slug',
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
    // Model events — auto-generate slug from full_name on create and update.
    // -------------------------------------------------------------------------
    protected static function booted(): void
    {
        static::creating(function (PodcastGuest $guest) {
            $guest->slug = self::makeSlug($guest->full_name);
        });

        static::updating(function (PodcastGuest $guest) {
            // Only regenerate the slug if full_name has actually changed.
            if ($guest->isDirty('full_name')) {
                $guest->slug = self::makeSlug($guest->full_name);
            }
        });
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

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a URL-friendly slug from a given string.
     * Lowercases, trims, and replaces spaces with hyphens.
     */
    private static function makeSlug(string $value): string
    {
        return str_replace(' ', '-', strtolower(trim($value)));
    }
}