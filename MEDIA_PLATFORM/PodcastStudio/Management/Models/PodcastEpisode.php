<?php

namespace MediaPlatform\PodcastStudio\Management\Models;

use Database\Factories\Media_platform\PodcastStudio\Management\PodcastEpisodeFactory;
use MediaPlatform\PodcastStudio\Management\Models\PodcastGuest;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PodcastEpisode extends Model
{
    use HasFactory;

    // -------------------------------------------------------------------------
    // Table name — always explicit per conventions.
    // -------------------------------------------------------------------------
    protected $table = 'podcast_episodes';

    // -------------------------------------------------------------------------
    // Mass-assignable columns.
    // -------------------------------------------------------------------------
    protected $fillable = [
        'podcast_show_id',
        'user_id',
        'podcast_episode_status_lookup_id',

        // Core
        'title',
        'slug',
        'scheduled_date',
        'draft',
        'raw_input_audio_filename',

        // iTunes
        'itunes_title_tag',
        'itunes_enclosure_url',
        'itunes_enclosure_length',
        'itunes_enclosure_type',
        'itunes_guid',
        'itunes_pubdate',
        'itunes_description',
        'itunes_duration',
        'itunes_link',
        'itunes_image',
        'itunes_explicit',
        'itunes_itunestitle_tag',
        'itunes_episode',
        'itunes_season',
        'itunes_episode_type',
        'itunes_block',
        'itunes_summary',
        'itunes_subtitle',
        'itunes_content_encoded',

        // RSS
        'rss_feed_enabled',

        // Website
        'website_content',
        'website_excerpt',
        'website_meta_description',
        'website_episode_notes',
        'website_attribution',
        'website_featured_image',
        'website_publish_on',
        'website_enabled',
    ];

    // -------------------------------------------------------------------------
    // Type casts.
    // -------------------------------------------------------------------------
    protected $casts = [
        'scheduled_date'   => 'date',
        'itunes_pubdate'   => 'datetime',
        'itunes_explicit'  => 'boolean',
        'itunes_block'     => 'boolean',
        'rss_feed_enabled' => 'boolean',
        'website_publish_on' => 'date',
        'website_enabled'  => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Factory resolution — points to the non-standard factory path.
    // -------------------------------------------------------------------------
    protected static function newFactory(): PodcastEpisodeFactory
    {
        return PodcastEpisodeFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user who owns this episode.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The podcast show this episode belongs to.
     */
    public function show(): BelongsTo
    {
        return $this->belongsTo(PodcastShow::class, 'podcast_show_id');
    }

    /**
     * The status lookup record assigned to this episode.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(PodcastEpisodeStatusLookup::class, 'podcast_episode_status_lookup_id');
    }

    /**
     * The links attached to this episode.
     */
    public function links(): BelongsToMany
    {
        return $this->belongsToMany(PodcastLink::class, 'podcast_link_episode');
    }

    /**
     * The guests who appeared on this episode.
     */
    public function guests(): BelongsToMany
    {
        return $this->belongsToMany(PodcastGuest::class, 'podcast_guest_episode');
    }
}