<?php

namespace App\Models\Concerns;

use App\Enums\CatalogSort;
use App\Models\Crate;

trait HasCrateAllowance
{
    /**
     * How many crates this account may own, nested ones included. `null` is
     * unlimited.
     */
    public function crateLimit(): ?int
    {
        return $this->is_pro
            ? config('billing.catalog_limits.pro.folders')
            : config('billing.catalog_limits.free.folders');
    }

    /**
     * How many levels deep a catalog may go. One is the top level alone.
     */
    public function crateDepthLimit(): int
    {
        return $this->is_pro
            ? config('billing.catalog_limits.pro.depth')
            : config('billing.catalog_limits.free.depth');
    }

    public function crateCount(): int
    {
        return $this->crates()->count();
    }

    public function canCreateCrate(): bool
    {
        $limit = $this->crateLimit();

        if (is_null($limit)) {
            return true;
        }

        return $this->crateCount() < $limit;
    }

    /**
     * Whether a crate may go inside $parent. The overall allowance is
     * canCreateCrate()'s to answer.
     */
    public function canNestIn(Crate $parent): bool
    {
        return $parent->user_id === $this->getKey()
            && $parent->depth + 1 < $this->crateDepthLimit();
    }

    /**
     * Ordering crates by hand and moving them between parents.
     */
    public function canArrangeCrates(): bool
    {
        return $this->is_pro;
    }

    public function catalogSort(): CatalogSort
    {
        if (! $this->canArrangeCrates()) {
            return CatalogSort::ALPHABETICAL;
        }

        return $this->preferences->catalog_sort;
    }
}
