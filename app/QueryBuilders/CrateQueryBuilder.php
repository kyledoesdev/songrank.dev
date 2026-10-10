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

    /**
     * One list of crates: the top level on the catalog, or what sits directly
     * inside $parent on its page.
     */
    public function forCatalogPage(User $user, ?Crate $parent = null): static
    {
        return $this->newQuery()
            ->ownedBy($user)
            ->inside($parent)
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
     * Where $crate could move: the user's crates outside its own branch with
     * room beneath them for everything it holds. Its current parent is left
     * out, as moving there would change nothing.
     */
    public function destinationsFor(Crate $crate, User $user): static
    {
        $branch = $crate->branch();
        $height = $branch->max('depth') - $crate->depth;

        return $this->newQuery()
            ->ownedBy($user)
            ->whereNotIn('id', $branch->pluck('id'))
            ->where('depth', '<', $user->crateDepthLimit() - 1 - $height)
            ->when($crate->parent_id, fn (Builder $query, int $parentId) => $query->whereKeyNot($parentId))
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
