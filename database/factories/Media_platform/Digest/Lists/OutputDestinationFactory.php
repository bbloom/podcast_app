<?php

namespace Database\Factories\Media_platform\Digest\Lists;

use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * OutputDestinationFactory — generates test OutputDestination records.
 *
 * Default state produces a valid SFTP destination with password auth.
 *
 * STATES
 * ──────
 * ->forUser(User $user) — pins the destination to a specific user
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
            'user_id'     => User::factory(),
            'name'        => fake()->words(3, true),
            'type'        => 'sftp',
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
            // ── Shared ───────────────────────────────────────────────────
            'enabled'     => true,
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