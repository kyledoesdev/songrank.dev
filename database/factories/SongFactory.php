<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\Ranking;
use App\Models\Song;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Song>
 */
class SongFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ranking_id' => Ranking::factory(),
            'artist_id' => Artist::factory(),
            'spotify_song_id' => str()->random(16),
            'title' => fake()->sentence(rand(1, 5)),
            'rank' => 0,
            'cover' => 'https://example.test/songs/'.str()->random(16).'.png',
            'uuid' => Str::uuid(),
        ];
    }
}
