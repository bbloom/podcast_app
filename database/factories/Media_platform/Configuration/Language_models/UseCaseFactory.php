<?php

namespace Database\Factories\Media_platform\Configuration\Language_models;

use MediaPlatform\Configuration\UseCases\Models\UseCase;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UseCaseFactory extends Factory
{
    protected $model = UseCase::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name'        => ucwords($name),
            'slug'        => Str::slug($name),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
