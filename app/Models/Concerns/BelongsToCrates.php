<?php

namespace App\Models\Concerns;

use App\Actions\Catalog\RemoveCrateItem;
use App\Models\CrateItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait BelongsToCrates
{
    /**
     * A soft delete never reaches the database cascade, so a deleted record
     * takes itself out of its crates here, whichever path deleted it.
     */
    public static function bootBelongsToCrates(): void
    {
        static::deleted(function (Model $entity) {
            $entity->crateItems()->get()->each(fn (CrateItem $item) => (new RemoveCrateItem)->handle($item));
        });
    }

    public function crateItems(): MorphMany
    {
        return $this->morphMany(CrateItem::class, 'entity');
    }
}
