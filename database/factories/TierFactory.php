<?php

namespace Database\Factories;

use App\Models\Tier;
use App\Models\Tierlist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tier>
 */
class TierFactory extends Factory
{
    public function definition(): array
    {
        /* Random rather than sequential: tiers are unique per list by slug, and
           fake()->unique() overflows after 26 letters in a long-running suite. */
        $name = Str::upper(Str::random(3));

        return [
            'tierlist_id' => Tierlist::factory(),
            'name' => $name,
            'slug' => Str::lower($name),
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
            'slug' => $bank['slug'],
            'color' => $bank['color'],
            'position' => 0,
            'is_bank' => true,
        ]);
    }
}
