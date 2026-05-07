<?php

namespace MediaPlatform\Videos\Models;

use App\Models\User;
use Database\Factories\Media_platform\Videos\VideoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MediaPlatform\Videos\Enums\VideoStatus;

/**
 * Video — a video being prepared for publication to YouTube.
 */
class Video extends Model
{
    use HasFactory;

    protected $table = 'videos';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'scheduled_date',
        'status',
        'youtube_title',
        'youtube_description',
        'youtube_chapters',
        'youtube_url',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'status'         => VideoStatus::class,
    ];

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * Resolve the factory for this model.
     */
    protected static function newFactory(): VideoFactory
    {
        return VideoFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user who owns this video.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope to videos belonging to the given user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('videos.user_id', $userId);
    }
}