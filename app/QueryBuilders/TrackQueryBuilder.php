<?php

namespace App\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;

class TrackQueryBuilder extends Builder
{
    /**
     * Tracks that appear on at least one tier list anybody can open.
     */
    public function tierlisted(): static
    {
        return $this->whereHas('tierlistItems.tierlist', fn (Builder $query) => $query->completed()->public());
    }

    public function tierlistedTrackCount(): int
    {
        return (int) (round($this->newQuery()->tierlisted()->distinct('track_id')->count() / 25) * 25);
    }
}
