<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\Track;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Track>
 */
class TrackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'track_id' => str()->random(22),
            'artist_id' => Artist::factory(),
            'name' => fake()->sentence(3),
            'cover' => fake()->imageUrl(300, 300, 'track', true),
            'album_name' => fake()->sentence(2),
        ];
    }
}
