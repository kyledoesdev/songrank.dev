<?php

namespace Database\Factories;

use App\Models\Crate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Crate>
 */
class CrateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'parent_id' => null,
            'crate_icon_id' => null,
            'name' => ucwords(fake()->unique()->words(2, true)),
            'color' => fake()->hexColor(),
            'depth' => 0,
        ];
    }

    /**
     * Keeps the owner and the depth in agreement with the parent, which they
     * always are in real data and never are when a test sets only one of them.
     */
    public function nestedIn(Crate $parent): static
    {
        return $this->state([
            'user_id' => $parent->user_id,
            'parent_id' => $parent->getKey(),
            'depth' => $parent->depth + 1,
        ]);
    }
}
