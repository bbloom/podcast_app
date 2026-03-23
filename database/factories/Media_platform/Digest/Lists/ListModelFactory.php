<?php

namespace Database\Factories\Media_platform\Digest\Lists;

use MediaPlatform\Enums\OutputType;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ListModelFactory — generates test ListModel records.
 *
 * Default state produces an email-output daily list.
 * Use ->webpage($destId) or ->wordpress($destId) for other output types.
 */
class ListModelFactory extends Factory
{
    protected $model = ListModel::class;

    public function definition(): array
    {
        return [
            'user_id'               => User::factory(),
            'name'                  => fake()->words(3, true),
            'description'           => fake()->optional()->sentence(),
            'enabled'               => true,
            'schedule_frequency'    => fake()->randomElement(['daily', 'weekly', 'monthly']),
            'schedule_day'          => null,
            'schedule_time'         => fake()->time('H:i'),
            'timezone'              => 'America/Toronto',
            // Default to email — no destination required
            'output_type'           => OutputType::Email,
            'output_destination_id' => null,
            'notify_by_email'       => false,
        ];
    }

    /**
     * Pin this list to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    /**
     * Webpage (SFTP) output type state.
     */
    public function webpage(int $destinationId): static
    {
        return $this->state(fn () => [
            'output_type'           => OutputType::Webpage,
            'output_destination_id' => $destinationId,
        ]);
    }

    /**
     * WordPress output type state.
     */
    public function wordpress(int $destinationId): static
    {
        return $this->state(fn () => [
            'output_type'           => OutputType::Wordpress,
            'output_destination_id' => $destinationId,
        ]);
    }
}