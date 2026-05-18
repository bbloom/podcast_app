<?php

// =============================================================================
// DeployHookFactory
//
// Factory for generating DeployHook test data.
//
// The url column is cast as encrypted on the model — the factory supplies a
// plain-text URL and Laravel's encrypted cast handles encryption on write.
//
// The hook is polymorphic. By default it is associated with a PodcastShow
// via the 'podcast_show' morph alias. Additional triggerable types can be
// added as factory states when needed.
//
// Path: database/factories/Media_platform/StaticSiteDeployHooks/
// =============================================================================

namespace Database\Factories\Media_platform\StaticSiteDeployHooks;

use Illuminate\Database\Eloquent\Factories\Factory;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;

class DeployHookFactory extends Factory
{
    // -------------------------------------------------------------------------
    // Bind to the DeployHook model.
    // -------------------------------------------------------------------------
    protected $model = DeployHook::class;

    /**
     * Define the factory's default state.
     * Associates the hook with a PodcastShow by default.
     */
    public function definition(): array
    {
        $show = PodcastShow::factory()->create();

        return [
            'triggerable_type'   => 'podcast_show',
            'triggerable_id'     => $show->id,
            'label'              => $this->faker->sentence(4),
            'provider'           => DeployHookProvider::cloudflare_pages,
            'url'                => 'https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/' . $this->faker->uuid(),
            'enabled'            => true,
            'last_triggered_at'  => null,
            'last_build_id'      => null,
            'last_trigger_status' => null,
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

    /**
     * Set the hook as having been successfully triggered.
     */
    public function succeeded(string $buildId = 'abc-123'): static
    {
        return $this->state([
            'last_triggered_at'   => now(),
            'last_build_id'       => $buildId,
            'last_trigger_status' => 'success',
        ]);
    }

    /**
     * Set the hook as having failed on its last trigger attempt.
     */
    public function failed(): static
    {
        return $this->state([
            'last_triggered_at'   => now(),
            'last_build_id'       => null,
            'last_trigger_status' => 'failed',
        ]);
    }
}