<?php

namespace App\Actions\Catalog;

use App\Models\Crate;
use App\Models\CrateItem;
use Illuminate\Support\Facades\DB;

final class MoveCrateItem
{
    /**
     * Drops an item into another slot in its crate, shifting only the items
     * between where it was and where it lands.
     *
     * @param  array{item_id: int, position: int}  $attributes
     */
    public function handle(Crate $crate, array $attributes): void
    {
        DB::transaction(function () use ($crate, $attributes) {
            $item = $crate->items()->findOrFail($attributes['item_id']);

            $others = CrateItem::query()->inCrate($crate)->whereKeyNot($item->getKey());

            $position = max(0, min($attributes['position'], (clone $others)->count()));

            $others->makeRoomFor($item->position, $position);

            $item->update(['position' => $position]);
        });
    }
}
