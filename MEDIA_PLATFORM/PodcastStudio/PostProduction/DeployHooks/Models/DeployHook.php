<?php

// =============================================================================
// DeployHook
//
// Represents a deploy hook for a static site hosting provider.
// Belongs to a PodcastShow. A show may have many hooks.
//
// The hook URL is stored encrypted — it is a secret that grants the ability
// to trigger a build on the hosting provider.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/DeployHooks/Models/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\DeployHooks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\Media_platform\PodcastStudio\PostProduction\DeployHookFactory;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PostProduction\DeployHooks\Enums\DeployHookProvider;

class DeployHook extends Model
{
    use HasFactory;

    // -------------------------------------------------------------------------
    // Table name — always explicit per conventions.
    // -------------------------------------------------------------------------
    protected $table = 'deploy_hooks';

    // -------------------------------------------------------------------------
    // Mass-assignable columns.
    // -------------------------------------------------------------------------
    protected $fillable = [
        'podcast_show_id',
        'label',
        'provider',
        'url',
        'enabled',
        'last_triggered_at',
    ];

    // -------------------------------------------------------------------------
    // Type casts.
    //
    // url is encrypted — never stored or logged as plain text.
    // provider is cast to the DeployHookProvider enum.
    // -------------------------------------------------------------------------
    protected $casts = [
        'provider'         => DeployHookProvider::class,
        'url'              => 'encrypted',
        'enabled'          => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Factory resolution — points to the non-standard factory path.
    // -------------------------------------------------------------------------
    protected static function newFactory(): DeployHookFactory
    {
        return DeployHookFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The podcast show this deploy hook belongs to.
     */
    public function show(): BelongsTo
    {
        return $this->belongsTo(PodcastShow::class, 'podcast_show_id');
    }
}