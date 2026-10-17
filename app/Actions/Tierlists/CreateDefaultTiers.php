<?php

namespace App\Actions\Tierlists;

use App\Models\Tier;
use App\Models\Tierlist;

final class CreateDefaultTiers
{
    /**
     * Every list is born with the bank at position zero and the configured
     * S-through-F set after it. The owner customises from there.
     */
    public function handle(Tierlist $tierlist): void
    {
        $bank = config('tierlists.bank');

        $tiers = [[
            'tierlist_id' => $tierlist->getKey(),
            'name' => $bank['name'],
            'color' => $bank['color'],
            'position' => 0,
            'is_bank' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]];

        foreach (config('tierlists.default_tiers') as $index => $tier) {
            $tiers[] = [
                'tierlist_id' => $tierlist->getKey(),
                'name' => $tier['name'],
                'color' => $tier['color'],
                'position' => $index + 1,
                'is_bank' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Tier::insert($tiers);
    }
}
