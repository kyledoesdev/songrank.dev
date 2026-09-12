<?php

namespace Database\Factories;

use App\Actions\Tierlists\CreateDefaultTiers;
use App\Enums\TierlistType;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Tierlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tierlist>
 */
class TierlistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => TierlistType::ARTIST->value,
            'name' => fake()->userName().' Tier List',
            'is_complete' => false,
            'is_public' => false,
            'completed_at' => null,
        ];
    }

    /* Types */

    public function albums(): static
    {
        return $this->state(['type' => TierlistType::ALBUM->value]);
    }

    public function tracks(): static
    {
        return $this->state(['type' => TierlistType::TRACK->value]);
    }

    /* Provenance */

    public function fromArtist(?Artist $artist = null): static
    {
        return $this->for($artist ?? Artist::factory(), 'source');
    }

    public function fromPlaylist(?Playlist $playlist = null): static
    {
        return $this->for($playlist ?? Playlist::factory(), 'source');
    }

    /* States */

    public function complete(): static
    {
        return $this->state([
            'is_complete' => true,
            'completed_at' => now(),
        ]);
    }

    public function public(): static
    {
        return $this->state(['is_public' => true]);
    }

    /**
     * A list arrives with its tiers, exactly as StoreTierlist will build it —
     * so no test ever has to remember to create a bank.
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (Tierlist $tierlist) => (new CreateDefaultTiers)->handle($tierlist));
    }
}
