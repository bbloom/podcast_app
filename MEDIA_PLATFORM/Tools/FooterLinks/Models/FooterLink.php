<?php

namespace MediaPlatform\Tools\FooterLinks\Models;

use App\Models\User;
use Database\Factories\Media_platform\Tools\FooterLinks\FooterLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;

class FooterLink extends Model
{
    use HasFactory;

    // -------------------------------------------------------------------------
    // Table name — always explicit per conventions.
    // -------------------------------------------------------------------------
    protected $table = 'footer_links';

    // -------------------------------------------------------------------------
    // Mass-assignable columns.
    // -------------------------------------------------------------------------
    protected $fillable = [
        'podcast_show_id',
        'user_id',
        'link_name',
        'link_url',
        'link_order',
    ];

    // -------------------------------------------------------------------------
    // Type casts.
    // -------------------------------------------------------------------------
    protected $casts = [
        'link_order' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Factory resolution — points to the non-standard factory path.
    // -------------------------------------------------------------------------
    protected static function newFactory(): FooterLinkFactory
    {
        return FooterLinkFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The podcast show this footer link belongs to.
     */
    public function podcastShow(): BelongsTo
    {
        return $this->belongsTo(PodcastShow::class);
    }

    /**
     * The user who owns this footer link.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}