<?php

namespace MediaPlatform\Digest\ContentSources\Youtube\Models;

use Database\Factories\Media_platform\Digest\Youtube\YoutubeChannelFactory;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListSource;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class YoutubeChannel extends Model
{
    use HasFactory;

    protected $table = 'youtube_channels';

    protected $fillable = [
        'user_id',
        'channel_id',
        'title',
        'handle',
        'channel_url',
        'rss_url',
        'thumbnail',
        'description',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];


    // -------------------------------------------------------------------------
    // Booted
    // -------------------------------------------------------------------------

    protected static function booted(): void
    {
        static::deleting(function (YoutubeChannel $channel) {
            // Clean up list_sources (which cascades to tracking and summaries via FK)
            $channel->listSources()->delete();
        });
    }

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    protected static function newFactory()
    {
        return YoutubeChannelFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user who added this channel.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * All list_sources rows that reference this channel.
     * Use this to find which lists this channel belongs to.
     */
    public function listSources(): MorphMany
    {
        return $this->morphMany(ListSource::class, 'sourceable');
    }
}