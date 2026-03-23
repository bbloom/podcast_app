<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\ContentSources\Podcasts\Models\Podcast;
use MediaPlatform\Digest\ContentSources\TextBasedRssFeeds\Models\TextBasedRssFeed;
use MediaPlatform\Digest\ContentSources\Youtube\Models\YoutubeChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * All lists belonging to this user.
     */
    public function lists(): HasMany
    {
        return $this->hasMany(ListModel::class);
    }

    /**
     * All output destinations belonging to this user.
     */
    public function outputDestinations(): HasMany
    {
        return $this->hasMany(OutputDestination::class);
    }

    /**
     * All Youtube channels belonging to this user.
     */
    public function youtubeChannels(): HasMany
    {
        return $this->hasMany(YoutubeChannel::class);
    }

    /**
     * All podcasts belonging to this user.
     */
    public function podcasts(): HasMany
    {
        return $this->hasMany(Podcast::class);
    }

    /**
     * All text-based RSS feeds belonging to this user.
     */
    public function textBasedRssFeeds(): HasMany
    {
        return $this->hasMany(TextBasedRssFeed::class);
    }
}