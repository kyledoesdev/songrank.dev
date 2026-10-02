<?php

namespace Database\Factories;

use App\Actions\Tierlists\CreateDefaultTiers;
use App\Enums\TierlistType;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Tierlist;
use App\Models\TierlistItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Relations\Relation;

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

    /**
     * A finished board always holds at least two placed entries: setup will not
     * start a list with fewer, and it cannot be finished with any left unplaced.
     * They go in the bottom tier so a test's own picks still lead the board.
     */
    public function complete(): static
    {
        return $this->state([
            'is_complete' => true,
            'completed_at' => now(),
        ])->afterCreating(function (Tierlist $tierlist) {
            $bottomTier = $tierlist->tiers()->reorder('position', 'desc')->first();

            TierlistItem::factory()
                ->count(2)
                ->inTier($bottomTier)
                ->sequence(fn (Sequence $sequence) => ['position' => $sequence->index])
                ->create([
                    'entryable_type' => $tierlist->type->value,
                    'entryable_id' => Relation::getMorphedModel($tierlist->type->value)::factory(),
                ]);
        });
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
