<?php

namespace MediaPlatform\Digest\Publishing\Models;

use App\Models\User;
use Database\Factories\Media_platform\Digest\Publishing\PublishedDigestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;

/**
 * PublishedDigest — a persisted digest payload for the static site output type.
 *
 * One record per digest run per list. Contains the full structured digest data
 * as JSON in the `payload` column. The API serves these records to the static
 * site generator (Astro) during its build process.
 *
 * Self-contained — does not depend on the ephemeral summaries table. Once a
 * PublishedDigest is created, it can be served by the API indefinitely (subject
 * to the list's retention policy).
 */
class PublishedDigest extends Model
{
    use HasFactory;

    protected $table = 'published_digests';

    protected $fillable = [
        'list_id',
        'user_id',
        'slug',
        'digest_date',
        'total_items',
        'source_count',
        'payload',
        'deploy_hook_fired_at',
        'api_fetched_at',
    ];

    protected $casts = [
        'payload'              => 'array',
        'digest_date'          => 'date',
        'deploy_hook_fired_at' => 'datetime',
        'api_fetched_at'       => 'datetime',
        'total_items'          => 'integer',
        'source_count'         => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    protected static function newFactory(): PublishedDigestFactory
    {
        return PublishedDigestFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The list this digest was built for.
     */
    public function list(): BelongsTo
    {
        return $this->belongsTo(ListModel::class, 'list_id');
    }

    /**
     * The user who owns this digest (via the list).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}