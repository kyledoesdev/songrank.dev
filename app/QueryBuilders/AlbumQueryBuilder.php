<?php

namespace App\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;

class AlbumQueryBuilder extends Builder
{
    /**
     * Albums that appear on at least one tier list anybody can open.
     */
    public function tierlisted(): static
    {
        return $this->whereHas('tierlistItems.tierlist', fn (Builder $query) => $query->completed()->public());
    }

    public function tierlistedAlbumCount(): int
    {
        return (int) (round($this->newQuery()->tierlisted()->distinct('album_id')->count() / 25) * 25);
    }
}
