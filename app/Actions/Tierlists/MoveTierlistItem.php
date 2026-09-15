<?php

namespace App\Actions\Tierlists;

use App\Models\Tierlist;
use Illuminate\Support\Facades\DB;

final class MoveTierlistItem
{
    /**
     * Drops an entry into a tier at a slot, shifting only the rows that have to
     * move rather than renumbering the tier.
     *
     * @param  array{item_id: int, position: int, tier_id: int}  $attributes
     */
    public function handle(Tierlist $tierlist, array $attributes): void
    {
        DB::transaction(function () use ($tierlist, $attributes) {
            $item = $tierlist->items()->findOrFail($attributes['item_id']);
            $tier = $tierlist->tiers()->findOrFail($attributes['tier_id']);

            $tierlist->items()
                ->where('tier_id', $item->tier_id)
                ->where('position', '>', $item->position)
                ->decrement('position');

            $others = $tierlist->items()
                ->where('tier_id', $tier->getKey())
                ->whereKeyNot($item->getKey());

            $position = max(0, min($attributes['position'], (clone $others)->count()));

            (clone $others)->where('position', '>=', $position)->increment('position');

            $item->update([
                'tier_id' => $tier->getKey(),
                'position' => $position,
            ]);
        });
    }
}
