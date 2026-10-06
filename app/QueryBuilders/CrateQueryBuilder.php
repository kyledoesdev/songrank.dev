<?php

namespace App\QueryBuilders;

use App\Enums\CatalogSort;
use App\Models\Crate;
use App\Models\User;
use App\QueryBuilders\Concerns\HasPositions;
use Illuminate\Database\Eloquent\Builder;

class CrateQueryBuilder extends Builder
{
    use HasPositions;

    public function forCatalogPage(User $user): static
    {
        return $this->newQuery()
            ->ownedBy($user)
            ->topLevel()
            ->withCount('items', 'children')
            ->ordered($user);
    }

    /**
     * Every crate the user owns, in display order, for the picker to nest.
     */
    public function forPicker(User $user): static
    {
        return $this->newQuery()
            ->ownedBy($user)
            ->ordered($user);
    }

    /**
     * A free account, or one whose Pro was revoked, always reads A–Z. Its
     * positions are kept, so a custom order comes back with Pro.
     */
    public function ordered(User $user): static
    {
        return $this->reorder()
            ->when(
                $user->catalogSort() === CatalogSort::CUSTOM,
                fn (Builder $query) => $query->orderBy('position'),
            )
            ->orderBy('name')
            ->orderBy('id');
    }

    public function ownedBy(User $user): static
    {
        return $this->where('user_id', $user->getKey());
    }

    public function topLevel(): static
    {
        return $this->whereNull('parent_id');
    }

    /**
     * Directly inside $parent, or at the top level when it is null.
     */
    public function inside(?Crate $parent): static
    {
        return $this->where('parent_id', $parent?->getKey());
    }

    /**
     * The list a crate sits in: the same owner and the same parent. Once the
     * crate is saved it is part of its own list.
     */
    public function siblingsOf(Crate $crate): static
    {
        return $this->where('user_id', $crate->user_id)
            ->where('parent_id', $crate->parent_id);
    }
}
