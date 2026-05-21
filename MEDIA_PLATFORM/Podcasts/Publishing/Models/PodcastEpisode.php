<?php

namespace MediaPlatform\Podcasts\Publishing\Models;

use App\Models\User;
use Database\Factories\Media_platform\Podcasts\Publishing\PodcastEpisodeFactory;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use MediaPlatform\Podcasts\Links\Models\PodcastLink;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class PodcastEpisode extends Model
{
    use HasFactory;

    // -------------------------------------------------------------------------
    // Table name — always explicit per conventions.
    // -------------------------------------------------------------------------
    protected $table = 'podcast_episodes_published';

    // -------------------------------------------------------------------------
    // Mass-assignable columns.
    // -------------------------------------------------------------------------
    protected $fillable = [
        'podcast_show_id',
        'user_id',
        'status',

        // Core
        'title',
        'slug',
        'scheduled_date',
        'draft',
        'raw_input_audio_filename',

        // Auphonic post-production
        'auphonic_production_uuid',

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
        'status'           => PodcastEpisodeStatus::class,
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

    

    // -------------------------------------------------------------------------
    // Scopes
    //
    // Reusable query fragments to avoid duplicating filter logic across
    // controllers and services. Always use these instead of raw where() chains.
    // -------------------------------------------------------------------------

    /**
     * Scope to episodes belonging to the given user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('podcast_episodes_published.user_id', $userId);
    }

    /**
     * Scope to episodes with the given pipeline status.
     */
    public function scopeWithStatus(Builder $query, PodcastEpisodeStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope ordering episodes by scheduled date ascending — most imminent first.
     */
    public function scopeOrderByScheduledDate(Builder $query): Builder
    {
        return $query->orderBy('scheduled_date', 'asc');
    }

    /**
     * Scope to episodes eligible for inclusion in the RSS feed for a given show.
     *
     * An episode is eligible when:
     *   - It belongs to the given show
     *   - rss_feed_enabled is true
     *   - itunes_pubdate is in the past
     *
     * Ordered most-recent-first, as required by the RSS spec.
     */
    public function scopeEligibleForRssFeed(Builder $query, PodcastShow $show): Builder
    {
        return $query
            ->where('podcast_show_id', $show->id)
            ->where('rss_feed_enabled', true)
            ->where('itunes_pubdate', '<', CarbonImmutable::now())
            ->orderByDesc('itunes_pubdate');
    }

    /**
     * Scope to episodes that are publicly visible on the website for a given show.
     *
     * An episode is visible when:
     *   - It belongs to the given show (matched via the show's slug)
     *   - website_enabled is true
     *   - website_publish_on is in the past
     *
     * Ordered newest-first by publish date, as required by the public API.
     */
    public function scopeEligibleForPublishOnWebsite(Builder $query, PodcastShow $show): Builder
    {
        return $query
            ->whereHas('show', fn (Builder $q) => $q->where('slug', $show->slug))
            ->where('website_enabled', true)
            ->where('website_publish_on', '<', CarbonImmutable::now(config('app.timezone')))
            ->orderBy('website_publish_on', 'desc');
    }
}