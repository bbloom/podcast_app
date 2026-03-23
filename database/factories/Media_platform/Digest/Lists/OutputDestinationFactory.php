<?php

namespace Database\Factories\Media_platform\Digest\Lists;

use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * OutputDestinationFactory — generates test OutputDestination records.
 *
 * Default state produces a valid SFTP destination with password auth.
 * Use ->wordpress() state for WordPress destinations.
 *
 * STATES
 * ──────
 * ->forUser(User $user) — pins the destination to a specific user
 * ->wordpress()         — switches to WordPress type with sensible defaults
 * ->sshKey()            — switches SFTP auth to SSH key
 * ->disabled()          — sets enabled = false
 */
class OutputDestinationFactory extends Factory
{
    protected $model = OutputDestination::class;

    /**
     * Default state — a valid SFTP destination with password auth.
     */
    public function definition(): array
    {
        return [
            'user_id'  => User::factory(),
            'name'     => fake()->words(3, true),
            'type'     => 'sftp',
            // ── SFTP fields ───────────────────────────────────────────────
            'host'        => fake()->domainName(),
            'port'        => 22,
            'username'    => fake()->userName(),
            'auth_type'   => 'password',
            'password'    => 'test-password',
            'private_key' => null,
            'passphrase'  => null,
            'path'        => '/var/www/digests',
            'base_url'    => 'https://' . fake()->domainName() . '/digests',
            // ── WordPress fields (null for SFTP) ──────────────────────────
            'wordpress_url'          => null,
            'wordpress_username'     => null,
            'wordpress_app_password' => null,
            'wordpress_post_status'  => null,
            'wordpress_category_ids' => null,
            'wordpress_tag_ids'      => null,
            // ── Shared ───────────────────────────────────────────────────
            'enabled' => true,
        ];
    }

    /**
     * Pin this destination to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    /**
     * WordPress destination state.
     * Provides sensible defaults so minimal overrides are needed in tests.
     */
    public function wordpress(): static
    {
        return $this->state(fn () => [
            'type'                   => 'wordpress',
            // Clear SFTP fields — not needed for WordPress
            'host'                   => null,
            'port'                   => null,
            'username'               => null,
            'auth_type'              => null,
            'password'               => null,
            'path'                   => null,
            'base_url'               => null,
            // WordPress fields
            'wordpress_url'          => 'https://' . fake()->domainName(),
            'wordpress_username'     => fake()->userName(),
            'wordpress_app_password' => 'test-app-password',
            'wordpress_post_status'  => 'publish',
            'wordpress_category_ids' => null,
            'wordpress_tag_ids'      => null,
        ]);
    }

    /**
     * SSH key auth state (SFTP only).
     */
    public function sshKey(): static
    {
        return $this->state(fn () => [
            'auth_type'   => 'ssh_key',
            'password'    => null,
            'private_key' => "-----BEGIN OPENSSH PRIVATE KEY-----\nfakekey\n-----END OPENSSH PRIVATE KEY-----",
            'passphrase'  => null,
        ]);
    }

    /**
     * Disabled destination state.
     */
    public function disabled(): static
    {
        return $this->state(fn () => ['enabled' => false]);
    }
}