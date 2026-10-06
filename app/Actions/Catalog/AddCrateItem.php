<?php

namespace App\Actions\Catalog;

use App\Models\Crate;
use App\Models\CrateItem;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;

final class AddCrateItem
{
    /**
     * Files an entity at the end of a crate. One already filed there stays
     * where it is.
     */
    public function handle(Crate $crate, Ranking|Tierlist|Review $entity): CrateItem
    {
        return $crate->items()->firstOrCreate([
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
        ]);
    }
}
