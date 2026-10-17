<?php

namespace Database\Factories;

use App\Enums\TierlistType;
use App\Models\Artist;
use App\Models\Tier;
use App\Models\Tierlist;
use App\Models\TierlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TierlistItem>
 */
class TierlistItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tierlist_id' => Tierlist::factory(),
            'tier_id' => Tier::factory(),
            'entryable_type' => TierlistType::ARTIST->value,
            'entryable_id' => Artist::factory(),
            'position' => 0,
        ];
    }

    /**
     * Keeps the item's tier and its list in agreement, which they always are in
     * real data and never are when a test sets only one of them.
     */
    public function inTier(Tier $tier, int $position = 0): static
    {
        return $this->state([
            'tierlist_id' => $tier->tierlist_id,
            'tier_id' => $tier->getKey(),
            'position' => $position,
        ]);
    }
}
