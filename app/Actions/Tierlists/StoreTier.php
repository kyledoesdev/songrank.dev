<?php

namespace App\Actions\Tierlists;

use App\Models\Tier;
use App\Models\Tierlist;

final class StoreTier
{
    /**
     * Adds a tier to the bottom of the board.
     *
     * @param  array{name: string, color: string}  $attributes
     */
    public function handle(Tierlist $tierlist, array $attributes): Tier
    {
        return $tierlist->tiers()->create([
            'name' => $attributes['name'],
            'color' => $attributes['color'],
            'position' => (int) $tierlist->tiers()->max('position') + 1,
            'is_bank' => false,
        ]);
    }
}
