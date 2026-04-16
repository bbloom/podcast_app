<?php

// =============================================================================
// DeployHookFactory
//
// Factory for generating DeployHook test data.
//
// The url column is cast as encrypted on the model — the factory supplies a
// plain-text URL and Laravel's encrypted cast handles encryption on write.
//
// Path: database/factories/Media_platform/PodcastStudio/PostProduction/
// =============================================================================

namespace Database\Factories\Media_platform\PodcastStudio\PostProduction;

use Illuminate\Database\Eloquent\Factories\Factory;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PostProduction\DeployHooks\Enums\DeployHookProvider;
use MediaPlatform\PodcastStudio\PostProduction\DeployHooks\Models\DeployHook;

class DeployHookFactory extends Factory
{
    // -------------------------------------------------------------------------
    // Bind to the DeployHook model.
    // -------------------------------------------------------------------------
    protected $model = DeployHook::class;

    /**
     * Define the factory's default state.
     */
    public function definition(): array
    {
        return [
            'podcast_show_id'   => PodcastShow::factory(),
            'label'             => $this->faker->sentence(4),
            'provider'          => DeployHookProvider::cloudflare_pages,
            'url'               => 'https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/' . $this->faker->uuid(),
            'enabled'           => true,
            'last_triggered_at' => null,
        ];
    }

    // -------------------------------------------------------------------------
    // States
    // -------------------------------------------------------------------------

    /**
     * Mark the hook as disabled.
     */
    public function disabled(): static
    {
        return $this->state(['enabled' => false]);
    }

    /**
     * Set the provider to Netlify.
     */
    public function netlify(): static
    {
        return $this->state([
            'provider' => DeployHookProvider::netlify,
            'url'      => 'https://api.netlify.com/build_hooks/' . $this->faker->uuid(),
        ]);
    }

    /**
     * Set the provider to Vercel.
     */
    public function vercel(): static
    {
        return $this->state([
            'provider' => DeployHookProvider::vercel,
            'url'      => 'https://api.vercel.com/v1/integrations/deploy/' . $this->faker->uuid(),
        ]);
    }

    /**
     * Set a specific last_triggered_at timestamp.
     */
    public function triggeredAt(\Carbon\Carbon $at): static
    {
        return $this->state(['last_triggered_at' => $at]);
    }
}