<?php

// =============================================================================
// DeployHook
//
// Represents a deploy hook for a static site hosting provider.
//
// Polymorphic — belongs to any triggerable model via the triggerable()
// relationship. Currently used by PodcastShow and ListModel (digest lists).
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
     * Polymorphic — currently PodcastShow or ListModel (digest list).
     */
    public function triggerable(): MorphTo
    {
        return $this->morphTo();
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Human-readable display name for the triggerable model.
     *
     * PodcastShow uses `title`, ListModel uses `name`. This accessor provides
     * a single consistent way to display the owning model's name in views,
     * avoiding the need for type-checking in every Blade template.
     *
     * Usage: $hook->triggerable_display_name
     */
    public function getTriggerableDisplayNameAttribute(): string
    {
        return match ($this->triggerable_type) {
            'podcast_show' => $this->triggerable->title ?? "Show #{$this->triggerable_id}",
            'digest_list'  => $this->triggerable->name ?? "List #{$this->triggerable_id}",
            default        => "#{$this->triggerable_id}",
        };
    }

    /**
     * Human-readable label for the triggerable type.
     *
     * Usage: $hook->triggerable_type_label
     */
    public function getTriggerableTypeLabelAttribute(): string
    {
        return match ($this->triggerable_type) {
            'podcast_show' => 'Podcast Show',
            'digest_list'  => 'Digest List',
            default        => $this->triggerable_type,
        };
    }

    /**
     * Route name for linking to the triggerable model's show page.
     *
     * Usage: route($hook->triggerable_show_route, $hook->triggerable)
     */
    public function getTriggerableShowRouteAttribute(): string
    {
        return match ($this->triggerable_type) {
            'podcast_show' => 'podcast_shows.show',
            'digest_list'  => 'lists.show',
            default        => 'dashboard',
        };
    }
}