<?php

namespace Database\Factories\Media_platform\Configuration\Language_models;

use MediaPlatform\Configuration\LanguageModels\Models\LanguageModel;
use MediaPlatform\Configuration\Providers\Models\Provider;  // for foreign key
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LanguageModelFactory extends Factory
{
    protected $model = LanguageModel::class;
    
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'provider_id' => Provider::factory(),
            'name'        => ucwords($name),
            'slug'        => Str::slug($name),
            'description' => $this->faker->optional()->sentence(),
            'enabled'   => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['enabled' => false]);
    }

    public function forProvider(Provider $provider): static
    {
        return $this->state(['provider_id' => $provider->id]);
    }
}
