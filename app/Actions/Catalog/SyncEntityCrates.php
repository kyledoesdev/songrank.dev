<?php

namespace App\Actions\Catalog;

use App\Contracts\CrateEntity;
use App\Models\Crate;
use App\Models\CrateItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SyncEntityCrates
{
    /**
     * Leaves the entity filed in exactly the crates ticked: added to the end of
     * any it was missing from, taken out of any no longer ticked. Crates the
     * user does not own are ignored.
     *
     * @param  array<int, int|string>  $crateIds
     */
    public function handle(User $user, CrateEntity $entity, array $crateIds): void
    {
        $ticked = Crate::query()->ownedBy($user)->whereKey($crateIds)->get();

        DB::transaction(function () use ($entity, $ticked) {
            $filed = $entity->crateItems()->get();

            $filed
                ->reject(fn (CrateItem $item) => $ticked->contains('id', $item->crate_id))
                ->each(fn (CrateItem $item) => (new RemoveCrateItem)->handle($item));

            $ticked
                ->reject(fn (Crate $crate) => $filed->contains('crate_id', $crate->getKey()))
                ->each(fn (Crate $crate) => (new AddCrateItem)->handle($crate, $entity));
        });
    }
}
