<?php

namespace App\QueryBuilders;

use App\Models\Artist;
use App\Models\Playlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TierlistQueryBuilder extends Builder
{
    public function forExplorePage(?string $search = null): static
    {
        return $this->newQuery()
            ->public()
            ->completed()
            ->when($search, fn (Builder $query, string $search) => $query->whereNameOrSourceLike($search))
            ->with('user', 'source')
            ->withTopTier()
            ->withCount('items')
            ->orderBy('completed_at', 'desc');
    }

    public function forProfilePage(User $user): static
    {
        return $this->newQuery()
            ->where('user_id', $user->getKey())
            ->when($user->getKey() !== Auth::id(), fn (Builder $query) => $query->public()->completed())
            ->with('user', 'source')
            ->withTopTier()
            ->withCount('items')
            ->orderByRaw('completed_at IS NULL DESC, completed_at DESC');
    }

    public function forDashboard(User $user): static
    {
        return $this->newQuery()
            ->where('user_id', $user->getKey())
            ->with('source')
            ->withTopTier()
            ->withCount('items')
            ->orderByRaw('completed_at IS NULL DESC, completed_at DESC');
    }

    /**
     * How many lists this user holds of each type, in one query rather than one
     * per type. Types they have none of are simply absent.
     *
     * @return Collection<string, int>
     */
    public function countsByTypeFor(User $user): Collection
    {
        return $this->newQuery()
            ->where('user_id', $user->getKey())
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');
    }

    /**
     * The whole board, in one go — every tier, its entries, and the catalog
     * record behind each entry. For the builder and the show page only.
     */
    public function withBoard(): static
    {
        return $this->with(['tiers.items.entryable']);
    }

    /**
     * Just enough of the board to draw a card preview, without dragging every
     * tier of every list into a feed of twelve.
     */
    public function withTopTier(int $tiers = 5, int $items = 5): static
    {
        return $this->with([
            'tiers' => fn (Relation $query) => $query->where('is_bank', false)->orderBy('position')->limit($tiers),
            'tiers.items' => fn (Relation $query) => $query->orderBy('position')->limit($items),
            'tiers.items.entryable',
        ]);
    }

    /**
     * A tier list is usually named after nothing in particular, and often has no
     * source at all, so the list's own name has to be searchable too.
     */
    public function whereNameOrSourceLike(string $search): static
    {
        return $this->where(function (Builder $query) use ($search) {
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhereHasMorph('source', [Artist::class, Playlist::class],
                    fn (Builder $query, string $type) => $query->where(
                        $type === Artist::class ? 'artist_name' : 'name',
                        'LIKE',
                        "%{$search}%",
                    ),
                );
        });
    }

    public function publicCompletedCount(): int
    {
        return (int) (round($this->newQuery()->completed()->public()->count() / 25) * 25);
    }

    public function explorableCount(): int
    {
        return $this->newQuery()->public()->completed()->count();
    }

    public function public(): static
    {
        return $this->where('is_public', true);
    }

    public function inProgress(): static
    {
        return $this->where('is_complete', false);
    }

    public function completed(): static
    {
        return $this->where('is_complete', true);
    }
}
