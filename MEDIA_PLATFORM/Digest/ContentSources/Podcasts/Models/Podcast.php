<?php

namespace MediaPlatform\Digest\ContentSources\Podcasts\Models;

use Database\Factories\Media_platform\Digest\Podcasts\PodcastFactory;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListSource;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Podcast extends Model
{
    use HasFactory;

    protected $table = 'podcasts';

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
        static::deleting(function (Podcast $podcast) {
            $podcast->listSources()->delete();
        });
    }

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    protected static function newFactory()
    {
        return PodcastFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user who added this podcast.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * All list_sources rows that reference this podcast.
     * Use this to find which lists this podcast belongs to.
     */
    public function listSources(): MorphMany
    {
        return $this->morphMany(ListSource::class, 'sourceable');
    }
}