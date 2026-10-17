<?php

namespace App\Actions\Catalog;

use App\Contracts\CrateEntity;
use App\Models\Crate;
use App\Models\CrateItem;

final class AddCrateItem
{
    /**
     * Files an entity at the end of a crate. One already filed there stays
     * where it is.
     */
    public function handle(Crate $crate, CrateEntity $entity): CrateItem
    {
        return $crate->items()->firstOrCreate([
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
        ]);
    }
}
