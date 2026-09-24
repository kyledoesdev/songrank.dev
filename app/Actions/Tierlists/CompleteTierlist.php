<?php

namespace App\Actions\Tierlists;

use App\Models\Tierlist;
use App\Stats\TierlistCompletedStat;

final class CompleteTierlist
{
    /**
     * Publishes the board, once every entry has been placed in a tier.
     */
    public function handle(Tierlist $tierlist): bool
    {
        if (! $tierlist->bankIsEmpty()) {
            return false;
        }

        $tierlist->update([
            'is_complete' => true,
            'completed_at' => now(),
        ]);

        TierlistCompletedStat::increase();

        return true;
    }
}
