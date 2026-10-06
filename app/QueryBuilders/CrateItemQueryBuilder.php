<?php

namespace App\QueryBuilders;

use App\Models\Crate;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use App\QueryBuilders\Concerns\HasPositions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrateItemQueryBuilder extends Builder
{
    use HasPositions;

    public function forCratePage(Crate $crate): static
    {
        return $this->newQuery()
            ->inCrate($crate)
            ->whereEntityExists()
            ->withEntityCards()
            ->orderBy('position')
            ->orderBy('id');
    }

    public function inCrate(Crate $crate): static
    {
        return $this->where('crate_id', $crate->getKey());
    }

    /**
     * Leaves out anything whose record has been deleted since it was filed.
     */
    public function whereEntityExists(): static
    {
        return $this->whereHasMorph('entity', [Ranking::class, Tierlist::class, Review::class]);
    }

    /**
     * Each entity with what its card reads, so a crate full of them costs a
     * handful of queries rather than a handful per card.
     */
    public function withEntityCards(): static
    {
        return $this->with(['entity' => fn (MorphTo $morphTo) => $morphTo->constrain([
            Ranking::class => fn (Builder $query) => $query
                ->with('user', 'source')
                ->with('songs', fn ($query) => $query->where('rank', 1))
                ->withCount('songs'),
            Tierlist::class => fn (Builder $query) => $query
                ->with('user', 'source')
                ->withTopTier()
                ->withCount('items'),
            Review::class => fn (Builder $query) => $query
                ->with('user', 'subject')
                ->withCount('comments'),
        ])]);
    }
}
