<?php

namespace App\Actions\Catalog;

use App\Enums\CatalogSort;
use App\Models\Crate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ReorderCrate
{
    /**
     * Moves a crate to another slot in its own list. Ordering by hand is what
     * a custom order means, so the account switches to one.
     */
    public function handle(User $user, Crate $crate, int $position): void
    {
        DB::transaction(function () use ($user, $crate, $position) {
            $others = Crate::query()->siblingsOf($crate)->whereKeyNot($crate->getKey());

            $position = max(0, min($position, (clone $others)->count()));

            $others->makeRoomFor($crate->position, $position);

            $crate->update(['position' => $position]);

            (new UpdateCatalogSort)->handle($user, CatalogSort::CUSTOM);
        });
    }
}
