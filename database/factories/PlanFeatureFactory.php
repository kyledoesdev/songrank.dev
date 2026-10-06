<?php

namespace Database\Factories;

use App\Enums\Billing\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanFeature>
 */
class PlanFeatureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan' => Plan::FREE,
            'label' => fake()->sentence(3),
            'detail' => null,
            'is_included' => true,
            'is_coming_soon' => false,
        ];
    }

    public function pro(): static
    {
        return $this->state(fn (array $attributes): array => [
            'plan' => Plan::PRO,
        ]);
    }

    public function excluded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_included' => false,
        ]);
    }

    public function comingSoon(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_coming_soon' => true,
        ]);
    }
}
