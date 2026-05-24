<?php

namespace MediaPlatform\Podcasts\Planning\CRUD\Models;

use App\Models\User;
use Database\Factories\Media_platform\Podcasts\Planning\PodcastEpisodePlanningFactory;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use MediaPlatform\Podcasts\Links\Models\PodcastLink;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PodcastEpisodePlanning extends Model
{
    use HasFactory;

    protected $table = 'podcast_episodes_planning';

    protected $fillable = [
        'podcast_show_id',
        'user_id',
        'status',

        // Core identity
        'title',
        'episode_number',
        'scheduled_date',

        // Creative content
        'notes',
        'theme',
        'script',
        'script_scratch',      // Ephemeral AI scratch pad (Step 4, Finalize Script Wizard)

        // Website content
        'website_content',
        'website_excerpt',
    ];

    protected $casts = [
        'status'         => PodcastEpisodePlanningStatus::class,
        'scheduled_date' => 'date',
    ];

    protected static function newFactory(): PodcastEpisodePlanningFactory
    {
        return PodcastEpisodePlanningFactory::new();
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function show(): BelongsTo
    {
        return $this->belongsTo(PodcastShow::class, 'podcast_show_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guests(): BelongsToMany
    {
        return $this->belongsToMany(
            PodcastGuest::class,
            'podcast_guest_episode_planning',
            'podcast_episode_planning_id',
            'podcast_guest_id'
        );
    }

    public function links(): BelongsToMany
    {
        return $this->belongsToMany(
            PodcastLink::class,
            'podcast_link_episode_planning',
            'podcast_episode_planning_id',
            'podcast_link_id'
        );
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Filter planning episodes belonging to the given user.
     * Table-qualified to avoid ambiguity in joins.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('podcast_episodes_planning.user_id', $userId);
    }

    /**
     * Filter planning episodes by a specific status.
     */
    public function scopeWithStatus(Builder $query, PodcastEpisodePlanningStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * Order by episode number ascending.
     */
    public function scopeOrderByEpisodeNumber(Builder $query): Builder
    {
        return $query->orderBy('episode_number', 'asc');
    }

    /**
     * Order by scheduled date ascending.
     */
    public function scopeOrderByScheduledDate(Builder $query): Builder
    {
        return $query->orderBy('scheduled_date', 'asc');
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Returns the formatted episode display title: "#N - Title".
     * episode_number and title are stored separately; the formatted
     * display is always derived, never stored.
     */
    public function getFormattedTitleAttribute(): string
    {
        if ($this->episode_number) {
            return "#{$this->episode_number} - {$this->title}";
        }

        return $this->title;
    }
}