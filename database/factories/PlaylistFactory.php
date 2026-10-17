<?php

namespace Database\Factories;

use App\Models\Playlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Playlist>
 */
class PlaylistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'playlist_id' => str()->random(16),
            'creator_id' => str()->random(16),
            'creator_name' => fake()->userName(),
            'name' => fake()->sentence(1),
            'description' => fake()->sentence(1),
            'cover' => 'https://example.test/playlists/'.str()->random(16).'.png',
            'track_count' => rand(5, 100),
        ];
    }
}
