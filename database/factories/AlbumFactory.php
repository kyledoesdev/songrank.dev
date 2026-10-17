<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Album>
 */
class AlbumFactory extends Factory
{
    public function definition(): array
    {
        return [
            'album_id' => str()->random(22),
            'artist_id' => Artist::factory(),
            'name' => fake()->sentence(2),
            'cover' => 'https://example.test/albums/'.str()->random(16).'.png',
            'release_date' => fake()->date(),
            'total_tracks' => rand(1, 24),
        ];
    }
}
