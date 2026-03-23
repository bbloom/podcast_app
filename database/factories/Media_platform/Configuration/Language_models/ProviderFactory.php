<?php

namespace Database\Factories\Media_platform\Configuration\Language_models;

use MediaPlatform\Configuration\Providers\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'name'        => $name,
            'slug'        => Str::slug($name),
            'description' => $this->faker->optional()->sentence(),
            'website_url' => $this->faker->optional()->url(),
            'enabled'   => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['enabled' => false]);
    }
}
