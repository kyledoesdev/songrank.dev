<?php

namespace Database\Factories;

use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Artist>
 */
class ArtistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'artist_id' => str()->random(16),
            'artist_name' => fake()->userName(),
            'artist_img' => 'https://example.test/artists/'.str()->random(16).'.png',
        ];
    }
}
