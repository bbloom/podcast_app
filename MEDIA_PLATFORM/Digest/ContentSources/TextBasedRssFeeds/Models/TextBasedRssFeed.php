<?php

namespace MediaPlatform\Digest\ContentSources\TextBasedRssFeeds\Models;

use Database\Factories\Media_platform\Digest\TextBasedRssFeeds\TextBasedRssFeedFactory;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListSource;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TextBasedRssFeed extends Model
{
    use HasFactory;

    protected $table = 'text_based_rss_feeds';

    protected $fillable = [
        'user_id',
        'rss_url',
        'title',
        'description',
        'site_url',
        'thumbnail',
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
        static::deleting(function (TextBasedRssFeed $feed) {
            $feed->listSources()->delete();
        });
    }

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    protected static function newFactory()
    {
        return TextBasedRssFeedFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user who added this feed.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * All list_sources rows that reference this feed.
     * Use this to find which lists this feed belongs to.
     */
    public function listSources(): MorphMany
    {
        return $this->morphMany(ListSource::class, 'sourceable');
    }
}