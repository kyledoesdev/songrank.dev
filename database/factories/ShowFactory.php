<?php

namespace Database\Factories;

use App\Models\Show;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Show>
 */
class ShowFactory extends Factory
{
    public function definition(): array
    {
        return [
            'show_id' => str()->random(16),
            'publisher' => fake()->company(),
            'name' => fake()->sentence(1),
            'description' => fake()->sentence(1),
            'cover' => 'https://example.test/shows/'.str()->random(16).'.png',
            'episode_count' => rand(5, 100),
            'data' => null,
        ];
    }
}
