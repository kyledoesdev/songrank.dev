<?php

namespace App\QueryBuilders;

use App\Contracts\CrateEntity;
use App\Models\Crate;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use App\Models\User;
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

    /**
     * A crate as somebody browsing a catalog sees it: everything for its owner,
     * only what anybody may open for anyone else.
     */
    public function forCatalogModal(Crate $crate, ?User $viewer): static
    {
        return $this->newQuery()
            ->inCrate($crate)
            ->visibleTo($crate, $viewer)
            ->with(['entity' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                Ranking::class => ['source'],
                Tierlist::class => ['source'],
                Review::class => ['subject'],
            ])])
            ->orderBy('position')
            ->orderBy('id');
    }

    public function visibleTo(Crate $crate, ?User $viewer): static
    {
        return $crate->user_id === $viewer?->getKey()
            ? $this->whereEntityExists()
            : $this->whereEntityIsPublic();
    }

    public function inCrate(Crate $crate): static
    {
        return $this->where('crate_id', $crate->getKey());
    }

    public function whereEntity(CrateEntity $entity): static
    {
        return $this->where('entity_type', $entity->getMorphClass())
            ->where('entity_id', $entity->getKey());
    }

    /**
     * Leaves out anything whose record has been deleted since it was filed.
     */
    public function whereEntityExists(): static
    {
        return $this->whereHasMorph('entity', [Ranking::class, Tierlist::class, Review::class]);
    }

    /**
     * Only records anybody may open: public, and finished.
     */
    public function whereEntityIsPublic(): static
    {
        return $this->whereHasMorph(
            'entity',
            [Ranking::class, Tierlist::class, Review::class],
            fn (Builder $query) => $query->public()->completed(),
        );
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
