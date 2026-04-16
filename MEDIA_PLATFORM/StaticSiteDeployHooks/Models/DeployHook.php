<?php

// =============================================================================
// DeployHook
//
// Represents a deploy hook for a static site hosting provider.
//
// Polymorphic — belongs to any triggerable model via the triggerable()
// relationship. Currently used by PodcastShow; can be extended to Digest
// Lists or any other model that needs to trigger static site builds.
//
// The hook URL is stored encrypted — it is a secret that grants the ability
// to trigger a build on the hosting provider.
//
// Path: MEDIA_PLATFORM/StaticSiteDeployHooks/Models/
// =============================================================================

namespace MediaPlatform\StaticSiteDeployHooks\Models;

use Database\Factories\Media_platform\StaticSiteDeployHooks\DeployHookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider;

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
        'triggerable_type',
        'triggerable_id',
        'label',
        'provider',
        'url',
        'enabled',
        'last_triggered_at',
        'last_build_id',
        'last_trigger_status',
    ];

    // -------------------------------------------------------------------------
    // Type casts.
    //
    // url is encrypted — never stored or logged as plain text.
    // provider is cast to the DeployHookProvider enum.
    // -------------------------------------------------------------------------
    protected $casts = [
        'provider'           => DeployHookProvider::class,
        'url'                => 'encrypted',
        'enabled'            => 'boolean',
        'last_triggered_at'  => 'datetime',
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
     * The owning model that this deploy hook belongs to.
     * Polymorphic — currently PodcastShow, extensible to any triggerable model.
     */
    public function triggerable(): MorphTo
    {
        return $this->morphTo();
    }
}