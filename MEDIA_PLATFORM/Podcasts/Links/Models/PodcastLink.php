<?php

namespace MediaPlatform\Podcasts\Links\Models;

use App\Models\User;
use Database\Factories\Media_platform\Podcasts\Links\PodcastLinkFactory;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PodcastLink extends Model
{
    use HasFactory;

    protected $table = 'podcast_links';

    protected $fillable = [
        'user_id',
        'title',
        'link',
        'description',
        'comments',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected static function newFactory(): PodcastLinkFactory
    {
        return PodcastLinkFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function episodes(): BelongsToMany
    {
        return $this->belongsToMany(PodcastEpisode::class, 'podcast_link_episode');
    }
}