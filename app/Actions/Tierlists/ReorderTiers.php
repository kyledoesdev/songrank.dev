<?php

namespace App\Actions\Tierlists;

use App\Models\Tier;
use App\Models\Tierlist;
use Illuminate\Support\Facades\DB;

final class ReorderTiers
{
    /**
     * Swaps a tier with the one above or below it. Its entries ride along.
     */
    public function handle(Tierlist $tierlist, Tier $tier, string $direction): bool
    {
        $tiers = $tierlist->placementTiers();
        $index = $tiers->search(fn (Tier $candidate) => $candidate->is($tier));

        if ($index === false) {
            return false;
        }

        $swapWith = $direction === 'up'
            ? $tiers->get($index - 1)
            : $tiers->get($index + 1);

        if ($index === 0 && $direction === 'up' || is_null($swapWith)) {
            return false;
        }

        DB::transaction(function () use ($tier, $swapWith) {
            $held = $tier->position;

            $tier->update(['position' => $swapWith->position]);
            $swapWith->update(['position' => $held]);
        });

        return true;
    }
}
