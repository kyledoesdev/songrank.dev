<?php

namespace Database\Factories;

use App\Models\CrateIcon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrateIcon>
 */
class CrateIconFactory extends Factory
{
    public function definition(): array
    {
        $word = fake()->unique()->word();

        return [
            'name' => ucfirst($word),
            'icon' => 'fa-'.$word,
            'is_pro' => false,
            'is_active' => true,
        ];
    }

    public function pro(): static
    {
        return $this->state(['is_pro' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
