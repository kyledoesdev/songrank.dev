<?php

namespace App\Actions\Tierlists;

use App\Models\Tier;
use App\Models\Tierlist;
use Illuminate\Support\Facades\DB;

final class DestroyTier
{
    /**
     * Removes a tier, handing whatever sat in it back to the holding area.
     * Refuses on the last placement tier.
     */
    public function handle(Tierlist $tierlist, Tier $tier): bool
    {
        if ($tierlist->placementTiers()->count() <= 1) {
            return false;
        }

        DB::transaction(function () use ($tierlist, $tier) {
            $bank = $tierlist->bank;

            $tier->items()->increment('position', (int) $bank->items()->max('position') + 1);
            $tier->items()->update(['tier_id' => $bank->getKey()]);

            $tierlist->tiers()
                ->where('position', '>', $tier->position)
                ->decrement('position');

            $tier->delete();
        });

        return true;
    }
}
