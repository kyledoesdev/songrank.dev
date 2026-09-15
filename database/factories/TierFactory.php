<?php

namespace Database\Factories;

use App\Models\Tier;
use App\Models\Tierlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tier>
 */
class TierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tierlist_id' => Tierlist::factory(),
            'name' => fake()->randomLetter(),
            'color' => fake()->hexColor(),
            'position' => 1,
            'is_bank' => false,
        ];
    }

    public function bank(): static
    {
        $bank = config('tierlists.bank');

        return $this->state([
            'name' => $bank['name'],
            'color' => $bank['color'],
            'position' => 0,
            'is_bank' => true,
        ]);
    }
}
