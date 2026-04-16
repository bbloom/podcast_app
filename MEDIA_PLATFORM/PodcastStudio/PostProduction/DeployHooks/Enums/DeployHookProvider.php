<?php

// =============================================================================
// DeployHookProvider
//
// Enum of supported static site hosting providers that accept deploy hooks.
// Each case's backed value is stored in the deploy_hooks.provider column.
//
// Adding a new provider: add a case here, add a label in label(), and update
// the create/edit Blade views if a provider-specific UI hint is needed.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/DeployHooks/Enums/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\DeployHooks\Enums;

enum DeployHookProvider: string
{
    case cloudflare_pages = 'cloudflare_pages';
    case netlify          = 'netlify';
    case vercel           = 'vercel';

    /**
     * Human-readable label for display in views and dropdowns.
     */
    public function label(): string
    {
        return match ($this) {
            self::cloudflare_pages => 'Cloudflare Pages',
            self::netlify          => 'Netlify',
            self::vercel           => 'Vercel',
        };
    }
}