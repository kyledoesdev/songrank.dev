<?php

namespace App\Actions\Tierlists\Tiers;

use App\Actions\MaskProfanity;
use App\Models\Tier;

final class UpdateTier
{
    /**
     * @param  array{name: string, color: string}  $attributes
     */
    public function handle(Tier $tier, array $attributes): void
    {
        $tier->update([
            'name' => (new MaskProfanity)->handle($attributes['name']),
            'color' => $attributes['color'],
        ]);
    }
}
