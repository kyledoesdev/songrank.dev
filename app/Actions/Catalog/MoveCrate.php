<?php

namespace App\Actions\Catalog;

use App\Models\Crate;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class MoveCrate
{
    /**
     * Moves a crate, and everything inside it, to the end of $parent's list,
     * or to the top level when $parent is null.
     *
     * Refuses a move into the crate's own branch, and one that would leave
     * anything in the branch deeper than the account allows.
     */
    public function handle(User $user, Crate $crate, ?Crate $parent): bool
    {
        if ($parent?->getKey() === $crate->parent_id) {
            return true;
        }

        $branch = $this->branch($crate);

        if ($parent && $branch->contains(fn (Crate $member) => $member->is($parent))) {
            return false;
        }

        $depth = $parent ? $parent->depth + 1 : 0;

        if ($depth + $branch->max('depth') - $crate->depth >= $user->crateDepthLimit()) {
            return false;
        }

        DB::transaction(function () use ($user, $crate, $parent, $branch, $depth) {
            Crate::query()->siblingsOf($crate)->closeGapAt($crate->position);

            $this->shiftDepth($branch->reject(fn (Crate $member) => $member->is($crate)), $depth - $crate->depth);

            $crate->update([
                'parent_id' => $parent?->getKey(),
                'depth' => $depth,
                'position' => Crate::query()->ownedBy($user)->inside($parent)->count(),
            ]);
        });

        return true;
    }

    /**
     * The crate and every crate inside it, however deep.
     *
     * @return Collection<int, Crate>
     */
    private function branch(Crate $crate): Collection
    {
        $branch = collect([$crate]);
        $level = $branch;

        while ($level->isNotEmpty()) {
            $level = Crate::query()->whereIn('parent_id', $level->pluck('id'))->get();
            $branch = $branch->merge($level);
        }

        return $branch;
    }

    /**
     * @param  Collection<int, Crate>  $crates
     */
    private function shiftDepth(Collection $crates, int $shift): void
    {
        if ($crates->isEmpty() || $shift === 0) {
            return;
        }

        $query = Crate::query()->whereKey($crates->pluck('id'));

        $shift > 0
            ? $query->increment('depth', $shift)
            : $query->decrement('depth', abs($shift));
    }
}
