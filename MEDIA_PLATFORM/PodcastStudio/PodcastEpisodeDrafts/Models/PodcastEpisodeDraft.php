<?php

// =============================================================================
// PodcastEpisodeDraft
//
// Lightweight planning and drafting workspace for podcast episodes.
// The draft accumulates all the inputs needed for episode creation:
// title, episode number, date, script/draft text, website content,
// and attached links. When finalized, these feed directly into
// Step3Controller to create the production episode record.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/Models/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Models;

use App\Models\User;
use Database\Factories\Media_platform\PodcastStudio\PodcastEpisodeDrafts\PodcastEpisodeDraftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use MediaPlatform\Podcasts\Links\Models\PodcastLink;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Enums\PodcastEpisodeDraftStatus;

class PodcastEpisodeDraft extends Model
{
    use HasFactory;

    // -------------------------------------------------------------------------
    // Table name — always explicit per conventions.
    // -------------------------------------------------------------------------
    protected $table = 'podcast_episode_drafts';

    // -------------------------------------------------------------------------
    // Factory resolution — points to the non-standard factory path.
    // -------------------------------------------------------------------------
    protected static function newFactory(): PodcastEpisodeDraftFactory
    {
        return PodcastEpisodeDraftFactory::new();
    }

    // -------------------------------------------------------------------------
    // Mass-assignable columns.
    // -------------------------------------------------------------------------
    protected $fillable = [
        'podcast_show_id',
        'user_id',
        'status',
        'title',
        'date',
        'episode_number',
        'draft',
        'website_content',
        'website_excerpt',
        'guest_notes',
        'comments',
        'basecamp_url',
    ];

    // -------------------------------------------------------------------------
    // Type casts.
    // -------------------------------------------------------------------------
    protected $casts = [
        'date'           => 'date',
        'episode_number' => 'integer',
        'status'         => PodcastEpisodeDraftStatus::class,
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The podcast show this draft belongs to.
     */
    public function show(): BelongsTo
    {
        return $this->belongsTo(PodcastShow::class, 'podcast_show_id');
    }

    /**
     * The user who owns this draft.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The links attached to this draft.
     * Upon episode creation, these are migrated to the podcast_link_episode pivot.
     */
    public function links(): BelongsToMany
    {
        return $this->belongsToMany(PodcastLink::class, 'podcast_link_episode_draft');
    }

    /**
     * The guests attached to this draft.
     * Upon episode creation, these are migrated to the podcast_guest_episode pivot.
     */
    public function guests(): BelongsToMany
    {
        return $this->belongsToMany(PodcastGuest::class, 'podcast_guest_episode_draft');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope to drafts belonging to the given user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('podcast_episode_drafts.user_id', $userId);
    }
}