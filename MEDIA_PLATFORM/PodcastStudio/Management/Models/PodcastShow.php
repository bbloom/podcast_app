<?php

namespace MediaPlatform\PodcastStudio\Management\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use Database\Factories\Media_platform\PodcastStudio\Management\PodcastShowFactory;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use MediaPlatform\Tools\FooterLinks\Models\FooterLink;


class PodcastShow extends Model
{
    use HasFactory;

    // -------------------------------------------------------------------------
    // Table name — always explicit per conventions.
    // -------------------------------------------------------------------------
    protected $table = 'podcast_shows';

    // -------------------------------------------------------------------------
    // Mass-assignable columns.
    // -------------------------------------------------------------------------
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'rss_link',

        // iTunes
        'itunes_image',
        'itunes_language',
        'itunes_category_primary',
        'itunes_category_secondary',
        'itunes_explicit',
        'itunes_author',
        'itunes_link',
        'itunes_email',
        'itunes_name',
        'itunes_title',
        'itunes_type',
        'itunes_copyright',
        'itunes_new_feed_url',
        'itunes_block',
        'itunes_complete',
        'itunes_summary',
        'itunes_subtitle',
        'itunes_content_encoded',

        // Spotify
        'spotify_limit',
        'spotify_country_of_origin',

        // Website
        'website_content',
        'website_excerpt',
        'website_meta_description',
        'website_featured_image',
        'website_publish_on',
        'website_enabled',

        // Storage
        'storage_artwork_url',
        'storage_video_files_url',
        'storage_audio_files_url',
    ];

    // -------------------------------------------------------------------------
    // Type casts.
    // -------------------------------------------------------------------------
    protected $casts = [
        'itunes_explicit'  => 'boolean',
        'itunes_block'     => 'boolean',
        'itunes_complete'  => 'boolean',
        'website_publish_on' => 'date',
        'website_enabled'  => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Factory resolution — points to the non-standard factory path.
    // -------------------------------------------------------------------------
    protected static function newFactory(): PodcastShowFactory
    {
        return PodcastShowFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user who owns this podcast show.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The episodes that belong to this podcast show.
     */
    public function episodes(): HasMany
    {
        return $this->hasMany(PodcastEpisode::class, 'podcast_show_id');
    }

    /**
     * The static site deploy hooks associated with this podcast show.
     * Polymorphic — uses the 'podcast_show' morph alias.
     */
    public function deployHooks(): MorphMany
    {
        return $this->morphMany(DeployHook::class, 'triggerable');
    }

    /**
     * The footer links belonging to this podcast show.
     * Used by the Astro static site build for the website footer.
     */
    public function footerLinks(): HasMany
    {
        return $this->hasMany(FooterLink::class, 'podcast_show_id');
    }
}