<?php

namespace MediaPlatform\Digest\ContentSources\Lists\Models;

use Database\Factories\Media_platform\Digest\Lists\ListModelFactory;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListSource;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\Digest\Publishing\Models\PublishedDigest;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/*
 * NOTE: This model is named ListModel because "List" is a reserved word in PHP.
 * It maps to the "lists" database table.
 *
 * OUTPUT TYPE ENUM
 * ────────────────
 * The output_type column is cast to OutputType so that you can always compare
 * it with the enum rather than raw strings:
 *
 *   if ($list->output_type === OutputType::StaticSite) { ... }
 *
 * Never compare against raw strings like 'static_site' directly in application code.
 */
class ListModel extends Model
{
    use HasFactory;

    protected $table = 'lists';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'enabled',
        'schedule_frequency',
        'schedule_day',
        'schedule_time',
        'timezone',
        'output_type',
        'output_destination_id',
        'notify_by_email',
        'retention_count',
        'last_run_at',
    ];

    protected $casts = [
        'enabled'         => 'boolean',
        'notify_by_email' => 'boolean',
        'last_run_at'     => 'datetime',
        'schedule_day'    => 'integer',
        'retention_count' => 'integer',
        // Cast output_type to the OutputType enum — Laravel will automatically
        // convert the stored string to the enum and back on read/write.
        'output_type'     => OutputType::class,
    ];

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    protected static function newFactory()
    {
        return ListModelFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user who owns this list.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The output destination for this list (SFTP only).
     * Null when output_type is Email or StaticSite.
     */
    public function outputDestination(): BelongsTo
    {
        return $this->belongsTo(OutputDestination::class, 'output_destination_id');
    }

    /**
     * All sources belonging to this list, via list_sources.
     */
    public function sources(): HasMany
    {
        return $this->hasMany(ListSource::class, 'list_id');
    }

    /**
     * Published digest records for this list.
     * Only populated for static site output type.
     */
    public function publishedDigests(): HasMany
    {
        return $this->hasMany(PublishedDigest::class, 'list_id');
    }

    /**
     * Deploy hooks attached to this list (polymorphic).
     * Only used for static site output type.
     */
    public function deployHooks(): MorphMany
    {
        return $this->morphMany(DeployHook::class, 'triggerable');
    }
}